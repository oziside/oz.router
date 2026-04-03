<?
declare(strict_types=1);
use Bitrix\Main\Localization\Loc;

use Oz\Router\Module\{
    Module
};

Loc::loadMessages(__FILE__);

$moduleId = Module::getId();

$aMenu = [
    [
        "parent_menu" => "global_menu_settings",
        "sort"        => 10,
        "text"        => Loc::getMessage("OZ_ROUTER_ADMIN_MENU_ROOT_TEXT"),
        "section"     => $moduleId,
        "module_id"   => $moduleId,
        "items_id"    => "menu_". $moduleId,
		'icon'        => 'oz_router_menu_icon',
        "items"       => [
            [
                "url"   => "oz_router_router.php?view=settings&lang=".LANGUAGE_ID,
                "text"  => Loc::getMessage("OZ_ROUTER_ADMIN_MENU_SETTINGS_TEXT"),
                "title" => Loc::getMessage("OZ_ROUTER_ADMIN_MENU_SETTINGS_TITLE"),
            ],
            [
                "url"   => "oz_router_router.php?view=swagger&lang=".LANGUAGE_ID,
                "text"  => Loc::getMessage("OZ_ROUTER_ADMIN_MENU_SWAGGER_TEXT"),
                "title" => Loc::getMessage("OZ_ROUTER_ADMIN_MENU_SWAGGER_TITLE"),
            ]
        ]
    ]
];

return $aMenu;
