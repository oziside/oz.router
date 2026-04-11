<?php
declare(strict_types=1);
namespace Oz\Router\Interface;

use Oz\Router\Guard\GuardContext;


interface CanActivateInterface
{
    /**
     * Предоставляет доступ к деталям 
     * текущего контекста обработки запроса.
     * 
     * @param GuardContext $context - текущий контекст выполнения.
     *
     * @return bool - разрешено ли продолжение текущего запроса.
    */
    public function canActivate(GuardContext $context): bool;
}
