<?php

namespace Everlution\Redlock\KeyGenerator;

use Everlution\Redlock\Model\LockInterface;

interface KeyGeneratorInterface
{
    /**
     * generate.
     *
     * Creates the key assigned to the lock.
     *
     * @param LockInterface $lock
     *
     * @return string
     */
    public function generate(LockInterface $lock);

    /**
     * ungenerate.
     *
     * Initialize the lock from the key values.
     *
     * @param string $key
     *
     * @return \Everlution\Redlock\Model\LockInterface
     */
    public function ungenerate($key, LockInterface $lock);
}
