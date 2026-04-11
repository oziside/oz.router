<?
declare(strict_types=1);
namespace Oz\Router\Module\Routes;

use Bitrix\Main\{
    HttpRequest,
    Engine\Response,
    Localization\Loc
};
use Oz\Router\{
    Module\Route,
    Module\Module,
    Module\Config,
    Module\Service,
};

Loc::loadMessages(__FILE__);


class SettingsRoute extends Route
{
    private Config $cfg;
    private Service\PathResolver $pathResolver;

    public function __construct()
    {
        $this->cfg = Module::getConfig();
        $this->pathResolver = new Service\PathResolver;
    }

    public function getViewName(): string
    {
        return 'settings';
    }

    
    /**
     * Proccess from request 
    */ 
    public function formRequestHandler(
        HttpRequest $request
    ): array
    {
        $isSaveAction = $request->getPost("save");

        if($isSaveAction)
        {
            $this->saveSettings($request);
        }

        // Return response
        $status  = empty($this->errors) ? 'success' : 'error';
        $details = empty($this->errors) ? [] : $this->errors;

        return $this->formResponse($status,$details);
    }


    /**
     * Proccess ajax request
    */
    public function ajaxRequestHandler(
        HttpRequest $request
    ): ?Response\AjaxJson
    {
        return null;
    }

    /**
     * Prepares data for view
    */
    public function prepareViewData(): array
    {
        return [
            // Base confs
            "CONFIG_ROUTES_FILE_PATH" => $this->cfg->getConfigRoutesFilePath(),
            "CONFIG_DI_FILE_PATH"     => $this->cfg->getConfigDIFilePath(),
        ];
    }


    /**
     * Saves settings from form submit, 
     * validates paths and returns errors if needed
     * 
     * @param HttpRequest $request
     * 
     * @return void
    */
    private function saveSettings(
        HttpRequest $request
    ): void
    {
        $configRoutesFilePath = trim($request->getPost("configRoutesFilePath") ?? "");
        $configDIFilePath     = trim($request->getPost("configDIFilePath"));


        /**
         * Save routes config path
        */
        if(!$this->pathResolver->isPhpFile($configRoutesFilePath))
            $this->errors[] = Loc::getMessage("OZ_ROUTER_SETTINGS_ROUTE_ERROR_CONFIG_PATH_INVALID");
        else
            $this->cfg->setConfigRoutesFilePath($configRoutesFilePath);


        /**
         * Save DI config path
        */
        if(!empty($configDIFilePath))
        {
            if(!$this->pathResolver->isPhpFile($configDIFilePath))
                $this->errors[] = Loc::getMessage("OZ_ROUTER_SETTINGS_ROUTE_ERROR_DI_PATH_INVALID");
            else
                $this->cfg->setConfigDIFilePath($configDIFilePath);
        }

    }
}
