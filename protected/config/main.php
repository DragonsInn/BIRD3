<?php

// Some short-hands:
$base = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."..";

Yii::setPathOfAlias('cdn',$base.'/cdn');

$BIRD3 = parse_ini_file($base."/config/BIRD3.ini", true);
$version = file_get_contents($base."/config/version.txt");

$CDN = "http://".$BIRD3["CDN"]["url"];

return array(
	'basePath'=>$base."/protected",
	'runtimePath'=>$base."/cache",
	'name'=>'BIRD3',
	'theme'=>'dragonsinn',

	// preloading 'log' component
	'preload'=>array('log', 'user'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
		# User
		'application.modules.user.components.*',
		'application.modules.user.models.*',
		# Booster
		'ext.EScriptBoost.*',
		# Caching
		#'ext.redis.*',
		# Misc
		'ext.iwi.*',
		'ext.BIRD3.components.*',
		'ext.ExtendedClientScript.cssmin.*',
		'ext.ExtendedClientScript.jsmin.*',
	),

	'modules'=>array(
		"user"
	),

	// application components
	'components'=>array(
		'user'=>array(
			'class'=>'BIRD3User',
			'allowAutoLogin'=>true,
			'autoRenewCookie'=>true,
		),
		'assetManager'=>array(
			'basePath'=>$base."/cdn/assets",
			'baseUrl'=>$BIRD3['CDN']['baseUrl']."/assets",
			#'class' => 'EAssetManagerBoost',
      		#'minifiedExtensionFlags'=>array('min.js','minified.js','packed.js')
		),
		'themeManager'=>array(
			'basePath'=>$base."/themes",
			"baseUrl"=>"/themes"
		),
		'cdn'=>array(
			'class'=>'CDNHelper',
			'basePath'=>$base.'/cdn',
			'baseUrl'=>$BIRD3['CDN']['baseUrl']
		),
		'clientScript'=>array(
			#'class'=>'ext.minScript.components.ExtMinScript',
			'class'=>'ext.ExtendedClientScript.ExtendedClientScript',
			'combineCss'=>true,
            'compressCss'=>false,
            'combineJs'=>true,
            'compressJs'=>true,
		),
		'dynamicRes'=>array(
            'class' => 'application.extensions.DynamicRes.DynamicRes',
            'urlConfig' => array(
                'basePath' => dirname(__FILE__).'/../../',
				'baseUrl'=>'/'
            )
        ),
		'db'=>array(
			'connectionString' => 'mysql:host=localhost;dbname='.$BIRD3['DB']['mydb'],
			'emulatePrepare' => true,
			'username' => $BIRD3['DB']['user'],
			'password' => $BIRD3['DB']['pass'],
			'charset' => 'utf8',
			'tablePrefix' => 'tbl_'
		),
		'session'=>array(
			'autoStart'=>true,
			'timeout'=>1000000
		),
		'urlManager'=>array(
			'urlFormat'=>'path',
			'showScriptName'=>false,
			'rules'=>array(
				'<controller:\w+>/<id:\d+>'=>'<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
		'cache'=>array(
			'class'=>'ext.redis.CRedisCache',
		),
		'iwi' => array(
    		'class' => 'application.extensions.iwi.IwiComponent',
    		// GD or ImageMagick
    		'driver' => 'GD',
    		// ImageMagick setup path
    		//'params'=>array('directory'=>'C:/ImageMagick'),
		),
		'errorHandler'=>array(
			// use 'site/error' action to display errors
			'errorAction'=>'site/error',
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
				// uncomment the following to show log messages on web pages
				/*
				array(
					'class'=>'CWebLogRoute',
				),
				*/
			),
		),
	),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'webmaster@example.com',
		'version'=>$version
	),
);
