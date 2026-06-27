<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

trait RedirectsToBankConciliation
{
    /**
     * Redirige a la conciliación bancaria conservando los filtros aplicados.
     *
     * El formulario/acción envía el campo "redirect_to" con la URL completa
     * (incluida la query string de filtros). Se valida que apunte a la propia
     * vista de conciliación para evitar redirecciones abiertas.
     */
    protected function redirectToBankConciliation(): RedirectResponse
    {
        $fallback = route('financial.bank-conciliation');
        $target = request()->input('redirect_to');

        if (is_string($target) && $target !== '') {
            $path = parse_url($target, PHP_URL_PATH);
            $host = parse_url($target, PHP_URL_HOST);
            $expectedPath = route('financial.bank-conciliation', [], false);

            if ($path === $expectedPath && ($host === null || $host === request()->getHost())) {
                return redirect()->to($target);
            }
        }

        return redirect()->to($fallback);
    }
}
