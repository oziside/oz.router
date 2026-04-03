<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die;

use Bitrix\Main\{
    Loader,
    Application
};
use Oz\Router\Problem\ProblemDetailsResponseFactory;
use Throwable;


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

try
{
    $kernel
        ->run()
        ->send();
}
catch (Throwable $exception)
{
    $context = $application->getContext();
    $request = $context->getRequest();
    $response = (new ProblemDetailsResponseFactory())->create($exception, $request);

    $context->setResponse($response);
    $response->send();
}

$application
	->terminate(0);
