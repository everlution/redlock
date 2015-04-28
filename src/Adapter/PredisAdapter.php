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

    public function isConnected()
    {
        try {
            /* @var $status \Predis\Response\Status */
            $status = $this->predis->ping();

            return $status->getPayload() == 'PONG';
        } catch (\Exception $e) {
            return false;
        }
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

    public function keys($pattern)
    {
        return $this
            ->predis
            ->keys($pattern)
        ;
    }

    public function set($key, $value)
    {
        $status = $this
            ->predis
            ->set($key, $value)
        ;

        /* @var $status \Predis\Response\Status */
        return $status->getPayload() == 'OK';
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
