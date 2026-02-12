# Miramira · Dashboard financiero

## Ver el proyecto (Laravel)

Para **ver el dashboard Laravel** en el navegador y decidir si añadir más cosas:

```bash
cd /Users/lisazhu/miramira-dashboard
./ver-proyecto.sh
```

Abre **http://127.0.0.1:8000**, inicia sesión con **admin** / **admin123** y explora:
- **Dashboard** (gráficas, resumen)
- **Registros** → Añadir → **Cierre diario** (conteo, Shopify POS, etc.)
- **Pedidos**, **Empleados**, **Usuarios**, **Empresa**

Requisitos: PHP 8.1+ y Composer. Si usas MySQL, ver `INSTALACION.md`.

---

Dashboard interno para 4 tiendas con:
- Filtro por **tienda** (o **empresa** = suma de todas)
- Filtro por **periodo**
- **Gráficas** (ventas vs gastos + distribución)
- **Tabla** de registros recientes
- **Carga de datos diarios** desde la propia web (se guardan en `localStorage`)
- **Tipos de registro**: Cierre diario, Gasto, Ingreso, Devolución de gasto
- **Cierre de caja diario** con conteo de monedas/billetes, Shopify POS, conciliación
- Exportación a **CSV**

## Tiendas incluidas
- Miramira - Luz del Tajo
- Miramira - Maquinista
- Miramira - Puerto Venecia
- Miramira - Xanadu

## Cómo abrirlo

### Opción A (rápida)
Abre `index.html` en el navegador (Chrome/Safari/Edge).

### Opción B (recomendado: servidor local)
Desde una terminal:

```bash
cd "/Users/lisazhu"
ruby -run -e httpd "miramira-dashboard" -p 5173
```

Luego abre en el navegador:
- `http://localhost:5173`

## Datos
- Se guardan en este navegador: `localStorage` (clave `miramira_financial_entries_v1`).
- Si no hay datos, al abrir por primera vez se carga un **demo** automático.

### Reset (borrar todos los datos)
En el navegador, abre la consola y ejecuta:

```js
localStorage.removeItem("miramira_financial_entries_v1")
```
