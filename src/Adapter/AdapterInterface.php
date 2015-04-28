<?php

namespace Everlution\Redlock\Adapter;

interface AdapterInterface
{
    public function isConnected();

    public function set($key, $value);

    public function del($key);

    public function get($key);

    public function setTTL($key, $ttl);

    public function getTTL($key);

    public function exists($key);

    public function keys($pattern);
}
