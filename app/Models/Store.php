<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes, BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'name',
        'slug',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Usuarios que tienen acceso a esta tienda (vía user_store, múltiples tiendas).
     */
    public function usersWithAccess(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_store');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_store');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function ringInventories(): HasMany
    {
        return $this->hasMany(RingInventory::class);
    }

    public function monthlyObjectiveSettings(): HasMany
    {
        return $this->hasMany(MonthlyObjectiveSetting::class);
    }

    public function objectiveDailyRows(): HasMany
    {
        return $this->hasMany(ObjectiveDailyRow::class);
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class);
    }

    public function customerRepairs(): HasMany
    {
        return $this->hasMany(CustomerRepair::class);
    }
}
