<?php

namespace Mosaic\Cache\Providers;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Interop\Container\Definition\DefinitionProviderInterface;
use Mosaic\Cache\Adapters\Psr6\Psr6Cache;
use Mosaic\Cache\Cache;

class Psr6Provider implements DefinitionProviderInterface
{
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
                return new Psr6Cache(
                    new ArrayCachePool()
                );
            }
        ];
    }
}
