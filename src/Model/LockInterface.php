<?php

namespace Everlution\Redlock\Model;

interface LockInterface
{
    public function setResourceName($resourceName);

    public function getResourceName();

    public function setType($type);

    public function getType();

    public function setToken($token);

    public function getToken();

    public function setValidityTime($validityTime);

    public function getValidityTime();
}
