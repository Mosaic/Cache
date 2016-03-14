<?php

namespace Mosaic\Cache\Providers;

use Cache\Adapter\Chain\CachePoolChain;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Interop\Container\Definition\DefinitionProviderInterface;
use Mosaic\Cache\Adapters\Psr6\Psr6Cache;
use Mosaic\Cache\Cache;

class PhpCacheProvider implements DefinitionProviderInterface
{
    /**
     * @var callable[]
     */
    private $pools = [];

    /**
     * @param array $pools
     */
    public function __construct(array $pools)
    {
        $this->pools = $pools;
    }

    /**
     * Returns the definition to register in the container.
     *
     * Definitions must be indexed by their entry ID. For example:
     *
     *     return [
     *         'logger' => ...
     *         'mailer' => ...
     *     ];
     *
     * @return array
     */
    public function getDefinitions()
    {
        return [
            Cache::class => function () {

                if (count($this->pools) < 1) {
                    $pools[] = new ArrayCachePool;
                } else {
                    $pools = array_map(function (callable $resolver) {
                        return call_user_func($resolver);
                    }, $this->pools);
                }

                return new Psr6Cache(
                    new CachePoolChain($pools)
                );
            }
        ];
    }
}
