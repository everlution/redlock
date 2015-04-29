<?php

namespace Everlution\Redlock\Adapter;

use Predis\Client as PredisClient;
use Everlution\Redlock\Exception\Adapter\InvalidTtlException;

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
        return (bool) $this
            ->predis
            ->del($key)
        ;
    }

    public function exists($key)
    {
        return (bool) $this
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
        if (!$this->exists($key)) {
            return false;
        }

        if (!is_numeric($ttl)) {
            throw new InvalidTtlException($ttl);
        }

        return $this
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
