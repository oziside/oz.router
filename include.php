<?

\Bitrix\Main\Loader::registerAutoLoadClasses("oz.router", [
	'\Oz\Router\Module\Routes\SettingsRoute' => 'classes/Routes/SettingsRoute.php',
	
    '\Oz\Router\Module\Service\PathResolver'      => 'classes/Service/PathResolver.php',
    
    '\Oz\Router\Module\Config' => 'classes/Config.php',
    '\Oz\Router\Module\Module' => 'classes/Module.php',
    '\Oz\Router\Module\Route'  => 'classes/Route.php',
]);
