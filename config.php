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
?>