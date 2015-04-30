<?php

namespace Everlution\Redlock\Exception\Adapter;

class InvalidTtlException extends \Exception
{
    public function __construct($ttl, $code = null, $previous = null)
    {
        $message = sprintf('Invalid TTL <%s>', $ttl);

        parent::__construct($message, $code, $previous);
    }
}
