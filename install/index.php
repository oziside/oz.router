<?
use Bitrix\{
    Main,
    Main\Localization\Loc  
};


Loc::loadMessages(__FILE__);

class oz_router extends CModule
{
    public $MODULE_ID = 'oz.router';

    public $MODULE_NAME;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_DESCRIPTION;

    public $PARTNER_NAME;
    public $PARTNER_URI;


    public function __construct()
    {
        $arModuleVersion = [];

        include(__DIR__ . "/version.php");

        //version info
        $this->MODULE_VERSION      = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        //module info
        $this->MODULE_NAME        = Loc::getMessage('MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('MODULE_DESCRIPTION');
        
        //partner info
        $this->PARTNER_NAME       = Loc::getMessage('PARTNER_NAME');
        $this->PARTNER_URI        = Loc::getMessage('PARTNER_URI');
    }

    public function InstallDB(): bool
    {
		return true;
    }

    public function UnInstallDB(): bool
    {
		return true;
    }

    public function InstallEvents(): bool
    {
        return true;
    }

    public function UnInstallEvents(): bool
    {
        return true;
    }

    public function InstallFiles(): bool
    {
        // to bitrix
        CopyDirFiles(__DIR__ . "/admin/",      $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
		CopyDirFiles(__DIR__ . "/services/",   $_SERVER["DOCUMENT_ROOT"]."/bitrix/services", true, true);
        // to local
        CopyDirFiles(__DIR__ . "/components/", $_SERVER["DOCUMENT_ROOT"]."/local/components", true, true);
		CopyDirFiles(__DIR__ . "/js/",   $_SERVER["DOCUMENT_ROOT"]."/local/js", true, true);

        return true;
    }

    public function UnInstallFiles(): bool
    {
        return true;
    }

    public function DoInstall(): void
    {
        global $USER;

        if ($USER->IsAdmin())
        {
            if (!Main\ModuleManager::isModuleInstalled($this->MODULE_ID))
            {
                $this->InstallDB();
                $this->InstallEvents();
                $this->InstallFiles();

                Main\ModuleManager::registerModule($this->MODULE_ID);
            }
        }
    }


    /**
     * Метод деинсталяции модуля
     *
     * @return void
    */
    public function DoUninstall(): void
    {
        global $APPLICATION, $USER, $step;

        $step = (int)$step;
        
        if ($step < 2)
        {
            $APPLICATION->IncludeAdminFile('', __DIR__ . '/unstep1.php');
        }
        elseif ($step === 2)
        {
            if ($USER->IsAdmin())
            {
                Main\ModuleManager::unRegisterModule($this->MODULE_ID);

                $request = Main\Application::getInstance()->getContext()->getRequest();
                
                // Откатываем миграции и данные базы
                if($request->get('unistallDB') === 'Y')
                    $this->UnInstallDB();
                // Удаляем события
                if($request->get('deleteEvents') === 'Y')
                    $this->UnInstallEvents();
                //Удаляем файлы
                if($request->get('deleteFiles') === 'Y')
                    $this->UnInstallFiles();
            }
        }
    }
}
