<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arComponentDescription = [
	'NAME' => GetMessage('OZ_SWAGGER_UI_COMPONENT_NAME'),
	'DESCRIPTION' => GetMessage('OZ_SWAGGER_UI_COMPONENT_DESCRIPTION'),
	'ICON' => '/images/discount.gif',
	'CACHE_PATH' => 'Y',
	'PATH' => [
		'ID' => 'oz_components',
		'NAME' => GetMessage('OZ_SWAGGER_UI_COMPONENT_SECTION_NAME'),
	],
];
