<?php

namespace Mosaic\Cache\Adapters\PhpCache;

use BadMethodCallException;
use Cache\Adapter\Apc\ApcCachePool;
use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Adapter\Memcache\MemcacheCachePool;
use Cache\Adapter\Memcached\MemcachedCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Adapter\Predis\PredisCachePool;
use Cache\Adapter\Void\VoidCachePool;
use Interop\Container\Definition\DefinitionProviderInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Memcache;
use Memcached;
use Mosaic\Cache\Providers\PhpCacheProvider;
use Mosaic\Common\Components\Component as ComponentInterface;
use Mosaic\Common\Conventions\FolderStructureConvention;
use Predis\Client;

class Component implements ComponentInterface
{
    /**
     * @var array
     */
    private static $custom = [];

    /**
     * @var callable[]
     */
    private $pools = [];

    /**
     * @param  callable $callable
     * @return $this
     */
    public function pool(callable $callable)
    {
        $this->pools[] = $callable;

        return $this;
    }

    /**
     * @param  string $host
     * @param  int    $port
     * @return $this
     */
    public function redis(string $host = '127.0.0.1', int $port = 6379)
    {
        return $this->pool(function () use ($host, $port) {
            return new PredisCachePool(
                new Client('tcp:/' . $host . ':' . $port)
            );
        });
    }

    /**
     * @return Component
     */
    public function apc()
    {
        return $this->pool(function () {
            return new ApcCachePool();
        });
    }

    /**
     * @return Component
     */
    public function apcu()
    {
        return $this->pool(function () {
            return new ApcuCachePool();
        });
    }

    /**
     * @param  null      $limit
     * @param  array     $cache
     * @return Component
     */
    public function array($limit = null, array &$cache = [])
    {
        return $this->pool(function () use ($limit, $cache) {
            return new ArrayCachePool($limit, $cache);
        });
    }

    /**
     * @param  string    $host
     * @param  int       $port
     * @return Component
     */
    public function memcache(string $host = '127.0.0.1', int $port = 11211)
    {
        return $this->pool(function () use ($host, $port) {

            $client = new Memcache();
            $client->connect($host, $port);

            return new MemcacheCachePool(
                $client
            );
        });
    }

    /**
     * @param  string    $host
     * @param  int       $port
     * @return Component
     */
    public function memcached(string $host = '127.0.0.1', int $port = 11211)
    {
        return $this->pool(function () use ($host, $port) {

            $client = new Memcached();
            $client->addServer($host, $port);

            return new MemcachedCachePool(
                $client
            );
        });
    }

    /**
     * @param  FolderStructureConvention $folderStructure
     * @return Component
     */
    public function file(FolderStructureConvention $folderStructure)
    {
        return $this->pool(function () use ($folderStructure) {

            $filesystem = new Filesystem(
                new Local($folderStructure->cachePath())
            );

            return new FilesystemCachePool($filesystem);
        });
    }

    /**
     * @return Component
     */
    public function void()
    {
        return $this->pool(function () {
            return new VoidCachePool();
        });
    }

    /**
     * @return DefinitionProviderInterface[]
     */
    public function getProviders() : array
    {
        return [
            new PhpCacheProvider($this->pools)
        ];
    }

    /**
     * @param string   $name
     * @param callable $callback
     */
    public static function extend(string $name, callable $callback)
    {
        self::$custom[$name] = $callback;
    }

    /**
     * @param  string    $name
     * @param  array     $params
     * @return Component
     */
    public function __call(string $name, array $params = [])
    {
        if (isset(self::$custom[$name])) {
            return $this->pool(self::$custom[$name]);
        }

        throw new BadMethodCallException;
    }
}
