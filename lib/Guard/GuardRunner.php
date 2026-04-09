<?php
declare(strict_types=1);
namespace Oz\Router\Guard;

use Oz\Router\Http\Exception\ForbiddenHttpException;
use Oz\Router\Interface\CanActivateInterface;

final class GuardRunner
{
    public function run(
        array $guards,
        GuardContext $context
    ): void
    {
        foreach ($guards as $guard)
        {
            if (!$guard instanceof CanActivateInterface)
            {
                continue;
            }

            if ($guard->canActivate($context) !== true)
            {
                throw new ForbiddenHttpException(
                    message: sprintf(
                        'Access denied by guard %s.',
                        $guard::class
                    ),
                );
            }
        }
    }
}
