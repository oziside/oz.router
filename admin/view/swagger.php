<?
declare(strict_types=1);
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * @var array $arFields
 */
$openApiSchemaOutputFile = $arFields["OPENAPI_SCHEMA_OUTPUT_FILE"];

$tabList = [[
	"DIV" 	=> "swaggerUiTab",
	"TAB" 	=> Loc::getMessage("OZ_ROUTER_SWAGGER_VIEW_TAB"),
	"TITLE" => Loc::getMessage("OZ_ROUTER_SWAGGER_VIEW_TAB_TITLE"),
]];

$tabControl = new CAdminTabControl("swaggerTabControl", $tabList);
?>

<?php $tabControl->Begin(); ?>
<?php $tabControl->BeginNextTab(); ?>
<tr>
	<td colspan="2">
		<?
		$APPLICATION->IncludeComponent('oz:swagger.ui', '', [
			'SPEC_PATH'  => $openApiSchemaOutputFile,
			'CACHE_TIME' => 3600,
		]);
		?>
	</td>
</tr>
<?php $tabControl->End(); ?>

<?php echo BeginNote(); ?>
	<?= htmlspecialcharsbx((string)Loc::getMessage("OZ_ROUTER_SWAGGER_VIEW_SCHEMA_PATH_NOTE_LABEL")); ?>
	<?=$openApiSchemaOutputFile 
		? htmlspecialcharsbx($openApiSchemaOutputFile) 
		: Loc::getMessage("OZ_ROUTER_SWAGGER_VIEW_SCHEMA_PATH_NOT_FOUND"); 
	?> <br>
	<?= htmlspecialcharsbx((string)Loc::getMessage("OZ_ROUTER_SWAGGER_VIEW_SCHEMA_PATH_NOTE_HINT")); ?>
<?php echo EndNote(); ?>
