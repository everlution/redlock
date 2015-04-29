<?php

namespace Everlution\Redlock\Manager;

use Everlution\Redlock\Model\LockType;
use Everlution\Redlock\Exception\InvalidLockTypeException;

class LockTypeManager
{
    private $truthMatrix = array(
        LockType::NULL => array(
            LockType::NULL              => true,
            LockType::CONCURRENT_READ   => true,
            LockType::CONCURRENT_WRITE  => true,
            LockType::PROTECTED_READ    => true,
            LockType::PROTECTED_WRITE   => true,
            LockType::EXCLUSIVE         => true,
        ),
        LockType::CONCURRENT_READ => array(
            LockType::NULL              => true,
            LockType::CONCURRENT_READ   => true,
            LockType::CONCURRENT_WRITE  => true,
            LockType::PROTECTED_READ    => true,
            LockType::PROTECTED_WRITE   => true,
            LockType::EXCLUSIVE         => false,
        ),
        LockType::CONCURRENT_WRITE => array(
            LockType::NULL              => true,
            LockType::CONCURRENT_READ   => true,
            LockType::CONCURRENT_WRITE  => true,
            LockType::PROTECTED_READ    => false,
            LockType::PROTECTED_WRITE   => false,
            LockType::EXCLUSIVE         => false,
        ),
        LockType::PROTECTED_READ => array(
            LockType::NULL              => true,
            LockType::CONCURRENT_READ   => true,
            LockType::CONCURRENT_WRITE  => false,
            LockType::PROTECTED_READ    => true,
            LockType::PROTECTED_WRITE   => false,
            LockType::EXCLUSIVE         => false,
        ),
        LockType::PROTECTED_WRITE => array(
            LockType::NULL              => true,
            LockType::CONCURRENT_READ   => true,
            LockType::CONCURRENT_WRITE  => false,
            LockType::PROTECTED_READ    => false,
            LockType::PROTECTED_WRITE   => false,
            LockType::EXCLUSIVE         => false,
        ),
        LockType::EXCLUSIVE => array(
            LockType::NULL              => true,
            LockType::CONCURRENT_READ   => false,
            LockType::CONCURRENT_WRITE  => false,
            LockType::PROTECTED_READ    => false,
            LockType::PROTECTED_WRITE   => false,
            LockType::EXCLUSIVE         => false,
        ),
    );

    /**
     * getAll.
     *
     * Returns all the available lock types.
     *
     * @return array[string]
     */
    public function getAll()
    {
        $locks = array();
        foreach ($this->truthMatrix as $type => $value) {
            $locks[] = $type;
        }
        return $locks;
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
        if (!in_array($type, $this->getAll())) {
            throw new InvalidLockTypeException($type);
        }

        $allowedLocks = array();
        foreach ($this->truthMatrix[$type] as $t => $isAllowed) {
            if ($isAllowed) {
                $allowedLocks[] = $t;
            }
        }
        return $allowedLocks;
    }
}
