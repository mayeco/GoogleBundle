GoogleBundle
============

add your settings to parameters.yml

        google_apiclient_clientid: client_id_de_proyecto_en_google_apps
        google_apiclient_clientsecret: client_secret_de_proyecto_en_google_apps

        google_adwordsapi_devkey: google_adwords_api_dev_token
        google_adwordsapi_version: google_adwords_api_version

        google_adwordsapi_clientlib: your_app_client_lib
        google_adwordsapi_useragent: your_app_user_agent
        google_utils_class: Mayeco\GoogleBundle\Services\GoogleUtils

add a new import to config.yml:

    - { resource: constants.php }
    
With this content:

    <?php

    // app/config/constants.php

    $container->setParameter('google_apiclient_scope_userinfo', \Google_Service_Oauth2::USERINFO_EMAIL);
    $container->setParameter('google_adwordsapi_scope', \AdWordsUser::OAUTH2_SCOPE);
    
Add your redirect URLs depending the enviroment, in config_dev.yml

    parameters:
        google_apiclient_redirecturl: 'http://www.YOUR_URL.com/app_dev.php/authenticate'
        

In your config_prod.yml use the production URL

    parameters:
        google_apiclient_redirecturl: 'http://www.YOUR_URL.com/authenticate'


You can change the redirect url on the fly by using the function GetGoogleApi to get the GoogleClient object.
