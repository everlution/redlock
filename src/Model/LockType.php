<?php

namespace Everlution\Redlock\Model;

abstract class LockType
{
    /**
     * Null (NL).
     *
     * Indicates interest in the resource, but does not prevent other processes
     * from locking it. It has the advantage that the resource and its lock
     * value block are preserved, even when no processes are locking it.
     */
    const NULL = 'NL';

    /**
     * Protected Read (PR).
     *
     * This is the traditional share lock, which indicates a desire to read the
     * resource but prevents other from updating it. Others can however also
     * read the resource.
     */
    const PROTECTED_READ = 'PR';

    /**
     * Protected Write (PW).
     *
     * This is the traditional update lock, which indicates a desire to read and
     * update the resource and prevents others from updating it. Others with
     * Concurrent Read access can however read the resource.
     */
    const PROTECTED_WRITE = 'PW';

    /**
     * Concurrent Read (CR).
     *
     * Indicates a desire to read (but not update) the resource. It allows other
     * processes to read or update the resource, but prevents others from having
     * exclusive access to it. This is usually employed on high-level resources,
     * in order that more restrictive locks can be obtained on subordinate
     * resources.
     */
    const CONCURRENT_READ = 'CR';

    /**
     * Concurrent Write (CW).
     *
     * Indicates a desire to read and update the resource. It also allows other
     * processes to read or update the resource, but prevents others from having
     * exclusive access to it. This is also usually employed on high-level
     * resources, in order that more restrictive locks can be obtained on
     * subordinate resources.
     */
    const CONCURRENT_WRITE = 'CW';

    /**
     * Exclusive (EX).
     *
     * This is the traditional exclusive lock which allows read and update
     * access to the resource, and prevents others from having any access to it.
     */
    const EXCLUSIVE = 'EX';
}
