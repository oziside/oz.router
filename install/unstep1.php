<?
global $APPLICATION;
$APPLICATION->SetTitle('Форма удаления модуля');

$moduleId = 'oz.router';
?>
<form action="<?=$APPLICATION->GetCurPage();?>">
    <?=bitrix_sessid_post();?>
    
    <p>
        <span style="color: red; font-weight: bold;">Внимание! Действия необратимы.</span> <br/> 
        <b>Все</b> данные модуля будут удалены из системы без возможности восстановления. <br/>
    </p>
    <br/>
    <p><b>Выберите какие данные нужно удалить:</b></p>

    <input type="hidden" name="lang" value="<?=LANGUAGE_ID;?>">
    <input type="hidden" name="id" value="<?=$moduleId?>">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="prevstep" value="1">
    
    <p>
        <input type="checkbox" name="unistallDB" id="unistallDB" value="Y" checked>
        <label for="unistallDB">Откатить миграции и БД</label>
    </p>
    <p>
        <input type="checkbox" name="deleteEvents" id="deleteEvents" value="Y" checked>
        <label for="deleteEvents">Удалить обработчики событий: <b>$eventManager->unRegisterEventHandler</b></label>
    </p>
    <p>
        <input type="checkbox" name="deleteFiles" id="deleteFiles" value="Y" checked>
        <label for="deleteFiles">Удалить файлы и папки: <b>local/(components/oz, services/oz.*, admin/oz.*)</b></label>
    </p>
    <input type="submit" name="inst" value="Удалить модуль">
</form>