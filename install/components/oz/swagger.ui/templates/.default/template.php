<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/** @var CBitrixComponentTemplate $this */
/** @var array $arResult */

Loc::loadMessages(__FILE__);

$containerId = 'oz-swagger-ui-' . $this->randString();
$config = [
	'spec' => $arResult['SPEC'],
	'meta' => [
		'title' => $arResult['TITLE'],
		'version' => $arResult['VERSION'],
		'description' => $arResult['DESCRIPTION'],
		'openapi' => $arResult['OPENAPI'],
	],
	'messages' => Loc::loadLanguageFile(__FILE__),
];
?>
<div id="<?= htmlspecialcharsbx($containerId) ?>" class="oz-swagger-ui" data-role="oz-swagger-ui">
	<script type="application/json" class="oz-swagger-ui__config"><?= Json::encode($config) ?></script>
</div>
