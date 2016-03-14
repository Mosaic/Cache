<?php

namespace Mosaic\Cache;

use Mosaic\Cache\Adapters\PhpCache\Component as PhpCacheComponent;
use Mosaic\Common\Components\AbstractComponent;

class Component extends AbstractComponent
{
    /**
     * @return PhpCacheComponent
     */
    public static function psr6()
    {
        return static::phpCache();
    }

    /**
     * @return PhpCacheComponent
     */
    public static function phpCache()
    {
        return new PhpCacheComponent();
    }

    /**
     * @param  callable $callback
     * @return array
     */
    public function resolveCustom(callable $callback) : array
    {
        return $callback();
    }
}
