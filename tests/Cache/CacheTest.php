<?php

use PHPUnit\Framework\TestCase;
use PhpHttpServer\Cache\Cache;

class CacheTest extends TestCase
{
    private $cache;

    protected function setUp(): void
    {
        // Create a new cache instance before each test
        $this->cache = new Cache(__DIR__ . '/test_cache', 10); // Cache directory and max size of 10
    }

    protected function tearDown(): void
    {
        // Clear the cache after each test
        $this->cache->clear();
    }

    public function testSetAndGet()
    {
        // Test setting and getting a value
        $this->cache->set('key1', 'value1');
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testSetWithCustomTTL()
    {
        // Test setting a value with a custom TTL
        $this->cache->set('key2', 'value2', 2); // TTL of 2 seconds

        // Value should exist immediately
        $this->assertEquals('value2', $this->cache->get('key2'));

        // Wait for TTL to expire
        sleep(3);

        // Value should no longer exist
        $this->assertNull($this->cache->get('key2'));
    }

    public function testDelete()
    {
        // Test deleting a value
        $this->cache->set('key3', 'value3');
        $this->cache->delete('key3');
        $this->assertNull($this->cache->get('key3'));

        // Ensure the cache file is also deleted
        $file = $this->cache->getCacheFilePath('key3');
        $this->assertFileDoesNotExist($file);
    }

    public function testClear()
    {
        // Test clearing the cache
        $this->cache->set('key4', 'value4');
        $this->cache->set('key5', 'value5');
        $this->cache->clear();

        $this->assertNull($this->cache->get('key4'));
        $this->assertNull($this->cache->get('key5'));
        $this->assertEquals(0, $this->cache->size());

        // Ensure all cache files are deleted
        $files = glob('test_cache/*.cache');
        $this->assertEmpty($files);
    }

    public function testExists()
    {
        // Test checking if a key exists
        $this->cache->set('key6', 'value6');
        $this->assertTrue($this->cache->exists('key6'));
        $this->assertFalse($this->cache->exists('nonexistent_key'));
    }

    public function testSize()
    {
        // Test getting the size of the cache
        $this->assertEquals(0, $this->cache->size());

        $this->cache->set('key7', 'value7');
        $this->cache->set('key8', 'value8');
        $this->assertEquals(2, $this->cache->size());
    }

    public function testEvict()
    {
        // Test evicting expired items
        $this->cache->set('key9', 'value9', 1); // TTL of 1 second
        $this->cache->set('key10', 'value10', 3); // TTL of 3 seconds

        sleep(2); // Wait for key9 to expire

        $this->cache->evict();

        $this->assertNull($this->cache->get('key9')); // key9 should be evicted
        $this->assertEquals('value10', $this->cache->get('key10')); // key10 should still exist
    }

    public function testUpdate()
    {
        // Test updating a value
        $this->cache->set('key11', 'value11');
        $this->cache->update('key11', 'updated_value');

        $this->assertEquals('updated_value', $this->cache->get('key11'));
    }

    public function testIsFull()
    {
        // Test checking if the cache is full
        for ($i = 1; $i <= 10; $i++) {
            $this->cache->set("key$i", "value$i");
        }

        $this->assertTrue($this->cache->is_full());

        $this->cache->delete('key1');
        $this->assertFalse($this->cache->is_full());
        $this->cache->clear();
    }
}