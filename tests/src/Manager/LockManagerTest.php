<?php

use Symfony\Component\Yaml\Yaml;
use Everlution\Redlock\Manager\LockManager;
use Everlution\Redlock\Quorum\HalfPlusOneQuorum;
use Everlution\Redlock\KeyGenerator\DefaultKeyGenerator;
use Everlution\Redlock\Adapter\PredisAdapter;
use Everlution\Redlock\Manager\LockTypeManager;
use Everlution\Redlock\Model\Lock;
use Everlution\Redlock\Model\LockType;

class LockManagerTest extends \PHPUnit_Framework_TestCase
{
    private $validAdapters;

    private $invalidAdapters;

    public function setUp()
    {
        parent::setUp();

        $config = Yaml::parse(file_get_contents(CONFIG_YAML));
        $this->validAdapters = $config['manager']['lock']['valid_redis_servers'];
        $this->invalidAdapters = $config['manager']['lock']['invalid_redis_servers'];
    }

    public function testConnectedAdapters()
    {
        // "n" valid
        $manager = $this->newManager(count($this->validAdapters), 0);
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(false));
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(true));

        // "n" - 1 valid
        $manager = $this->newManager(count($this->validAdapters) - 1, 1);
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(false));
        $this->assertCount(count($this->validAdapters) - 1, $manager->getAdapters(true));

        // "n" - 2 valid
        $manager = $this->newManager(count($this->validAdapters) - 2, 2);
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(false));
        $this->assertCount(count($this->validAdapters) - 2, $manager->getAdapters(true));

        // "n" - "n" valid = all invalid
        $manager = $this->newManager(0, count($this->invalidAdapters));
        $this->assertCount(count($this->invalidAdapters), $manager->getAdapters(false));
        $this->assertCount(0, $manager->getAdapters(true));

        // "n" valid and "m" invalid
        $manager = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));
        $this->assertCount(count($this->invalidAdapters) + count($this->validAdapters), $manager->getAdapters(false));
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(true));
    }

    public function testCountKeyHits()
    {
        // all servers have the lock
        $manager1 = $this->newManager(count($this->validAdapters));

        $lock1 = $this->newLock('printer', LockType::EXCLUSIVE, 'dbowdg2879dg2p98dhe');
        $this->assertTrue($manager1->acquireLock($lock1));
        $this->assertEquals(
            count($this->validAdapters),
            $manager1->countKeyHits($manager1->generateKey($lock1))
        );

        // one server is down
        $manager2 = $this->newManager(count($this->validAdapters) - 1, 1);

        $lock2 = $this->newLock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager2->acquireLock($lock2));
        $this->assertEquals(
            count($this->validAdapters) - 1,
            $manager2->countKeyHits($manager2->generateKey($lock2))
        );

        // no servers available
        $manager3 = $this->newManager(0);

        $lock3 = $this->newLock('printer', LockType::PROTECTED_READ, 'n028hd029dh');
        $this->assertFalse($manager3->acquireLock($lock3));
        $this->assertEquals(
            0,
            $manager3->countKeyHits($manager3->generateKey($lock2))
        );

        // 2 servers down
        $manager4 = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));

        $lock4 = $this->newLock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager4->acquireLock($lock4));
        $this->assertEquals(
            count($this->validAdapters),
            $manager4->countKeyHits($manager4->generateKey($lock4))
        );
    }

    public function testGetKeysHits()
    {
        $manager = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));

        $lock = $this->newLock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock));

        $lock = $this->newLock('printer', LockType::PROTECTED_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock));

        $lock = $this->newLock('printer', LockType::CONCURRENT_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock));

        $hits = $manager->getKeysHits($manager->generateKey($lock));

        foreach ($hits as $key => $count) {
            $this->assertEquals(
                count($this->validAdapters),
                $count
            );
        }
    }

    public function testGetCurrentLocks()
    {
        $manager = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));

        $lock1 = $this->newLock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock1));

        $lock2 = $this->newLock('printer', LockType::PROTECTED_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock2));

        $lock3 = $this->newLock('printer', LockType::CONCURRENT_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock3));

        $locks = $manager->getCurrentLocks();

        foreach ($locks as $lock) {
            $this->assertInstanceOf('\Everlution\Redlock\Model\Lock', $lock);

            if ($lock->getType() == LockType::NULL) {
                $this->assertEquals($lock, $lock1);
            } else if ($lock->getType() == LockType::PROTECTED_READ) {
                $this->assertEquals($lock, $lock2);
            } else if ($lock->getType() == LockType::CONCURRENT_READ) {
                $this->assertEquals($lock, $lock3);
            } else {
                $this->assertEquals(1, 0); // never
            }
        }
    }

    public function testEmpty()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $this->assertCount(0, $manager->getCurrentLocks());

        $lock = $this->newLock('ResourceA', LockType::EXCLUSIVE, 'aksbd8bdge2qod7g');
        $this->assertFalse($manager->hasLock($lock));
        $this->assertTrue($manager->canAcquireLock($lock));
    }

    public function testAcquireRelease()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $token1 = 'asidub2798dgdeefddsf';
        $token2 = 'vophyouasbdyasdoausd';

        $nlLock = $this->newLock('printer', LockType::NULL, $token1);
        $cwLock = $this->newLock('printer', LockType::CONCURRENT_WRITE, $token1);
        $exLock = $this->newLock('printer', LockType::EXCLUSIVE, $token2);
        $crLock = $this->newLock('printer', LockType::CONCURRENT_READ, $token1);

        $this->assertFalse($manager->hasLock($nlLock));
        $this->assertTrue($manager->acquireLock($nlLock));
        $this->assertTrue($manager->hasLock($nlLock));

        $this->assertFalse($manager->hasLock($cwLock));
        $this->assertTrue($manager->acquireLock($cwLock));
        $this->assertTrue($manager->hasLock($cwLock));

        $this->assertFalse($manager->hasLock($exLock));
        $this->assertFalse($manager->acquireLock($exLock));
        $this->assertFalse($manager->hasLock($exLock));

        $this->assertFalse($manager->hasLock($crLock));
        $this->assertTrue($manager->acquireLock($crLock));
        $this->assertTrue($manager->hasLock($crLock));

        $this->assertTrue($manager->hasLock($cwLock));
        $this->assertTrue($manager->releaseLock($cwLock));
        $this->assertFalse($manager->hasLock($cwLock));

        $this->assertFalse($manager->hasLock($exLock));
        $this->assertFalse($manager->acquireLock($exLock));
        $this->assertFalse($manager->hasLock($exLock));

        $this->assertTrue($manager->hasLock($crLock));
        $this->assertTrue($manager->releaseLock($crLock));
        $this->assertFalse($manager->hasLock($crLock));

        $this->assertFalse($manager->hasLock($exLock));
        $this->assertTrue($manager->acquireLock($exLock));
        $this->assertTrue($manager->hasLock($exLock));

        $this->assertTrue($manager->hasLock($nlLock));
        $this->assertTrue($manager->releaseLock($nlLock));
        $this->assertFalse($manager->hasLock($nlLock));
    }

    public function testNull()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $lock = $this->newLock('doc', LockType::NULL, 'd92hd982hd8dhw');
        $this->assertTrue($manager->acquireLock($lock));

        $lock = $this->newLock('doc', LockType::CONCURRENT_WRITE, 'noi987ghf28vd');
        $this->assertTrue($manager->acquireLock($lock));
    }

    // helpers

    private function clearAll(LockManager $manager)
    {
        foreach ($manager->getCurrentLocks() as $lock) {
            $manager->releaseLock($lock);
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

    /**
     * newManager.
     *
     * @param int $validAdaptersCount
     * @param int $invalidAdaptersCount
     * @param int $ttl
     * @param int $retries
     * @param int $retryMaxDelay
     * @return LockManager
     */
    private function newManager($validAdaptersCount = 1, $invalidAdaptersCount = 0, $ttl = 60, $retries = 3, $retryMaxDelay = 1)
    {
        $manager = new LockManager(
            new HalfPlusOneQuorum(),
            new DefaultKeyGenerator(),
            new LockTypeManager(),
            $ttl,
            $retries,
            $retryMaxDelay
        );

        $count = count($this->validAdapters);
        if ($validAdaptersCount < $count) {
            $count = $validAdaptersCount;
        }

        foreach ($this->validAdapters as $key => $value) {
            if ($count == 0) {
                break;
            }
            $count--;

            $manager
                ->addAdapter(
                    new PredisAdapter(
                        new \Predis\Client(array(
                            'host'    => $value['host'],
                            'port'    => $value['port'],
                            'timeout' => $value['timeout'],
                        ))
                    )
                )
            ;
        }

        $count = count($this->invalidAdapters);
        if ($invalidAdaptersCount < $count) {
            $count = $invalidAdaptersCount;
        }

        foreach ($this->invalidAdapters as $key => $value) {
            if ($count == 0) {
                break;
            }
            $count--;

            $manager
                ->addAdapter(
                    new PredisAdapter(
                        new \Predis\Client(array(
                            'host'    => $value['host'],
                            'port'    => $value['port'],
                            'timeout' => $value['timeout'],
                        ))
                    )
                )
            ;
        }

        $this->clearAll($manager); // removing all the keys

        return $manager;
    }
}
