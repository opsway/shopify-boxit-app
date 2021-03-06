<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=boxit',
            'username' => 'root',
            'password' => 'password',
            'charset' => 'utf8',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                        'class' => 'yii\log\FileTarget',
                        'levels' => ['info'],
                        'categories' => ['info'],
                        'logFile' => '@app/runtime/logs/info.log',
                        'maxFileSize' => 1024 * 2,
                        'maxLogFiles' => 20,
                ],
            ],
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'BoxItAPI' => [
            'class' => 'common\components\BoxItAPI',
        ],
        'ShopifyAPI' => [
            'class' => 'common\components\ShopifyAPI',
        ],
        'ShopifyApp' => [
            'class' => 'common\components\ShopifyApp',
        ],
    ],
];
