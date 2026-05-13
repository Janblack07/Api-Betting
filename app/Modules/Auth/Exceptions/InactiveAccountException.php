<?php

namespace App\Modules\Auth\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class InactiveAccountException extends HttpException
{
    public function __construct(string $message = 'Tu cuenta está bloqueada o suspendida. Contacta soporte.')
    {
        parent::__construct(403, $message);
    }
}
