<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die;

use Bitrix\Main\{
    Loader,
    Application
};


if(!Loader::includeSharewareModule('oz.router'))
{
    return;
}

$application = Application::getInstance();
$definitions = [];

if (!empty($arParams['ROUTES_FILE_PATH']))
{
    $configRoutesFilePath = $application->getDocumentRoot() . $arParams['ROUTES_FILE_PATH'];
    $guessedDiFilePath = dirname(dirname($configRoutesFilePath)) . '/di.php';

    if (is_file($guessedDiFilePath))
    {
        $definitions = require $guessedDiFilePath;
    }
}

$router = new \Oz\Router\Router($definitions);

if($arParams['ROUTES_FILE_PATH'])
{
    $router
        ->loadRoutesFromFile($configRoutesFilePath);
}

$kernel = new \Oz\Router\RouterRunner($router);

$kernel
    ->run()
    ->send();

$application
	->terminate(0);
