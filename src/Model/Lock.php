<?php

namespace Everlution\Redlock\Model;

class Lock implements LockInterface
{
    private $resourceName;

    private $type;

    private $token;

    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    public function getResourceName()
    {
        return $this->resourceName;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }
}
