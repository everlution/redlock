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
        $manager = $this->newManager(count($this->validAdapters), count($this->invalidAdapters));

        $lock = new Lock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock));

        $lock = new Lock('printer', LockType::PROTECTED_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock));

        $lock = new Lock('printer', LockType::CONCURRENT_READ, 'dn87020w80df8gsad');
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

        $lock1 = new Lock('printer', LockType::NULL, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock1));

        $lock2 = new Lock('printer', LockType::PROTECTED_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock2));

        $lock3 = new Lock('printer', LockType::CONCURRENT_READ, 'dn87020w80df8gsad');
        $this->assertTrue($manager->acquireLock($lock3));

        $locks = $manager->getCurrentLocks();

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

    public function testEmpty()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $this->assertCount(0, $manager->getCurrentLocks());

        $lock = new Lock('ResourceA', LockType::EXCLUSIVE, 'aksbd8bdge2qod7g');
        $this->assertFalse($manager->hasLock($lock));
        $this->assertTrue($manager->canAcquireLock($lock));
    }

    public function testAcquireRelease()
    {
        $manager = $this->newManager(count($this->validAdapters));

        $token1 = 'asidub2798dgdeefddsf';
        $token2 = 'vophyouasbdyasdoausd';

        $nlLock = new Lock('printer', LockType::NULL, $token1);
        $cwLock = new Lock('printer', LockType::CONCURRENT_WRITE, $token1);
        $exLock = new Lock('printer', LockType::EXCLUSIVE, $token2);
        $crLock = new Lock('printer', LockType::CONCURRENT_READ, $token1);

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

        $lock = new Lock('doc', LockType::NULL, 'd92hd982hd8dhw');
        $this->assertTrue($manager->acquireLock($lock));

        $lock = new Lock('doc', LockType::CONCURRENT_WRITE, 'noi987ghf28vd');
        $this->assertTrue($manager->acquireLock($lock));
    }

    public function testLockExpired()
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

        return $manager;
    }
}
