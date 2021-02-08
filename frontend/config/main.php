<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);
$modules= array_merge(
    require __DIR__ . '/_modules.php',
    require __DIR__ . '/_fixed_module.php'   
);
return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    //'bootstrap' => ['log'],
    'bootstrap' => ['log', 'maintenanceMode'],
    'controllerNamespace' => 'frontend\controllers',
    //'modules' =>require(__DIR__.'/_modules.php'), // Load routes from PHP File
    'modules' =>$modules,
    //'modules'=>parse_ini_file(Yii::$app->basePath.'/config/module.ini',true),
	'timeZone' => 'Asia/Manila', //set timezone to Manila
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'reports'=>[ //This configuration is for handling request reports for non-dost
            'class'=>'common\components\ReportConfig',
            'ReportName'=>'Request',
            'ReportNumber'=>1
        ],
        'epayment_config'=>[// ePayment Configuration
            'class'=>'common\components\EpaymentConfig',
            'URI'=>'https://yii2customer.onelab.ph/web/api/op',
            
        ],
        'api_config' => [
            'class' => 'common\components\ApiConfig',
            'api_url' => 'https://eulimsapi.onelab.ph/api/web/v1/'
        ],
        'places' => [
            'class' => '\dosamigos\google\places\Places',
            'key' => 'AIzaSyBkbMSbpiE90ee_Jvcrgbb12VRXZ9tlzIc',
            'format' => 'json' // or 'xml'
        ],
        'placesSearch' => [
            'class' => '\dosamigos\google\places\Search',
            'key' => 'AIzaSyBkbMSbpiE90ee_Jvcrgbb12VRXZ9tlzIc',
            'format' => 'json' // or 'xml'
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'js' => [
                        //YII_ENV_DEV ? 'jquery.js' : 'jquery.min.js'
                        'jquery.min.js'
                    ],
                    'jsOptions' => ['position' => \yii\web\View::POS_HEAD],
                ],
                'yii\bootstrap\BootstrapAsset' => [
                    'css' => [
                       // YII_ENV_DEV ? 'css/bootstrap.min.css' : 'css/bootstrap.min.css',
                        'css/bootstrap.min.css'
                    ],
                ],
                'yii\bootstrap\BootstrapPluginAsset' => [
                    'js' => [
                        //YII_ENV_DEV ? 'js/bootstrap.js' : 'js/bootstrap.min.js',
                        'js/bootstrap.min.js'
                    ]
                ]
            ],
        ],
        'maintenanceMode' => [
            // Component class namespace
            'class' => 'brussens\maintenance\MaintenanceMode',
            // Page title
            'title' => 'Under Maintenance!',
            // Mode status
            'enabled' => false,
            // Route to action
            'route' => 'maintenance/index',
            // Show message
            'message' => 'Sorry, we are updating the system. Please come back soon...',
            // Allowed role
            'roles' => [
                'super-administrator',
            ],
            'urls' => [
                'site/login',
		'site/logout',
                'debug/default/toolbar',
                'debug/default/view',
                'settings/disable',
                'settings/enable',
            ],
            // Allowed IP addresses
            //'ips' => [
            // '127.0.0.1',
            //],
            // Layout path
            'layoutPath' => '@frontend/views/admin-lte/layouts/main.php',
            // View path
            'viewPath' => '@frontend/views/maintenance',
            // User name attribute name
            'usernameAttribute' => 'username',
            // HTTP Status Code
            'statusCode' => 503,
            //Retry-After header
            'retryAfter' => 120 //or Wed, 21 Oct 2015 07:28:00 GMT for example
        ],
       
        'user' => [
            'identityClass' => 'common\models\system\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],

        'customeraccount' => [
            'class'=>'yii\web\User',
            'identityClass' => 'common\models\lab\Customeraccount',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-customer', 'httpOnly' => true],
        ],
    
		'referralaccount' => [
            'class'=>'yii\web\User',
            'identityClass' => 'common\models\system\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-referral', 'httpOnly' => true],
			'enableSession' =>false
        ],

        'view' => [
         'theme' => [
             'pathMap' => [
                //'@app/views/' => '@vendor/dmstr/yii2-adminlte-asset/example-views/yiisoft/yii2-app'
                '@app/views' => '@frontend/views/admin-lte'
             ],
         ],
        ],
        'session' => [
            'class' => 'yii\web\CacheSession',
            'cache' => 'sessionCache',
            'timeout' => 600000
         ],
        'sessionCache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@runtime/cache/session'
        ],
        
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManagerBackend'=>[
            'class' => 'yii\web\UrlManager',
            'baseUrl' => '//localhost/eulims/backend/web',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules'=>require(__DIR__.'/_routes.php') // Load routes from PHP File
        ],
        'imagemanager' => [
		'class' => 'noam148\imagemanager\components\ImageManagerGetPath',
		//set media path (outside the web folder is possible)
		'mediaPath' => '../../backend/web/uploads/user/photo',
		//path relative web folder to store the cache images
		'cachePath' => 'assets/images',
		//use filename (seo friendly) for resized images else use a hash
		'useFilename' => true,
		//show full url (for example in case of a API)
		'absoluteUrl' => false,
		'databaseComponent' => 'db' // The used database component by the image manager, this defaults to the Yii::$app->db component
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager', // or use 'yii\rbac\DbManager'
        ]
    ],

    'as access' => [
        'class' => 'mdm\admin\components\AccessControl',
        'allowActions' => [
            'site/logout',
            'lab/booking/index',
            'lab/booking/create',
            'lab/booking/viewcustomer',
            'lab/booking/viewbyreference',
            'lab/csf/index',
            'lab/csf/view',
            'chat/info/*',
            'api/restreferral/*',
            'api/restcustomer/*',
        ]
    ],

    'params' => $params,
];
