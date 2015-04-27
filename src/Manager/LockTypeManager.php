<?php

namespace Everlution\Redlock\Manager;

use Everlution\Redlock\Model\LockType;
use Everlution\Redlock\Exception\InvalidLockTypeException;

class LockTypeManager
{
    /**
     * getAll.
     *
     * Returns all the available lock types.
     *
     * @return array[string]
     */
    public function getAll()
    {
        return array(
            LockType::NULL,
            LockType::PROTECTED_READ,
            LockType::PROTECTED_WRITE,
            LockType::CONCURRENT_READ,
            LockType::CONCURRENT_WRITE,
            LockType::EXCLUSIVE,
        );
    }

    /**
     * getConcurrentAllowedLocks.
     *
     * Returns the allowed concurrent locks depending by the lock type.
     *
     * @param string $type
     *
     * @return array[string]
     *
     * @throws \Everlution\Redlock\Exception\InvalidLockTypeException
     */
    public function getConcurrentAllowedLocks($type)
    {
        switch ($type) {
            case LockType::NULL:
                return array(
                    LockType::NULL,
                    LockType::PROTECTED_READ,
                    LockType::PROTECTED_WRITE,
                    LockType::CONCURRENT_READ,
                    LockType::CONCURRENT_WRITE,
                    LockType::EXCLUSIVE,
                );
            case LockType::PROTECTED_READ:
                return array(
                    LockType::NULL,
                    LockType::CONCURRENT_READ,
                    LockType::PROTECTED_READ,
                );
            case LockType::PROTECTED_WRITE:
                return array(
                    LockType::NULL,
                    LockType::CONCURRENT_READ,
                );
            case LockType::CONCURRENT_READ:
                return array(
                    LockType::NULL,
                    LockType::PROTECTED_READ,
                    LockType::PROTECTED_WRITE,
                    LockType::CONCURRENT_READ,
                    LockType::CONCURRENT_WRITE,
                );
            case LockType::CONCURRENT_WRITE:
                return array(
                    LockType::NULL,
                    LockType::CONCURRENT_READ,
                    LockType::CONCURRENT_WRITE,
                );
            case LockType::EXCLUSIVE:
                return array(
                    LockType::NULL,
                );
            default:
                throw new InvalidLockTypeException($type);
        }
    }
}
