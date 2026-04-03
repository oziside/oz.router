<?
declare(strict_types=1);
namespace Oz\Router\Module\Service;


use Oz\Router\Module\{
    Module
};


final class PathResolver
{
    /**
     * Возвращает абсолютный путь к файлу/папке или null, 
     * если файл/папка не найдены
     * 
     * @param string $path
     * @param bool $checkExist
     * 
     * @return ?string
    */
    public function resolve(
        string $path,
        bool $checkExist = true
    ): ?string
    {
        if(empty($path))
        {
            return null;
        }

        $documentRoot = Module::getApplication()->getDocumentRoot();

        $rootRemoved  = $this->removeDocumentRoot($path);
        $preparedPath = Rel2Abs("/", trim($rootRemoved, '/'));
        
        if($preparedPath)
        {
            $fullPath = $documentRoot . $preparedPath;

            if(!$checkExist)
                return $fullPath;

            if (file_exists($fullPath))
                return $fullPath;
        }

        return null;
    }


    /**
     * Проверяет, что путь указывает на существующий php-файл
     * 
     * @param string $path
     * 
     * @return bool
    */
    public function isPhpFile(string $path): bool
    {
        $resolvedPath = $this->resolve($path);

        if($resolvedPath === null || !is_file($resolvedPath))
        {
            return false;
        }

        return mb_strtolower((string)pathinfo($resolvedPath, PATHINFO_EXTENSION)) === "php";
    }


    /**
     * Возвращает строку, гарантированно 
     * начинающуюся и заканчивающуюся на слеш
     * 
     * @param string $value
     * 
     * @return string
    */
    public function ensureSlashes(string $value): string
    {
        return '/' . trim($value, '/') . '/';
    }


    /**
     * Возвращает путь с удаленным DocumentRoot,
     * 
     * @param string $path
     * 
     * @return string
    */
    public function removeDocumentRoot(string $path): string
    {
        $originPath = $this->ensureSlashes($path);

        return removeDocRoot($originPath);
    }
}
