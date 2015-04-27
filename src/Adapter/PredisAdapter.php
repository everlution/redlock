<?php

namespace Everlution\Redlock\Adapter;

use Predis\Client as PredisClient;

class PredisAdapter implements AdapterInterface
{
    private $predis;

    public function __construct(PredisClient $predis)
    {
        $this->predis = $predis;
    }

    public function del($key)
    {
        return $this
            ->predis
            ->del($key)
        ;
    }

    public function exists($key)
    {
        return $this
            ->predis
            ->exists($key)
        ;
    }

    public function get($key)
    {
        return $this
            ->predis
            ->get($key)
        ;
    }

    public function keys($pattern = '*')
    {
        return $this
            ->predis
            ->keys($pattern)
        ;
    }

    public function set($key, $value)
    {
        return $this
            ->predis
            ->set($key, $value)
        ;
    }

    public function setTTL($key, $ttl)
    {
        $this
            ->predis
            ->expire($key, $ttl)
        ;
    }

    public function getTTL($key)
    {
        return $this
            ->predis
            ->ttl($key)
        ;
    }
}
