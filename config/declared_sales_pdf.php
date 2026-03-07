<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Colores del PDF "Registro de ventas"
    |--------------------------------------------------------------------------
    | Ajusta estos valores para que coincidan con tu plantilla.
    |
    | Puedes usar:
    |   - Array RGB: [R, G, B] con valores 0-255. Ej: [55, 71, 79]
    |   - Hex: '#RRGGBB'. Ej: '#37474F' (barra) o '#7B2D8E' (título/cajas)
    |
    | Para sacar el color de tu foto: usa un cuentagotas (color picker) en
    | la imagen y copia el valor hex o los R, G, B.
    |
    | - bar: barra superior (nombre de la tienda). Ej. gris oscuro #37474F
    | - accent: título, cajas Total con/sin IVA, cabecera y fila TOTAL.
    */
    'colors' => [
        'bar' => '#7f7f7f',   // Fondo de la fila con el nombre de la tienda
        'accent' => '#e84993', // Letras "REGISTRO VENTAS MES AÑO" y fondo de Total con IVA / Total sin IVA (y cabecera/total tabla)
    ],
];
