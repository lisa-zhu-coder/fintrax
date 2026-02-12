<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes, BelongsToCompany;
    
    protected $fillable = [
        'company_id',
        'full_name',
        'dni',
        'phone',
        'email',
        'street',
        'postal_code',
        'city',
        'position',
        'hours',
        'start_date',
        'end_date',
        'social_security',
        'iban',
        'gross_salary',
        'net_salary',
        'shirt_size',
        'blazer_size',
        'pants_size',
        'user_id',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    /**
     * Evitar error "Unable to cast value to a decimal" cuando hours/gross_salary/net_salary
     * vienen vacíos o no numéricos de la base de datos.
     */
    protected function castAttribute($key, $value)
    {
        if (in_array($key, ['hours', 'gross_salary', 'net_salary'], true)) {
            if ($value === '' || $value === null) {
                return null;
            }
            if (is_string($value) && !is_numeric($value)) {
                return null;
            }
        }
        return parent::castAttribute($key, $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'employee_store');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class);
    }

    public function overtimeSetting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OvertimeSetting::class);
    }

    public function vacationPeriods(): HasMany
    {
        return $this->hasMany(EmployeeVacationPeriod::class);
    }

    public function vacationDays(): HasMany
    {
        return $this->hasMany(EmployeeVacationDay::class);
    }
}
