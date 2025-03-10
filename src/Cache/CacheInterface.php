<?php

namespace PhpHttpServer\Cache;

interface CacheInterface
{
    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value, $ttl);

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get($key);

    /**
     * Delete an item from the cache by key.
     *
     * @param string $key
     * @return void
     */
    public function delete($key);

    /**
     * Clear the entire cache.
     *
     * @return void
     */
    public function clear();

    /**
     * Check if an item exists in the cache by key.
     *
     * @param string $key
     * @return bool
     */
    public function exists($key);

    /**
     * Get the number of items in the cache.
     *
     * @return int
     */
    public function size();

    /**
     * Evict expired items from the cache.
     *
     * @return void
     */
    public function evict();

    /**
     * Update an item in the cache by key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function update($key, $value);

    /**
     * Check if the cache is full.
     *
     * @return bool
     */
    public function is_full();
}