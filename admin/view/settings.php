<?
declare(strict_types=1);
use Bitrix\Main\Localization\Loc;

/**
 * @var array $arFields
*/
Loc::loadMessages(__FILE__);

// Base configs
$configRoutesFilePath = $arFields["CONFIG_ROUTES_FILE_PATH"];
$configDIFilePath     = $arFields["CONFIG_DI_FILE_PATH"];
// OpenAPI configs
$openApiSources 	   = $arFields["OPENAPI_SOURCES"];
$openApiSourcesIsEmpty = false;
$openApiSchemaOutput   = $arFields["OPENAPI_SCHEMA_OUTPUT"];

if(empty($openApiSources))
{
	$openApiSourcesIsEmpty = true;
	$openApiSources = [""];
}


// Init generateShemeWaiter
$tabList = [
    [
        "DIV"   => "settingsTab",
        "TAB"   => "Настройки",
        "TITLE" => "Конфигурация роутера"
    ],
    [
		"DIV"   => "openApiTab",
		"TAB"   => "OpenAPI",
		"TITLE" => "Генерация OpenAPI схемы"
	]
];

$tabControl = new CAdminTabControl("settingsTabControl", $tabList);

$tabControl->Begin(); 
?>


<!-- form -->
<form method="post" class="next_options" enctype="multipart/form-data" action="<?= htmlspecialcharsbx($APPLICATION->GetCurPageParam()); ?>">
    <?=bitrix_sessid_post();?>
	<!-- #1 Tab -->
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
					"ELEMENT_ID" 		=> "configRoutesFilePath", 
					"FORM_ELEMENT_NAME" => "configRoutesFilePath"
				],
				"arPath" 		   => ["SITE" => SITE_ID],
				"select" 		   => 'F',// F - file only, D - folder only
				"operation" 	   => 'O',// O - open, S - save
				"showUploadTab"    => false,
				"showAddToMenuTab" => false,
				"allowAllFiles"    => false,
				"SaveConfig" 	   => true,
				"fileFilter" 	   => 'php'
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
					"ELEMENT_ID" 		=> "configDIFilePath", 
					"FORM_ELEMENT_NAME" => "configDIFilePath"
				],
				"arPath" 		   => ["SITE" => SITE_ID],
				"select" 		   => 'F',// F - file only, D - folder only
				"operation" 	   => 'O',// O - open, S - save
				"showUploadTab"    => false,
				"showAddToMenuTab" => false,
				"allowAllFiles"    => false,
				"SaveConfig" 	   => true,
				"fileFilter" 	   => 'php'
			]);?>
        </td>
    </tr>

    <!-- #2 Tab -->
    <?php $tabControl->BeginNextTab(); ?>
	<tr class="heading">
		<td colspan="2">Параметры</td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l" style="width:50%; vertical-align: top;">
			Пути к файлам/папкам с OpenAPI описаниями:<br>
			<small>Относительно корня сайта,<br> например /local/php_interface/openapi</small>
		</td>
		<td class="adm-detail-content-cell-r" style="width:50%">
			<div id="sourcePathsContainer" class="oz-adm-input-group">
				<?foreach ($openApiSources as $path):?>
					<div class="oz-adm-input-group-item">
						<input
							type="text"
							size="70"
							maxlength="255"
							value="<?=$path;?>"
							name="openApiSources[]"
							placeholder="/path/to/openapi/(folder|file)"
						>
					</div>
				<?endforeach;?>
			</div>
			<div>
				<input 
					type="button" 
					id="addSourcePathItemButton" 
					class="adm-btn" 
					value="Добавить"
				>
			</div>
		</td>
	</tr>
	<tr>
		<td class="adm-detail-content-cell-l" style="width:50%">
			Путь сохранения схемы  : <br>
			<small>Относительно корня сайта,<br> например /local/php_interface/openapi/scheme.(json|yaml|yml)</small>
		</td>
		<td class="adm-detail-content-cell-r" style="width:50%">
			<input
				type="text"
				size="70"
				maxlength="255"
				value="<?=$openApiSchemaOutput?>"
				name="openApiSchemaOutput"
				placeholder="/path/to/generated/openapi.json"
			>
		</td>
	</tr>
	<tr class="heading">
		<td colspan="2">
			Генератор (ручной запуск)
		</td>
	</tr>
	<tr>
		<td style="width:50%">
			<?php echo BeginNote('', 'style="text-align: left;"'); ?>
				<!-- Check requirements -->
				<?if($openApiSourcesIsEmpty || empty($openApiSchemaOutput)):?>
					<span class="required">Генерация схемы недоступна</span><br>
					<b>Не указаны:</b><br>
					<?if($openApiSourcesIsEmpty):?>
						- Пути файлам/папкам источникам OpenAPI описаний;<br>
					<?endif;?>
					<?if(empty($openApiSchemaOutput)):?>
						- Путь сохранения сгенерированной схемы;<br>
					<?endif;?>
				<!-- Allow generation -->
				<?else:?>
					<b>Будут просканированы директории:</b><br> 
					<?foreach ($openApiSources as $path):?>
					<i>- <?=$path;?></i><br>
					<?endforeach;?><br>
					<b>Путь сохранения файла:</b> <br>
					<i>- <?=$openApiSchemaOutput;?></i>
				<?endif;?>
			<?php echo EndNote(); ?>
		</td>
		<td style="width:50%; position: relative;">
			<div class="oz-adm-generator-box" id="generateSchemeWaiter">
				<div class="oz-adm-generator-header">
					<input 
						type="button"
						id="generateSchemeButton" 
						class="adm-btn-save" 
						value="Сгенерировать схему" 
						<?if ($openApiSourcesIsEmpty || empty($openApiSchemaOutput)):?>
							title="Не указан путь к файлу генерации схемы"
							disabled
						<?endif;?>
					>
				</div>
				<div id="generateSchemeReponse" class="oz-adm-response">
					Ответ сервера...
				</div>
			</div>
		</td>
	</tr>

    <?php $tabControl->Buttons(); ?>

	<input type="submit" name="save" class="submit-btn adm-btn-save" value="Сохранить" title="Сохранить">
	<input type="submit" name="cancel" value="Сбросить" title="Сбросить">
</form>
<!-- /form -->

<?php $tabControl->End(); ?>

<!-- script section-->

<script>
BX.ready(function()
{
	// Source paths elements
	const sourcePathsContainer 	  = BX('sourcePathsContainer');
	const addSourcePathItemButton = BX('addSourcePathItemButton');
	
	// Generate scheme elements				
	const generateShemeWaiter   = BX('generateSchemeWaiter');
	const generateSchemeReponse = BX('generateSchemeReponse');
	const generateSchemeButton 	= BX('generateSchemeButton');

	function createOpenApiSourcePathInput(value)
	{
		return BX.create('div', {
			props: {
				className: 'oz-adm-input-group-item'
			},
			children: [
				BX.create('input', {
					props: {
						value: value || ''
					},
					attrs: {
						type: 'text',
						size: '70',
						maxlength: '255',
						name: 'openApiSources[]',
						placeholder: '/path/to/openapi/(folder|file)'
					}
				})
			]
		});
	}

	if (!!sourcePathsContainer && !!addSourcePathItemButton)
	{
		BX.bind(addSourcePathItemButton, 'click', function()
		{
			const inputGroup = createOpenApiSourcePathInput('');
			const input = BX.findChild(inputGroup, { tag: 'input' }, true, false);

			BX.append(inputGroup, sourcePathsContainer);

			if (!!input)
			{
				input.focus();
			}
		});
	}

	if (!!generateSchemeButton)
	{
		function responseHandler(data)
		{
			BX.closeWait(generateShemeWaiter);	
			
			generateSchemeButton.classList.remove('adm-btn-load');
			generateSchemeButton.disabled = false;	

			generateSchemeReponse.classList.add('oz-adm-response-' + data.status);
			generateSchemeReponse.innerText = data.status === 'success' 
				? data.data?.join(', ')
				: data.errors?.map(err => '- ' + err.message).join('\n');
		}


		BX.bind(generateSchemeButton, 'click', function()
		{
			const waiter = BX.showWait(generateShemeWaiter);

			generateSchemeButton.classList.add('adm-btn-load');
        	generateSchemeButton.disabled = true;

			BX.ajax({
                url: '/bitrix/admin/oz_router_router.php?view=settings',
                data: {
				   action: 'generateSchemeAction',
				   sessid: BX.bitrix_sessid()
                },
                method: 'POST',
				timeout: 60,
				dataType: 'json',
				processData: true,
				onsuccess: responseHandler,
				onfailure: responseHandler
			});
		});
	}
});
</script>
