<?php
declare(strict_types=1);

use Oz\Router\{
	Module\Route,
	Module\Routes,
	Module\Module
};

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);

$APPLICATION->SetAdditionalCSS('/local/modules/oz.router/admin/admin.css');


/**
 * Init request context
*/
$applicaton = Module::getApplication();
$context    = $applicaton->getContext()->getCurrent(); 
$request 	= $context->getRequest();


/**
 * Cecking route by view param
*/
$view = $request->getQuery('view');

$routes = [
	'settings' => new Routes\SettingsRoute
];

$route   = $routes[$view] ?? null;
$isRoute = $route instanceof Route;
$result  = [];
   

if($isRoute && check_bitrix_sessid())
{  
	/**
	 * Process ajax request
	*/
	if($request->isAjaxRequest())
	{
		$response = $route->ajaxRequestHandler($request);

		if($response)
			$response->send();
		
		$applicaton
			->terminate();
	}
	/**
	 * Process form submit
	*/
	else if($request->isPost())
	{
		$response = $route->formRequestHandler($request);

		if($response)
			$result = $response;
		
		if(isset($response['redirect']))
		{
			LocalRedirect($response['redirect']);
		}
	}	
}

/**
 * Rendering page view
*/
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php";

// Show message if exist
if(isset($result['status']) && isset($result['message']))
{
	CAdminMessage::ShowMessage([
		"MESSAGE" => $result['message'],
		"TYPE"    => $result['status'],
		"DETAILS" => !empty($result['details']) 
			? implode("\n", $result['details']) 
			: '',
	]);
}

$isRoute
	? $route->renderView()
	: require_once(__DIR__ . "/view/404.php");

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php";
