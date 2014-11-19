MayecoGoogleBundle
============

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
