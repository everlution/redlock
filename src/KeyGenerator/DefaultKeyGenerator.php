<?php

namespace Everlution\Redlock\KeyGenerator;

use Everlution\Redlock\Model\LockInterface;

class DefaultKeyGenerator implements KeyGeneratorInterface
{
    private $prefix;

    public function __construct($prefix = null)
    {
        $this->prefix = $prefix;
    }

    public function generate(LockInterface $lock)
    {
        return sprintf(
            '%s%s:%s:%s',
            $this->prefix ? $this->prefix : '',
            $lock->getResourceName(),
            $lock->getType(),
            $lock->getToken()
        );
    }

    public function ungenerate($key, LockInterface $lock)
    {
        $chunks = explode(':', $key);

        if (isset($chunks[0])) {
            $lock->setResourceName($chunks[0]);
        }

        if (isset($chunks[1])) {
            $lock->setType($chunks[1]);
        }

        if (isset($chunks[2])) {
            $lock->setToken($chunks[2]);
        }

        return $lock;
    }
}
