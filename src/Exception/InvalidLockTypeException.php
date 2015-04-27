<?php

namespace Everlution\Redlock\Exception;

class InvalidLockTypeException extends \Exception
{
    public function __construct($lock, $code = 0, $previous = null)
    {
        $message = sprintf('Invalid lock <%s>', $lock);

        parent::__construct($message, $code, $previous);
    }
}
