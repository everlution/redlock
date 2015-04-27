<?php

use Everlution\Redlock\Manager\LockManager;
use Everlution\Redlock\Quorum\HalfPlusOneQuorum;
use Everlution\Redlock\KeyGenerator\DefaultKeyGenerator;
use Everlution\Redlock\Adapter\PredisAdapter;
use Everlution\Redlock\Manager\LockTypeManager;
use Everlution\Redlock\Model\Lock;
use Everlution\Redlock\Model\LockType;

class LockManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    public function setUp()
    {
        parent::setUp();

        // creating the adapters
        $adapters = array(
            new PredisAdapter(new \Predis\Client('tcp://127.0.0.1:6379?timeout=3&throw_errors=false')),
            new PredisAdapter(new \Predis\Client('tcp://127.0.0.1:6380?timeout=3&throw_errors=false')),
            new PredisAdapter(new \Predis\Client('tcp://127.0.0.1:6381?timeout=3&throw_errors=false')),
            new PredisAdapter(new \Predis\Client('tcp://127.0.0.1:6382?timeout=3&throw_errors=false')),
            new PredisAdapter(new \Predis\Client('tcp://127.0.0.1:6383?timeout=3&throw_errors=false')),
        );

        $this->manager = new LockManager(
            $adapters,
            new HalfPlusOneQuorum(),
            new DefaultKeyGenerator(),
            new LockTypeManager(),
            60,     // TTL
            3,      // Retries
            1       // Retry Max Delay
        );
    }

    private function clearAll()
    {
        foreach ($this->manager->getCurrentLocks() as $lock) {
            $this
                ->manager
                ->releaseLock($lock)
            ;
        }
    }

    private function newLock($resourceName = null, $type = null, $token = null)
    {
        $lock = new Lock();

        if ($resourceName) {
            $lock->setResourceName($resourceName);
        }

        if ($type) {
            $lock->setType($type);
        }

        if ($token) {
            $lock->setToken($token);
        }

        return $lock;
    }

    public function testEmpty()
    {
        $this->clearAll();

        $this->assertCount(0, $this->manager->getCurrentLocks());

        $lock = $this->newLock('ResourceA', LockType::EXCLUSIVE, 'aksbd8bdge2qod7g');
        $this->assertFalse($this->manager->hasLock($lock));
        $this->assertTrue($this->manager->canAcquireLock($lock));
    }

    public function testAcquireRelease()
    {
        $this->clearAll();

        $token1 = 'asidub2798dgdeefddsf';
        $token2 = 'vophyouasbdyasdoausd';

        $nlLock = $this->newLock('printer', LockType::NULL, $token1);
        $cwLock = $this->newLock('printer', LockType::CONCURRENT_WRITE, $token1);
        $exLock = $this->newLock('printer', LockType::EXCLUSIVE, $token2);
        $crLock = $this->newLock('printer', LockType::CONCURRENT_READ, $token1);

        $this->assertFalse($this->manager->hasLock($nlLock));
        $this->assertTrue($this->manager->acquireLock($nlLock));
        $this->assertTrue($this->manager->hasLock($nlLock));

        $this->assertFalse($this->manager->hasLock($cwLock));
        $this->assertTrue($this->manager->acquireLock($cwLock));
        $this->assertTrue($this->manager->hasLock($cwLock));

        $this->assertFalse($this->manager->hasLock($exLock));
        $this->assertFalse($this->manager->acquireLock($exLock));
        $this->assertFalse($this->manager->hasLock($exLock));

        $this->assertFalse($this->manager->hasLock($crLock));
        $this->assertTrue($this->manager->acquireLock($crLock));
        $this->assertTrue($this->manager->hasLock($crLock));

        $this->assertTrue($this->manager->hasLock($cwLock));
        $this->assertTrue($this->manager->releaseLock($cwLock));
        $this->assertFalse($this->manager->hasLock($cwLock));

        $this->assertFalse($this->manager->hasLock($exLock));
        $this->assertFalse($this->manager->acquireLock($exLock));
        $this->assertFalse($this->manager->hasLock($exLock));

        $this->assertTrue($this->manager->hasLock($crLock));
        $this->assertTrue($this->manager->releaseLock($crLock));
        $this->assertFalse($this->manager->hasLock($crLock));

        $this->assertFalse($this->manager->hasLock($exLock));
        $this->assertTrue($this->manager->acquireLock($exLock));
        $this->assertTrue($this->manager->hasLock($exLock));

        $this->assertTrue($this->manager->hasLock($nlLock));
        $this->assertTrue($this->manager->releaseLock($nlLock));
        $this->assertFalse($this->manager->hasLock($nlLock));
    }

    public function testNull()
    {
        $this->clearAll();

        $lock = $this->newLock('doc', LockType::NULL, 'd92hd982hd8dhw');
        $this->assertTrue($this->manager->acquireLock($lock));

        $lock = $this->newLock('doc', LockType::CONCURRENT_WRITE, 'noi987ghf28vd');
        $this->assertTrue($this->manager->acquireLock($lock));
    }

    public function tearDown()
    {
        parent::tearDown();
        #$this->clearAll();
    }
}
