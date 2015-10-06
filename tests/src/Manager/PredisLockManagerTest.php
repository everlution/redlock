<?php

use Everlution\Redlock\Adapter\PredisAdapter;

class PredisLockManagerTest extends LockManagerTest
{
    public function getValidAdapter(array $config)
    {
        return new PredisAdapter(
            new \Predis\Client(array(
                'host'    => $config['host'],
                'port'    => $config['port'],
                'timeout' => $config['timeout'],
                'async'   => $config['async'],
            ))
        );
    }

    public function getInvalidAdapter(array $config)
    {
        return new PredisAdapter(
            new \Predis\Client(array(
                'host'    => $config['host'],
                'port'    => $config['port'],
                'timeout' => $config['timeout'],
            ))
        );
    }
}
