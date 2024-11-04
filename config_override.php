<?php

$sugar_config['oauth2']['client_id'] = '46ca4069-aa34-8b91-4aae-66ae5487b104';
$sugar_config['oauth2']['client_secret'] = 'Prova1';  // Usa il tuo client_secret
$sugar_config['oauth2']['encryption_key'] = 'YITH/FgUOBabmnTYwO/TM4SOOSGFhLnn4vNnBI4bc+c=';  // La tua chiave di crittografia
$sugar_config['oauth2']['private_key'] = 'file:///web/htdocs/www.gestionale-moodon.it/home/index/Api/V8/OAuth2/private.key';
$sugar_config['oauth2']['public_key'] = 'file:///web/htdocs/www.gestionale-moodon.it/home/index/Api/V8/OAuth2/public.key';
$sugar_config['oauth2']['grant_types'] = array(
    'password' => array(
        'class' => 'League\OAuth2\Server\Grant\PasswordGrant',
        'access_token_ttl' => 'PT1H'  // Il tempo di validità del token di accesso
    ),
);
/***CONFIGURATOR***/
$sugar_config['verify_client_ip'] = false; // Disabilita controllo IP
$sugar_config['http_referer']['list'][0] = 'appinstaller.aruba.it';
$sugar_config['default_export_charset'] = 'ISO-8859-1';
$sugar_config['disabled_languages'] = '';
$sugar_config['email_allow_send_as_user'] = false;
$sugar_config['email_xss'] = 'YToxMzp7czo2OiJhcHBsZXQiO3M6NjoiYXBwbGV0IjtzOjQ6ImJhc2UiO3M6NDoiYmFzZSI7czo1OiJlbWJlZCI7czo1OiJlbWJlZCI7czo0OiJmb3JtIjtzOjQ6ImZvcm0iO3M6NToiZnJhbWUiO3M6NToiZnJhbWUiO3M6ODoiZnJhbWVzZXQiO3M6ODoiZnJhbWVzZXQiO3M6NjoiaWZyYW1lIjtzOjY6ImlmcmFtZSI7czo2OiJpbXBvcnQiO3M6ODoiXD9pbXBvcnQiO3M6NToibGF5ZXIiO3M6NToibGF5ZXIiO3M6NDoibGluayI7czo0OiJsaW5rIjtzOjY6Im9iamVjdCI7czo2OiJvYmplY3QiO3M6MzoieG1wIjtzOjM6InhtcCI7czo2OiJzY3JpcHQiO3M6Njoic2NyaXB0Ijt9';
$sugar_config['aod']['enable_aod'] = false;
/***CONFIGURATOR***/