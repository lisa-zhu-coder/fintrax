<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Rutas excluidas de verificación CSRF.
     * logout: evita 419 "Page Expired" cuando la sesión ha caducado o el token está desactualizado.
     */
    protected $except = [
        'logout',
    ];
}
