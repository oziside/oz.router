<?php
declare(strict_types=1);
namespace Oz\Router\Module;


use Bitrix\Main\{
    Application
};

class Module
{
    private const MODULE_ID = 'oz.router';

    /**
     * Возвращает идентификатор модуля
     * 
     * @return string
    */
    public static function getId(): string
    {
        return self::MODULE_ID;
    }

    /**
     * Возвращает инстанс Bitrix приложения
     * 
     * @return Application
    */
    public static function getApplication(): Application
    {
        return Application::getInstance();
    }

    /**
     * Возвращает объект конфигурации модуля
     * 
     * @return Config
    */
    public static function getConfig(): Config
    {
        return new Config;
    }
}