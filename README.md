GoogleBundle
============

add your settings to parameters.yml

        google_apiclient_clientid: client_id_de_proyecto_en_google_apps
        google_apiclient_clientsecret: client_secret_de_proyecto_en_google_apps

        google_adwordsapi_devkey: google_adwords_api_dev_token
        google_adwordsapi_version: google_adwords_api_version
        google_adwordsapi_useragent: your_app_user_agent

Add your redirect URLs depending the enviroment, in config_dev.yml

    parameters:
        google_apiclient_redirecturl: 'http://www.YOUR_URL.com/app_dev.php/authenticate'
        

In your config_prod.yml use the production URL

    parameters:
        google_apiclient_redirecturl: 'http://www.YOUR_URL.com/authenticate'


You can change the redirect url on the fly by using the function GetGoogleApi to get the GoogleClient object.
