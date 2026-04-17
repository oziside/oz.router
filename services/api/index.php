<?php
define("NOT_CHECK_PERMISSIONS", true);	// Отключаем проверку прав на доступ к файлам и каталогам
define("STOP_STATISTICS", true);		// Отключаем сбор статистики
define("NO_AGENT_CHECK", true);			// Отключаем выполнение агентов
define("NO_AGENT_STATISTIC", true);		// Отключаем выполнение агентов модуля "Статистика"
define("DisableEventsCheck", true);		// Отключаем отправку писем на хите

function __webdavIsDavHeaders() {return false;}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");


if(!\Bitrix\Main\Loader::includeSharewareModule('oz.router'))
{
    return;
}

$config = new \Oz\Router\Module\Config;

$arParams = [
	'ROUTES_FILE_PATH' => $config->getConfigRoutesFilePath(),
];

$APPLICATION->IncludeComponent(
	'oz:router.provider', // component name
	'', 				  // template name
	$arParams, 			  // component params
	null, 				  // parent component
	["HIDE_ICONS"=>"Y"]	  // additional params
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
