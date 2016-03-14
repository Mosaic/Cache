<?php

namespace Mosaic\Cache\Adapters\Psr6;

use Closure;
use Mosaic\Cache\Cache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class Psr6Cache implements Cache, CacheItemPoolInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $pool;

    /**
     * @param CacheItemPoolInterface $pool
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->pool->hasItem($key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->pool->getItem($key)->get();
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);

        $this->forget($key);

        return $value;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string        $key
     * @param  mixed         $value
     * @param  \DateTime|int $minutes
     * @return bool
     */
    public function put($key, $value, $minutes)
    {
        $cache = $this->pool->getItem($key);

        $cache->set($value);
        $cache->expiresAfter($minutes * 60);

        return $this->pool->save($cache);
    }

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  string        $key
     * @param  mixed         $value
     * @param  \DateTime|int $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->put($key, $value, $minutes);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        $cache = $this->pool->getItem($key);

        $cache->set($value);

        return $this->pool->save($cache);
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string        $key
     * @param  \DateTime|int $minutes
     * @param  \Closure      $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        if (!is_null($value = $this->get($key))) {
            return $value;
        }

        $this->put($key, $value = $callback(), $minutes);

        return $value;
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        if (!is_null($value = $this->get($key))) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key)
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *                    The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *                                  If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *                                  MUST be thrown.
     *
     * @return CacheItemInterface
     *                            The corresponding Cache Item.
     */
    public function getItem($key)
    {
        return $this->pool->getItem($key);
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     *                    An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *                                  If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *                                  MUST be thrown.
     *
     * @return array|\Traversable
     *                            A traversable collection of Cache Items keyed by the cache keys of
     *                            each item. A Cache item will be returned for each key, even if that
     *                            key is not found. However, if no keys are specified then an empty
     *                            traversable MUST be returned instead.
     */
    public function getItems(array $keys = [])
    {
        return $this->pool->getItems($keys);
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *                    The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *                                  If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *                                  MUST be thrown.
     *
     * @return bool
     *              True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        return $this->pool->hasItem($key);
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *              True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        return $this->pool->clear();
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *                    The key for which to delete
     *
     * @throws InvalidArgumentException
     *                                  If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *                                  MUST be thrown.
     *
     * @return bool
     *              True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param  array                    $keys
     *                                        An array of keys that should be removed from the pool.
     * @throws InvalidArgumentException
     *                                       If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *                                       MUST be thrown.
     *
     * @return bool
     *              True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        return $this->pool->deleteItems($keys);
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *                                 The cache item to save.
     *
     * @return bool
     *              True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        return $this->pool->save($item);
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *                                 The cache item to save.
     *
     * @return bool
     *              False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->pool->saveDeferred($item);
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *              True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        return $this->pool->commit();
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function toPsr6() : CacheItemPoolInterface
    {
        return $this;
    }
}
