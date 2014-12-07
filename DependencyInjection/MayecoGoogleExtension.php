<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * MIT license.
 */
 
namespace Mayeco\GoogleBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * @author Mario Young <maye.co@gmail.com>
 * @link   maye.co
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

        $container->setParameter("mayeco_google.user_agent", $config["user_agent"]);

        $container->setParameter("mayeco_google.oauthinfo.client_id", $config["oauth_info"]["client_id"]);
        $container->setParameter("mayeco_google.oauthinfo.client_secret", $config["oauth_info"]["client_secret"]);
        $container->setParameter("mayeco_google.oauthinfo.redirect_url", $config["oauth_info"]["redirect_url"]);
        $container->setParameter("mayeco_google.oauthinfo.access_type", $config["oauth_info"]["access_type"]);
        $container->setParameter("mayeco_google.oauthinfo.approval_prompt", $config["oauth_info"]["approval_prompt"]);

        $container->setParameter("mayeco_google.oauthinfo.scopes",
            array(
                \Google_Service_Oauth2::USERINFO_EMAIL,
                \AdWordsUser::OAUTH2_SCOPE,
            )
        );

        $container->setParameter("mayeco_google.oauthinfo",
            array(
                "client_id" => $config["oauth_info"]["client_id"],
                "client_secret" => $config["oauth_info"]["client_secret"],
            )
        );

        $container->setParameter("mayeco_google.adwords.dev_token", $config["adwords"]["dev_token"]);
        $container->setParameter("mayeco_google.adwords.lib_version", $config["adwords"]["lib_version"]);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
