<?php

namespace Everlution\Redlock\KeyGenerator;

use Everlution\Redlock\Model\LockInterface;

class DefaultKeyGenerator
{
    public function generate(LockInterface $lock)
    {
        return sprintf(
            '%s:%s:%s',
            $lock->getResourceName(),
            $lock->getType(),
            $lock->getToken()
        );
    }
}
