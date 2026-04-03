<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

$arComponentParameters = [
	'PARAMETERS' => [
		'SPEC_PATH' => [
			'PARENT' => 'BASE',
			'NAME' => Loc::getMessage('OZ_SWAGGER_UI_PARAM_SPEC_PATH'),
			'TYPE' => 'STRING',
			'DEFAULT' => '/local/php_interface/openapi/scheme.json',
		],
		'CACHE_TIME' => [
			'DEFAULT' => 3600,
		],
	],
];
