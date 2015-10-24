<?php

use Symfony\Component\Yaml\Yaml;
use Everlution\Redlock\Adapter\PhpRedisAdapter;

/**
 * Integration test with Phpredis.
 */
class PhpredisAdapterTest extends AdapterTest
{
    public function newAdapter($valid = true)
    {
        $config = Yaml::parse(file_get_contents(CONFIG_YAML));

        if ($valid) {
            $config = $config['adapter']['phpredis']['valid_redis'];
        } else {
            $config = $config['adapter']['phpredis']['invalid_redis'];
        }

        $redis = new \Redis();
        $redis->connect($config['host'], $config['port'], $config['timeout']);

        $adapter = new PhpRedisAdapter($redis);

        if ($valid) {
            foreach ($adapter->keys('*') as $key) {
                $adapter->del($key);
            }
        }

        return $adapter;
    }
}
