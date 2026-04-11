<?
declare(strict_types=1);
use Bitrix\Main\Localization\Loc;

/**
 * @var array $arFields
*/
Loc::loadMessages(__FILE__);

$configRoutesFilePath = $arFields["CONFIG_ROUTES_FILE_PATH"];
$configDIFilePath     = $arFields["CONFIG_DI_FILE_PATH"];

$tabList = [
    [
        "DIV"   => "settingsTab",
        "TAB"   => "Настройки",
        "TITLE" => "Конфигурация роутера"
    ],
];

$tabControl = new CAdminTabControl("settingsTabControl", $tabList);

$tabControl->Begin();
?>

<form method="post" class="next_options" enctype="multipart/form-data" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam()); ?>">
    <?=bitrix_sessid_post();?>

    <?php $tabControl->BeginNextTab(); ?>
    <tr class="heading">
        <td colspan="2">Настройки</td>
    </tr>
    <tr class="adm-detail-required-field">
        <td class="adm-detail-content-cell-l" style="width:50%">
            Файл конфигурации маршрутов:
        </td>
        <td class="adm-detail-content-cell-r" style="width:50%">
            <input
                type="text"
                size="40"
                maxlength="255"
                value="<?=$configRoutesFilePath?>"
                name="configRoutesFilePath"
                id="configRoutesFilePath"
            >
            <input type="button" value="..." onclick="SelectRoutesFilePath();">
            <?CAdminFileDialog::ShowScript([
                "event" => "SelectRoutesFilePath",
                "arResultDest" => [
                    "ELEMENT_ID"        => "configRoutesFilePath",
                    "FORM_ELEMENT_NAME" => "configRoutesFilePath"
                ],
                "arPath"          => ["SITE" => SITE_ID],
                "select"          => 'F',
                "operation"       => 'O',
                "showUploadTab"   => false,
                "showAddToMenuTab" => false,
                "allowAllFiles"   => false,
                "SaveConfig"      => true,
                "fileFilter"      => 'php'
            ]);?>
        </td>
    </tr>
    <tr>
        <td class="adm-detail-content-cell-l" style="width:50%">
           Файл конфигурации DI:
        </td>
        <td class="adm-detail-content-cell-r" style="width:50%">
            <input
                type="text"
                size="40"
                maxlength="255"
                value="<?=$configDIFilePath?>"
                name="configDIFilePath"
                id="configDIFilePath"
            >
            <input type="button" value="..." onclick="SelectConfigDiFilePath();">
            <?CAdminFileDialog::ShowScript([
                "event" => "SelectConfigDiFilePath",
                "arResultDest" => [
                    "ELEMENT_ID"        => "configDIFilePath",
                    "FORM_ELEMENT_NAME" => "configDIFilePath"
                ],
                "arPath"          => ["SITE" => SITE_ID],
                "select"          => 'F',
                "operation"       => 'O',
                "showUploadTab"   => false,
                "showAddToMenuTab" => false,
                "allowAllFiles"   => false,
                "SaveConfig"      => true,
                "fileFilter"      => 'php'
            ]);?>
        </td>
    </tr>

    <?php $tabControl->Buttons(); ?>

    <input type="submit" name="save" class="submit-btn adm-btn-save" value="Сохранить" title="Сохранить">
    <input type="submit" name="cancel" value="Сбросить" title="Сбросить">
</form>

<?php $tabControl->End(); ?>
