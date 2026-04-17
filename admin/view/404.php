<?php
declare(strict_types=1);
use Bitrix\Main\{
	Localization\Loc
};

use Oz\Router\{
	Module\Module
};

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage("OZ_ROUTER_ADMIN_404_TITLE"));
?>
<div class="oz-adm-404-wrap">
	<div class="oz-adm-404-card">
		<h1 class="oz-adm-404-code">404</h1>
		<p class="oz-adm-404-text">
			<?=Loc::getMessage("OZ_ROUTER_ADMIN_404_TEXT_1"); ?>
		</p>
		<p class="oz-adm-404-text">
			<?=Loc::getMessage("OZ_ROUTER_ADMIN_404_TEXT_2", [
				"#MODULE_ID#" => Module::getId()
			]); ?>
		</p>
	</div>
</div>
