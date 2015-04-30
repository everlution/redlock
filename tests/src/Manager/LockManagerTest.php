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

    public function testGetAdapters()
    {
        $manager = $this->newManager(0, 0);
        $this->assertCount(0, $manager->getAdapters(false));
        $this->assertCount(0, $manager->getAdapters(true));

        // "n" valid
        $manager = $this->newManager(count($this->validAdapters), 0);
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(false));
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(true));

        // "n" - 1 valid
        $manager = $this->newManager(count($this->validAdapters) - 1, 1);
        $this->assertCount(count($this->validAdapters), $manager->getAdapters(false));
        $this->assertCount(count($this->validAdapters) - 1, $manager->getAdapters(true));

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

        $lock1 = new Lock('printer', LockType::EXCLUSIVE, 'dbowdg2879dg2p98dhe');
        $this->assertTrue($manager1->acquireLock($lock1));
        $this->assertEquals(
            count($this->validAdapters),
            $manager1->countKeyHits($manager1->generateKey($lock1))
        );

        // one server is down
        $manager2 = $this->newManager(count($this->validAdapters) - 1, 1);

        $lock2 = new Lock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager2->acquireLock($lock2));
        $this->assertEquals(
            count($this->validAdapters) - 1,
            $manager2->countKeyHits($manager2->generateKey($lock2))
        );

        // no servers available
        $manager3 = $this->newManager(0);

        $lock3 = new Lock('printer', LockType::PROTECTED_READ, 'n028hd029dh');
        $this->assertFalse($manager3->acquireLock($lock3));
        $this->assertEquals(
            0,
            $manager3->countKeyHits($manager3->generateKey($lock2))
        );

        // 2 servers down
        $manager4 = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));

        $lock4 = new Lock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager4->acquireLock($lock4));
        $this->assertEquals(
            count($this->validAdapters),
            $manager4->countKeyHits($manager4->generateKey($lock4))
        );
    }

    public function testGetKeysHits()
    {
        $keyGenerator = new \Everlution\Redlock\KeyGenerator\DefaultKeyGenerator();

        $locks = array(
            new Lock('printer', LockType::NULL, 'dn87020w80df8gsad'),
            new Lock('printer', LockType::PROTECTED_READ, 'dn87020w80df8gsad'),
            new Lock('printer', LockType::CONCURRENT_READ, 'dn87020w80df8gsad'),
        );

        $keys = array();

        foreach ($locks as $lock) {
            $keys[$keyGenerator->generate($lock)] = $lock;
        }

        $manager = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));

        $hits = $manager->getKeysHits('printer:*:*');
        $this->assertInternalType('array', $hits);
        $this->assertCount(0, $hits);

        $i = 1;
        foreach ($keys as $key => $lock) {
            $manager->acquireLock($lock);

            $hits = $manager->getKeysHits('printer:*:*');
            $this->assertInternalType('array', $hits);
            $this->assertCount($i, $hits);

            $this->assertContains($key, array_keys($keys));

            $i++;
        }
    }

    public function testGetCurrentLocks()
    {
        $manager = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));

        $locks = $manager->getCurrentLocks('*:*:*');
        $this->assertInternalType('array', $locks);
        $this->assertCount(0, $locks);

        $lock1 = new Lock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock1));

        $lock2 = new Lock('printer', LockType::PROTECTED_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock2));

        $lock3 = new Lock('printer', LockType::CONCURRENT_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock3));

        $locks = $manager->getCurrentLocks('printer:*:*');
        $this->assertInternalType('array', $locks);
        $this->assertCount(3, $locks);

        foreach ($locks as $lock) {
            $this->assertInstanceOf('\Everlution\Redlock\Model\Lock', $lock);

            if ($lock->getType() == LockType::NULL) {
                $this->assertEquals($lock, $lock1);
            } elseif ($lock->getType() == LockType::PROTECTED_READ) {
                $this->assertEquals($lock, $lock2);
            } elseif ($lock->getType() == LockType::CONCURRENT_READ) {
                $this->assertEquals($lock, $lock3);
            } else {
                $this->assertEquals(1, 0); // never
            }
        }
    }

    public function testCanAcquireLock()
    {
        $resource1 = 'resource1';

        $token1 = 'iasubd2db22';
        $token2 = 'd72kl2bdlka';

        $manager = $this->newManager(count($this->validAdapters), 0, 120);

        $this->assertTrue(
            $manager->canAcquireLock(
                $lock = new Lock($resource1, LockType::NULL, $token1)
            )
        );
        $manager->acquireLock($lock);

        $this->assertTrue(
            $manager->canAcquireLock(
                $lock = new Lock($resource1, LockType::NULL, $token1)
            )
        );
        $manager->acquireLock($lock);

        $this->assertTrue(
            $manager->canAcquireLock(
                $lock = new Lock($resource1, LockType::EXCLUSIVE, $token1)
            )
        );
        $manager->acquireLock($lock);

        $this->assertTrue(
            $manager->canAcquireLock(
                $lock = new Lock($resource1, LockType::EXCLUSIVE, $token1)
            )
        );
        $manager->acquireLock($lock);

        $this->assertFalse(
            $manager->canAcquireLock(
                $lock = new Lock($resource1, LockType::CONCURRENT_WRITE, $token1)
            )
        );
        $manager->acquireLock($lock);

        $this->assertFalse(
            $manager->canAcquireLock(
                $lock = new Lock($resource1, LockType::EXCLUSIVE, $token2)
            )
        );
        $manager->acquireLock($lock);

        $resource2 = 'resource2';

        $this->assertTrue(
            $manager->canAcquireLock(
                $lock = new Lock($resource2, LockType::EXCLUSIVE, $token1)
            )
        );
        $manager->acquireLock($lock);

        $this->assertFalse(
            $manager->canAcquireLock(
                $lock = new Lock($resource2, LockType::CONCURRENT_READ, $token1)
            )
        );
        $manager->acquireLock($lock);

        $this->assertFalse(
            $manager->canAcquireLock(
                $lock = new Lock($resource2, LockType::EXCLUSIVE, $token2)
            )
        );
        $manager->acquireLock($lock);
    }

    public function testHasLock()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $resource1 = 'resource1';
        $token1 = 'token1';

        $resource2 = 'resource2';
        $token2 = 'token2';

        $lock1 = new Lock($resource1, LockType::NULL, $token1);

        $this->assertFalse($manager->hasLock($lock1));
        $manager->acquireLock($lock1);
        $this->assertTrue($manager->hasLock($lock1));
        $manager->releaseLock($lock1);
        $this->assertFalse($manager->hasLock($lock1));
        $manager->acquireLock($lock1);

        $lock2 = new Lock($resource1, LockType::NULL, $token2);

        $this->assertFalse($manager->hasLock($lock2));
        $manager->acquireLock($lock2);
        $this->assertTrue($manager->hasLock($lock1));
        $this->assertTrue($manager->hasLock($lock2));

        $lock3 = new Lock($resource2, LockType::NULL, $token2);

        $manager->acquireLock($lock3);
        $this->assertTrue($manager->hasLock($lock1));
        $this->assertTrue($manager->hasLock($lock2));
        $this->assertTrue($manager->hasLock($lock3));

        $manager->releaseLock($lock2);
        $this->assertTrue($manager->hasLock($lock1));
        $this->assertFalse($manager->hasLock($lock2));
        $this->assertTrue($manager->hasLock($lock3));
    }

    public function testAcquireLock()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $resource1 = 'resource1';
        $token1 = 'token1';

        $resource2 = 'resource2';
        $token2 = 'token2';

        $lock1 = new Lock($resource1, LockType::NULL, $token1);

        $this->assertTrue($manager->acquireLock($lock1));
        $this->assertTrue($manager->acquireLock($lock1));
        $this->assertTrue($manager->acquireLock($lock1));

        $lock2 = new Lock($resource1, LockType::NULL, $token2);

        $this->assertTrue($manager->acquireLock($lock2));
    }

    public function testReleaseLock()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $resource1 = 'resource1';
        $token1 = 'token1';

        $resource2 = 'resource2';
        $token2 = 'token2';

        $lock1 = new Lock($resource1, LockType::NULL, $token1);

        $this->assertTrue($manager->acquireLock($lock1));
        $this->assertTrue($manager->releaseLock($lock1));
        $this->assertFalse($manager->releaseLock($lock1));
    }

    public function testReleaseAllLocks()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $resource1 = 'resource1';
        $token1 = 'token1';

        $resource2 = 'resource2';
        $token2 = 'token2';

        $manager->acquireLock(new Lock($resource1, LockType::NULL, $token1));
        $manager->acquireLock(new Lock($resource1, LockType::NULL, $token1));
        $manager->acquireLock(new Lock($resource1, LockType::NULL, $token2));
        $manager->acquireLock(new Lock($resource2, LockType::NULL, $token1));
        $manager->acquireLock(new Lock($resource2, LockType::EXCLUSIVE, $token1));

        $this->assertCount(4, $manager->getCurrentLocks('*'));
        $this->assertCount(4, $manager->getCurrentLocks('resource*'));

        $manager->releaseAllLocks();

        $this->assertCount(0, $manager->getCurrentLocks('*'));
        $this->assertCount(0, $manager->getCurrentLocks('resource*'));
    }

    public function aatestLockExpired()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $lock1 = new Lock('doc', LockType::EXCLUSIVE, 'd92hd982hd8dhw', 10); // 10 secs validity
        $lock2 = new Lock('doc', LockType::PROTECTED_WRITE, 'c872e6gdgsdg', 60);

        $this->assertTrue($manager->acquireLock($lock1));
        $this->assertTrue($manager->hasLock($lock1));

        sleep(5);
        $this->assertTrue($manager->hasLock($lock1));
        $this->assertFalse($manager->acquireLock($lock2));

        sleep(2);
        $this->assertTrue($manager->hasLock($lock1));
        $this->assertFalse($manager->acquireLock($lock2));

        sleep(4);
        $this->assertFalse($manager->hasLock($lock1));
        $this->assertTrue($manager->acquireLock($lock2));

        $this->assertFalse($manager->acquireLock($lock1));
        $this->assertFalse($manager->hasLock($lock1));
    }

    public function testLockExtend()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $lock1 = new Lock('doc', LockType::EXCLUSIVE, 'd92hd982hd8dhw', 7); // 7 secs validity

        $this->assertFalse($manager->hasLock($lock1));

        $this->assertTrue($manager->acquireLock($lock1));
        $this->assertTrue($manager->hasLock($lock1));

        sleep(5); // after ~5 secs

        // the lock was initially supposed to expire after ~7 secs

        $lock1->setValidityTime(7);
        $this->assertTrue($manager->acquireLock($lock1)); // overrides the validity time

        sleep(3); // after ~8 secs (~3 secs from the reset)
        $this->assertTrue($manager->hasLock($lock1));

        sleep(5); // after ~13 secs (~8 secs from the reset)
        $this->assertFalse($manager->hasLock($lock1));
    }

    /**
     * @expectedException Everlution\Redlock\Exception\InvalidLockTypeException
     * @expectedExceptionMessageRegExp #Invalid lock type <thisIsAnInvalidLockType>#
     */
    public function testInvalidLockType()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $lock = new Lock('resource1', 'thisIsAnInvalidLockType', 'askdhvasd');

        $manager->acquireLock($lock);
    }

    /**
     * newManager.
     *
     * @param int $validAdaptersCount
     * @param int $invalidAdaptersCount
     * @param int $ttl
     * @param int $retries
     * @param int $retryMaxDelay
     *
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
                            'async'   => $value['async'],
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

        $manager->releaseAllLocks();
        $manager->clearAllLocks();

        return $manager;
    }
}
