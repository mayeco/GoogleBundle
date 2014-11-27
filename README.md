MayecoGoogleBundle
============

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/mayeco/GoogleBundle/build-status/master)

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
