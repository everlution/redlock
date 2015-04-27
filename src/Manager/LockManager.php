<?php

namespace Everlution\Redlock\Manager;

use Everlution\Redlock\Quorum\QuorumInterface;
use Everlution\Redlock\KeyGenerator\KeyGeneratorInterface;
use Everlution\Redlock\Model\LockInterface;
use Everlution\Redlock\Model\Lock;

class LockManager
{
    const CLOCK_DRIFT_FACTOR = 0.01;

    /**
     * @var array[\Everlution\Redlock\Adapter\AdapterInterface]
     */
    private $adapters;

    /**
     * @var \Everlution\Redlock\Quorum\QuorumInterface
     */
    private $quorum;

    /**
     * @var \Everlution\Redlock\KeyGenerator\KeyGeneratorInterface
     */
    private $keyGenerator;

    /**
     * @var \Everlution\Redlock\Manager\LockTypeManager
     */
    private $lockTypeManager;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var int
     */
    private $retryCount;

    /**
     * @var int
     */
    private $retryMaxDelay;

    public function __construct(
        array $adapters,
        QuorumInterface $quorum,
        KeyGeneratorInterface $keyGenerator,
        LockTypeManager $lockTypeManager,
        $ttl,
        $retryCount,
        $retryMaxDelay
    ) {
        $this->adapters         = $adapters;
        $this->quorum           = $quorum;
        $this->keyGenerator     = $keyGenerator;
        $this->lockTypeManager  = $lockTypeManager;
        $this->ttl              = (int) $ttl;
        $this->retryCount       = (int) $retryCount;
        $this->retryMaxDelay    = (int) $retryMaxDelay;
    }

    /**
     * getCurrentLocks.
     *
     * Returns the current locks defined in redis.
     *
     * @return array[\Everlution\Redlock\Model\Lock]
     */
    public function getCurrentLocks()
    {
        $locks = array();
        foreach ($this->adapters as $adapter) {
            if (!$adapter->isConnected()) {
                continue;
            }
            foreach ($adapter->keys() as $k) {
                if (isset($locks[$k])) {
                    $locks[$k]['count']++;
                } else {
                    $locks[$k] = array(
                        'lock' => $this->keyGenerator->ungenerate($k, new Lock()),
                        'count' => 0,
                    );
                }
            }
        }

        // considering only the quorum
        $finalLocks = array();
        foreach ($locks as $l) {
            if ($this->quorum->isApproved($l['count'])) {
                $finalLocks[] = $l['lock'];
            }
        }

        return $finalLocks;
    }

    public function canAcquireLock(LockInterface $lock)
    {
        /* @var $currentLock \Everlution\Redlock\Model\Lock */
        foreach ($this->getCurrentLocks() as $currentLock) {
            $allowedLocks = $this
                ->lockTypeManager
                ->getConcurrentAllowedLocks($currentLock->getType())
            ;
            if (!in_array($lock->getType(), $allowedLocks)) {
                return false;
            }
        }

        return true;
    }

    /**
     * hasLock.
     *
     * Checks whether the lock has already been assigned. The token allowes to
     * differenciate between clients.
     *
     * @param LockInterface $lock
     *
     * @return bool
     */
    public function hasLock(LockInterface $lock)
    {
        /* @var $l \Everlution\Redlock\Model\Lock */
        foreach ($this->getCurrentLocks() as $l) {
            if ($l->getResourceName() == $lock->getResourceName()
                && $l->getType() == $lock->getType()
                && $l->getToken() == $lock->getToken()
            ) {
                return true;
            }
        }

        return false;
    }

    private function getClockDrift()
    {
        return ($this->ttl * self::CLOCK_DRIFT_FACTOR) + 2;
    }

    public function acquireLock(LockInterface $lock)
    {
        if ($this->hasLock($lock)) {
            return true;
        }

        if (!$this->canAcquireLock($lock)) {
            return false;
        }

        $retries = $this->retryCount;

        $key = $this
            ->keyGenerator
            ->generate($lock)
        ;

        do {
            $n = 0;
            $startTime = microtime(true) * 1000;

            foreach ($this->adapters as $adapter) {
                if (!$adapter->isConnected()) {
                    continue;
                }
                if ($adapter->set($key, $lock->getToken())) {
                    $adapter->setTTL($key, $this->ttl);
                    $n++;
                }
            }

            # Add 2 milliseconds to the drift to account for Redis expires
            # precision, which is 1 millisecond, plus 1 millisecond min drift
            # for small TTLs.
            $drift = $this->getClockDrift();
            $validityTime = $this->ttl - (microtime(true) * 1000 - $startTime) - $drift;

            if ($this->quorum->isApproved($n) && $validityTime > 0) {
                return true;
            } else {
                foreach ($this->adapters as $adapter) {
                    $adapter->del($key);
                }
            }

            usleep($this->getRandomDelay()); // Wait a random delay before to retry

            $retries--;
        } while ($retries > 0);

        return false;
    }

    private function getRandomDelay()
    {
        return mt_rand(floor($this->retryMaxDelay / 2), $this->retryMaxDelay) * 1000;
    }

    public function releaseLock(LockInterface $lock)
    {
        $key = $this
            ->keyGenerator
            ->generate($lock)
        ;

        $i = 0;
        foreach ($this->adapters as $adapter) {
            if ($adapter->isConnected() && $adapter->del($key)) {
                $i++;
            }
        }

        return $this
            ->quorum
            ->isApproved($i)
        ;
    }
}
