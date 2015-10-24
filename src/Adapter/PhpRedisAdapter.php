<?php

namespace Everlution\Redlock\Adapter;

use Everlution\Redlock\Exception\Adapter\InvalidTtlException;
use RedisException;

class PhpRedisAdapter implements AdapterInterface
{
    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function isConnected()
    {
        try {
            $status = $this->redis->ping();

            return $status == '+PONG';
        } catch (RedisException $e) {
            return false;
        }
    }

    public function set($key, $value, $ttl = null)
    {
        if (!$this->isConnected()) {
            return false;
        }

        $status = $this->redis->set($key, $value);
        if ($status === true && $ttl) {
            $this->redis->expire($key, $ttl);
        }

        return $status === true;
    }

    public function del($key)
    {
        if (!$this->isConnected()) {
            return false;
        }

        return (bool) $this->redis->del($key);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }

    public function setTTL($key, $ttl)
    {
        if (!$this->isConnected()) {
            return false;
        }

        if (!$this->exists($key)) {
            return false;
        }

        if (!is_numeric($ttl)) {
            throw new InvalidTtlException($ttl);
        }

        return $this->redis->expire($key, (int) $ttl);
    }

    public function getTTL($key)
    {
        if (!$this->isConnected()) {
            return false;
        }

        return $this->redis->ttl($key);
    }

    public function exists($key)
    {
        if (!$this->isConnected()) {
            return false;
        }

        return (bool) $this->redis->exists($key);
    }

    public function keys($pattern)
    {
        if (!$this->isConnected()) {
            return array();
        }

        $it = null; /* Initialize our iterator to NULL */

        $redis = $this->redis;
        $redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY); /* retry when we get no keys back */

        $keys = array();
        while ($array = $redis->scan($it, $pattern)) {
            foreach ($array as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
