<?php

namespace PhpHttpServer\Cache;

class Cache implements CacheInterface
{
    private $cacheDir;
    private $maxSize; // Maximum number of items in the cache
    private $cache = []; // In-memory cache storage

    public function __construct($cacheDir = __DIR__ . '/cache', $maxSize = 100)
    {
        $this->cacheDir = $cacheDir;
        $this->maxSize = $maxSize;

        // Create the cache directory if it doesn't exist
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        // Load existing cache items from disk
        $this->loadCache();
    }

    public function set($key, $value, $ttl = 3600)
    {
        if ($this->is_full()) {
            $this->evict(); // Evict expired or least recently used items
        }

        $this->cache[$key] = [
            'data' => $value,
            'expires' => time() + $ttl
        ];

        $this->saveCache();
    }

    public function get($key)
    {
        if ($this->exists($key)) {
            $item = $this->cache[$key];

            // Check if the item has expired
            if (time() < $item['expires']) {
                return $item['data'];
            } else {
                // Item has expired, delete it
                $this->delete($key);
            }
        }

        return null;
    }

    public function delete($key)
    {
        if ($this->exists($key)) {
            // Remove the item from memory
            unset($this->cache[$key]);

            // Remove the corresponding cache file
            $file = $this->getCacheFilePath($key);
            if (file_exists($file)) {
                unlink($file);
            }

            $this->saveCache();
        }
    }

    public function clear()
    {
        // Remove all items from memory
        $this->cache = [];

        // Remove all cache files
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function exists($key)
    {
        return array_key_exists($key, $this->cache);
    }

    public function size()
    {
        return count($this->cache);
    }

    public function evict()
    {
        foreach ($this->cache as $key => $item) {
            if (time() >= $item['expires']) {
                $this->delete($key);
            }
        }

        // If the cache is still full, remove the oldest item
        if ($this->is_full()) {
            $oldestKey = array_key_first($this->cache);
            $this->delete($oldestKey);
        }
    }

    public function update($key, $value)
    {
        if ($this->exists($key)) {
            $this->cache[$key]['data'] = $value;
            $this->cache[$key]['expires'] = time() + 3600; // Reset TTL
            $this->saveCache();
        }
    }

    public function is_full()
    {
        return $this->size() >= $this->maxSize;
    }

    private function loadCache()
    {
        $files = glob($this->cacheDir . '/*.cache');

        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cache = unserialize($data);

            if (time() < $cache['expires']) {
                $key = basename($file, '.cache');
                $this->cache[$key] = $cache;
            } else {
                // Delete expired cache files
                unlink($file);
            }
        }
    }

    public function getCacheFilePath($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    private function saveCache()
    {
        foreach ($this->cache as $key => $item) {
            $file = $this->getCacheFilePath($key);
            file_put_contents($file, serialize($item));
        }
    }
}