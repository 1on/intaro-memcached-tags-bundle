<?php

namespace Intaro\MemcachedTagsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder();
        $tree->root('intaro_memcache_tags')
            ->end();

        return $tree;
    }
}
