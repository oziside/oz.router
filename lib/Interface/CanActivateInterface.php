<?php
declare(strict_types=1);
namespace Oz\Router\Interface;

use Bitrix\Main\{
    HttpContext
};


interface CanActivateInterface
{
    /**
     * Предоставляет доступ к деталям 
     * текущего контекста обработки запроса.
     * 
     * @param HttpContext $ctx - текущий контекст выполнения. 
     *
     * @return bool - разрешено ли продолжение текущего запроса.
    */
    public function canActivate(HttpContext $ctx): bool;
}