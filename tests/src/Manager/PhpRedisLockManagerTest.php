<?php

use Everlution\Redlock\Adapter\PhpRedisAdapter;

class PhpRedisLockManagerTest extends LockManagerTest
{
    public function getValidAdapter(array $config)
    {
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port'], $config['timeout']);

        return new PhpRedisAdapter($redis);
    }

    public function getInvalidAdapter(array $config)
    {
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port'], $config['timeout']);

        return new PhpRedisAdapter($redis);
    }
}
