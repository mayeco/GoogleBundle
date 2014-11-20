<?php

namespace Mayeco\GoogleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mayeco_google');

        $rootNode
            ->children()
                ->scalarNode('user_agent')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->defaultValue('mayeco_google_bundle')
                ->end()
                ->arrayNode('oauth_info')
                    ->isRequired()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('client_id')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('client_secret')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('redirect_url')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('access_type')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('offline')
                            ->validate()
                            ->ifNotInArray(array('offline', 'online'))
                                ->thenInvalid('Invalid access type "%s"')
                            ->end()
                        ->end()
                        ->scalarNode('approval_prompt')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('force')
                            ->validate()
                            ->ifNotInArray(array('auto', 'force'))
                                ->thenInvalid('Invalid approval prompt "%s"')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('adwords')
                    ->isRequired()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('dev_token')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('lib_version')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('v201409')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
