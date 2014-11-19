<?php

namespace Mayeco\GoogleBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MayecoGoogleExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        
        $container->setParameter($this->getAlias() . ".user_agent", $config["user_agent"]);
        
        $container->setParameter($this->getAlias() . ".oauthinfo.client_id", $config["oauth_info"]["client_id"]);
        $container->setParameter($this->getAlias() . ".oauthinfo.client_secret", $config["oauth_info"]["client_secret"]);
        $container->setParameter($this->getAlias() . ".oauthinfo.redirect_url", $config["oauth_info"]["redirect_url"]);
        $container->setParameter($this->getAlias() . ".oauthinfo.access_type", $config["oauth_info"]["access_type"]);
        $container->setParameter($this->getAlias() . ".oauthinfo.approval_prompt", $config["oauth_info"]["approval_prompt"]);
        
        $container->setParameter($this->getAlias() . ".oauthinfo.scope.email", \Google_Service_Oauth2::USERINFO_EMAIL);
        $container->setParameter($this->getAlias() . ".oauthinfo.scope.adwords", \AdWordsUser::OAUTH2_SCOPE);

        $container->setParameter($this->getAlias() . ".oauthinfo",
            array(
                "client_id" => $config["oauth_info"]["client_id"],
                "client_secret" => $config["oauth_info"]["client_secret"],
            )
        );

        $container->setParameter($this->getAlias() . ".adwords.dev_token", $config["adwords"]["dev_token"]);
        $container->setParameter($this->getAlias() . ".adwords.lib_version", $config["adwords"]["lib_version"]);
        
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
