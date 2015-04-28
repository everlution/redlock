<?php

namespace Everlution\Redlock\Exception;

class InvalidLockTypeException extends \Exception
{
    public function __construct($lockType, $code = 0, $previous = null)
    {
        $message = sprintf('Invalid lock type <%s>', $lockType);

        parent::__construct($message, $code, $previous);
    }
}
