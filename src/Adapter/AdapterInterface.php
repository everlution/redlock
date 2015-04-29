<?php

namespace Everlution\Redlock\Adapter;

interface AdapterInterface
{
    /**
     * isConnected.
     *
     * Verifies if it can reach the the service.
     *
     * @return bool
     */
    public function isConnected();

    /**
     * set.
     *
     * Sets the value of a key.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function set($key, $value);

    /**
     * del.
     *
     * Deletes a key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function del($key);

    /**
     * get.
     *
     * Retrieves the value for the specified key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * setTTL.
     *
     * Defines the time to leave for a key.
     *
     * @param string $key
     * @param int    $ttl
     *
     * @return bool
     */
    public function setTTL($key, $ttl);

    /**
     * getTTL.
     *
     * Retrieves the TTL for the key.
     *
     * @param string $key
     *
     * @return int
     */
    public function getTTL($key);

    /**
     * exists.
     *
     * Verifies whether a key exists or not.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key);

    /**
     * keys.
     *
     * Returns the keys matching the pattern.
     *
     * @param string $pattern The pattern to match
     *
     * @return array[string]
     */
    public function keys($pattern);
}
