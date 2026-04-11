<?
declare(strict_types=1);
namespace Oz\Router\Module;

use Bitrix\Main\Config\Option;


final class Config
{
    private const PATH_ROUTES_FILE    = "path_config_routes_file";
    private const PATH_DI_CONFIG_FILE = "path_config_di_file";


    /**
     * Устанавливает путь к файлу 
     * с описанием маршрутов проекта
     * 
     * @param string $path
     * 
     * @return void
    */
    public function setConfigRoutesFilePath(string $path): void
    {
        $this->set(self::PATH_ROUTES_FILE, $path);
    }


    /**
     * Возвращает путь к файлу 
     * с описанием маршрутов проекта
     * 
     * @return string
    */
    public function getConfigRoutesFilePath(): string
    {
        // TODO: дать возможность указать несколько файлов или папку с файлами маршрутов
        // а вообще, скорее всего надо разработать единый файл конфигурации, где будут 
        // описаны конфигурации для всего проекта
        return $this->get(self::PATH_ROUTES_FILE);
    }


    /**
     * Устанавливает путь к файлу 
     * с описанием конфигурации DI контейнера
     * 
     * @param string $path
     * 
     * @return void
    */
    public function setConfigDIFilePath(string $path): void
    {
        $this->set(self::PATH_DI_CONFIG_FILE, $path);
    }


    /**
     * Возвращает путь к файлу 
     * с описанием конфигурации DI контейнера
     * 
     * @return string
    */
    public function getConfigDIFilePath(): string
    {
        return $this->get(self::PATH_DI_CONFIG_FILE);
    }

    /**
     * Установка значения в настройки модуля
     * 
     * @param string $key
     * @param string $value
     * 
     * @return void
    */
    private function set(string $key, string $value): void
    {
        Option::set(Module::getId(), $key, $value);
    }


    /**
     * Возвращает значение из настроек модуля по ключу
     * 
     * @param string $key
     * @param string $default
     * 
     * @return string
    */
    private function get(string $key, string $default = ''): string
    {
        return Option::get(Module::getId(), $key, $default);
    }


    /**
     * Удаляет значение из настроек модуля по ключу
     * 
     * @param string $key
     * 
     * @return void
    */
    private function delete(string $key): void
    {
        Option::delete(Module::getId(), ['name' => $key]);
    }
}
