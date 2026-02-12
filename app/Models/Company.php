<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;
    
    protected $table = 'companies';

    protected $fillable = [
        'name',
        'cif',
        'address',
        'email',
        'phone',
        // Campos para futuro (planes, límites)
        'plan',
        'max_users',
        'max_stores',
        'rings_inventory_enabled',
        'clients_module_enabled',
    ];

    protected $casts = [
        'rings_inventory_enabled' => 'boolean',
        'clients_module_enabled' => 'boolean',
    ];

    /**
     * Tiendas de esta empresa.
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Usuarios de esta empresa.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Empleados de esta empresa.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Pedidos de esta empresa.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Registros financieros de esta empresa.
     */
    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }

    /**
     * Facturas de esta empresa.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Carteras de efectivo de esta empresa.
     */
    public function cashWallets(): HasMany
    {
        return $this->hasMany(CashWallet::class);
    }

    /**
     * Proveedores de esta empresa.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Traspasos de esta empresa.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }

    /**
     * Permisos personalizados por rol para esta empresa.
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(CompanyRolePermission::class, 'company_id');
    }

    /**
     * Pedidos de clientes de esta empresa.
     */
    public function customerOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    /**
     * Reparaciones de clientes de esta empresa.
     */
    public function customerRepairs(): HasMany
    {
        return $this->hasMany(CustomerRepair::class);
    }
}
