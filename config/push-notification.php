<?php

return array(

    // 'appNameIOS'     => array(
    //     'environment' =>'development',
    //     'certificate' =>'ck.pem',
    //     'passPhrase'  =>'core2duo',
    //     'service'     =>'apns'
    // ),
    'appNameIOS'     => array(
        'environment' =>'production',
        'certificate' =>'ckProd.pem',
        'passPhrase'  =>'core2duo',
        'service'     =>'apns'
    ),
    'appNameAndroid' => array(
        'environment' =>'development',
        'apiKey'      =>'AIzaSyBBBdtFvCPsG7zogL0Gi6TBfiC_70cM4OU',
        'service'     =>'gcm'
    )

);