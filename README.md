MayecoGoogleBundle
============

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/0fb81cde-508c-4c86-a4e8-9f8e9ac0f715/big.png)](https://insight.sensiolabs.com/projects/0fb81cde-508c-4c86-a4e8-9f8e9ac0f715)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/build-status/master) [![Latest Stable Version](https://poser.pugx.org/mayeco/google-bundle/v/stable.svg)](https://packagist.org/packages/mayeco/google-bundle) [![Latest Unstable Version](https://poser.pugx.org/mayeco/google-bundle/v/unstable.svg)](https://packagist.org/packages/mayeco/google-bundle) [![License](https://poser.pugx.org/mayeco/google-bundle/license.svg)](https://packagist.org/packages/mayeco/google-bundle)

add your settings to config.yml

    mayeco_google:
        oauth_info:
            client_id: client_id_de_proyecto_en_google_apps
            client_secret: client_secret_de_proyecto_en_google_apps
            redirect_url: 'http://www.YOUR_URL.com/authenticate'
        adwords:
            dev_token: google_adwords_api_dev_token

Add your redirect URLs for dev enviroment, in config_dev.yml

    mayeco_google:
        oauth_info:
            redirect_url: 'http://www.YOUR_URL.com/app_dev.php/authenticate'
