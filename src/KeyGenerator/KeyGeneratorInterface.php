<?php

namespace Everlution\Redlock\KeyGenerator;

use Everlution\Redlock\Model\LockInterface;

interface KeyGeneratorInterface
{
    public function generate(LockInterface $lock);
}
