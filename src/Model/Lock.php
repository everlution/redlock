<?php

namespace Everlution\Redlock\Model;

class Lock implements LockInterface
{
    /**
     * resourceName.
     *
     * The resource identification name
     *
     * @var string
     */
    private $resourceName;

    /**
     * type.
     *
     * The type of the lock
     *
     * @var string
     */
    private $type;

    /**
     * token.
     *
     * Random value used in order to release the lock in a safe way
     *
     * @var string
     */
    private $token;

    /**
     * validityTime.
     *
     * It is both the auto release time, and the time the client has in order to
     * perform the operation required before another client may be able to
     * acquire the lock again. To be specified in seconds.
     *
     * @var int
     */
    private $validityTime;

    public function __construct($resourceName = null, $type = null, $token = null, $validityTime = null)
    {
        $this->resourceName = $resourceName;
        $this->type = $type;
        $this->token = $token;
        $this->validityTime = $validityTime;
    }

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

    public function setValidityTime($validityTime)
    {
        $this->validityTime = $validityTime;

        return $this;
    }

    public function getValidityTime()
    {
        return $this->validityTime;
    }
}
