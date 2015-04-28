<?php

use Everlution\Redlock\Manager\LockTypeManager;
use Everlution\Redlock\Model\LockType;

class LockTypeManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    public function setUp()
    {
        parent::setUp();
        $this->manager = new LockTypeManager();
    }

    public function testGetAll()
    {
        $typesTest = $this
            ->manager
            ->getAll()
        ;

        $this->assertCount(6, $typesTest);

        $types = array(
            LockType::NULL,
            LockType::PROTECTED_READ,
            LockType::PROTECTED_WRITE,
            LockType::CONCURRENT_READ,
            LockType::CONCURRENT_WRITE,
            LockType::EXCLUSIVE,
        );

        foreach ($types as $type) {
            $this->assertContains($type, $typesTest);
        }
    }

    public function testNullLock()
    {
        $type = LockType::NULL;
        $allowedTypes = $this
            ->manager
            ->getAll()
        ;

        $allowedTypesTest = $this
            ->manager
            ->getConcurrentAllowedLocks($type)
        ;

        $this->assertCount(6, $allowedTypesTest);

        foreach ($allowedTypesTest as $allowedTypeTest) {
            $this->assertContains($allowedTypeTest, $allowedTypes);
        }
    }

    public function testProtectedReadLock()
    {
        $type = LockType::PROTECTED_READ;
        $allowedTypes = array(
            LockType::NULL,
            LockType::CONCURRENT_READ,
            LockType::PROTECTED_READ,
        );

        $allowedTypesTest = $this
            ->manager
            ->getConcurrentAllowedLocks($type)
        ;

        $this->assertCount(3, $allowedTypesTest);

        foreach ($allowedTypesTest as $allowedTypeTest) {
            $this->assertContains($allowedTypeTest, $allowedTypes);
        }
    }

    public function testProtectedWriteLock()
    {
        $type = LockType::PROTECTED_WRITE;
        $allowedTypes = array(
            LockType::NULL,
            LockType::CONCURRENT_READ,
        );

        $allowedTypesTest = $this
            ->manager
            ->getConcurrentAllowedLocks($type)
        ;

        $this->assertCount(2, $allowedTypesTest);

        foreach ($allowedTypesTest as $allowedTypeTest) {
            $this->assertContains($allowedTypeTest, $allowedTypes);
        }
    }

    public function testConcurrentReadLock()
    {
        $type = LockType::CONCURRENT_READ;
        $allowedTypes = array(
            LockType::NULL,
            LockType::PROTECTED_READ,
            LockType::PROTECTED_WRITE,
            LockType::CONCURRENT_READ,
            LockType::CONCURRENT_WRITE,
        );

        $allowedTypesTest = $this
            ->manager
            ->getConcurrentAllowedLocks($type)
        ;

        $this->assertCount(5, $allowedTypesTest);

        foreach ($allowedTypesTest as $allowedTypeTest) {
            $this->assertContains($allowedTypeTest, $allowedTypes);
        }
    }

    public function testConcurrentWriteLock()
    {
        $type = LockType::CONCURRENT_WRITE;
        $allowedTypes = array(
            LockType::NULL,
            LockType::CONCURRENT_READ,
            LockType::CONCURRENT_WRITE,
        );

        $allowedTypesTest = $this
            ->manager
            ->getConcurrentAllowedLocks($type)
        ;

        $this->assertCount(3, $allowedTypesTest);

        foreach ($allowedTypesTest as $allowedTypeTest) {
            $this->assertContains($allowedTypeTest, $allowedTypes);
        }
    }

    public function testExclusiveLock()
    {
        $type = LockType::EXCLUSIVE;
        $allowedTypes = array(
            LockType::NULL,
        );

        $allowedTypesTest = $this
            ->manager
            ->getConcurrentAllowedLocks($type)
        ;

        $this->assertCount(1, $allowedTypesTest);

        foreach ($allowedTypesTest as $allowedTypeTest) {
            $this->assertContains($allowedTypeTest, $allowedTypes);
        }
    }

    /**
     * @expectedException Everlution\Redlock\Exception\InvalidLockTypeException
     * @expectedExceptionMessageRegExp #Invalid lock <.*>#
     */
    public function testInvalidLock()
    {
        $this
            ->manager
            ->getConcurrentAllowedLocks('kasdb')
        ;
    }
}
