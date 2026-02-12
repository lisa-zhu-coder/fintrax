<?php

namespace Database\Factories;

use App\Models\FinancialEntry;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialEntryFactory extends Factory
{
    protected $model = FinancialEntry::class;

    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['daily_close', 'expense', 'income', 'expense_refund']),
            'concept' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
