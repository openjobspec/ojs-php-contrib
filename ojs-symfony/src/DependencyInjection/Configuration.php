<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ojs');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('url')
                    ->defaultValue('http://localhost:8080')
                    ->info('Base URL of the OJS backend')
                ->end()
                ->scalarNode('auth_token')
                    ->defaultNull()
                    ->info('Optional Bearer token for authentication')
                ->end()
                ->scalarNode('default_queue')
                    ->defaultValue('default')
                    ->info('Default queue name')
                ->end()
                ->integerNode('timeout')
                    ->defaultValue(30)
                    ->info('HTTP timeout in seconds')
                ->end()
                ->arrayNode('worker')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('queues')
                            ->scalarPrototype()->end()
                            ->defaultValue(['default'])
                            ->info('Queues the worker will consume from')
                        ->end()
                        ->integerNode('concurrency')
                            ->defaultValue(10)
                            ->info('Number of concurrent job processors')
                        ->end()
                        ->floatNode('poll_interval')
                            ->defaultValue(2.0)
                            ->info('Seconds between poll cycles')
                        ->end()
                        ->floatNode('heartbeat_interval')
                            ->defaultValue(15.0)
                            ->info('Seconds between heartbeats')
                        ->end()
                        ->floatNode('shutdown_timeout')
                            ->defaultValue(25.0)
                            ->info('Seconds to wait for in-flight jobs on shutdown')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('encryption')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('current_key_id')
                            ->isRequired()
                            ->info('ID of the active encryption key')
                        ->end()
                        ->arrayNode('keys')
                            ->useAttributeAsKey('id')
                            ->scalarPrototype()->end()
                            ->info('Map of key ID => hex-encoded 256-bit key')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('events')
                    ->canBeEnabled()
                    ->info('Bridge OJS SSE events to Symfony EventDispatcher')
                ->end()
                ->arrayNode('health')
                    ->canBeEnabled()
                    ->info('Register OJS health check service')
                ->end()
                ->arrayNode('messenger')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('queue')
                            ->defaultValue('default')
                            ->info('Default queue for Messenger transport')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
