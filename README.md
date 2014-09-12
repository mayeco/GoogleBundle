GoogleBundle
============

In parameters.yml


    google_apiclient_class: Google_Client
    google_apiclient_clientid: client_id_de_proyecto_en_google_apps
    google_apiclient_clientsecret: client_secret_de_proyecto_en_google_apps
    google_apiclient_redirecturl: redirect_url_proyecto_en_google_apps

    google_adwordsapi_class: AdWordsUser
    google_adwordsapi_devkey: google_adwords_api_dev_token
    google_adwordsapi_version: google_adwords_api_version

    google_adwordsapi_clientlib: mentalidad user agent
    google_adwordsapi_useragent: mentalidad user lib agent
    google_adwordsapi_oauthinfo:
        client_id: "%google_apiclient_clientid%"
        client_secret: "%google_apiclient_clientsecret%"

    google_utils_class: Mayeco\GoogleBundle\Services\GoogleUtils
