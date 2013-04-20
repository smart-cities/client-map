<?php
	require './../../common.php';

	// remove this once the autoloader starts working...
	require $GLOBALS['config']['rootpath'].'/classes/api/DeviceApi.class.php';
	require $GLOBALS['config']['rootpath'].'/classes/api/ReadingApi.class.php';
	require $GLOBALS['config']['rootpath'].'/classes/DBObjectResponse.class.php';

	use Luracast\Restler\AutoLoader;

	$loader = AutoLoader::instance();
	spl_autoload_register($loader);

	use Luracast\Restler\Defaults;
	use Luracast\Restler\Restler;
	use Luracast\Restler\Resources;

	SmartCitiesAutoloader::Register();


	Defaults::$responderClass = 'DBOjbectResponse';

	$r = new Restler();

	$r->setSupportedFormats('JsonFormat', 'XmlFormat','YamlFormat');

	Defaults::$useUrlBasedVersioning = false;
	Defaults::$smartAutoRouting = true;

	//api doc generation
	Resources::$useFormatAsExtension = false;
	Resources::$hideProtected = false;
	$r->addAPIClass('Luracast\Restler\Resources');

	// setup API modules

	//$r->init();

	// first parameter is the class name,
	// second parameter is the exposed URL path for the class methods
	$r->addAPIClass('DeviceApi', 'device');
	$r->addAPIClass('ReadingApi', 'reading');

	// debug
	if (isset($_REQUEST['dump_routes'])) { var_dump($r->routes);exit; }

	// deal with request
	$r->handle();