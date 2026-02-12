<?php

namespace Tests\Feature;

use App\Models\FinancialEntry;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::factory()->create(['key' => 'admin']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_user_can_view_financial_entries(): void
    {
        $store = Store::factory()->create();
        FinancialEntry::factory()->count(5)->create(['store_id' => $store->id]);

        $response = $this->actingAs($this->user)->get('/financial');

        $response->assertStatus(200);
        $response->assertSee('Registros Financieros');
    }

    public function test_user_can_create_financial_entry(): void
    {
        $store = Store::factory()->create();

        $response = $this->actingAs($this->user)->post('/financial', [
            'date' => now()->format('Y-m-d'),
            'store_id' => $store->id,
            'type' => 'expense',
            'amount' => 100.50,
            'concept' => 'Test expense',
        ]);

        $response->assertRedirect('/financial');
        $this->assertDatabaseHas('financial_entries', [
            'store_id' => $store->id,
            'type' => 'expense',
            'amount' => 100.50,
        ]);
    }

    public function test_user_cannot_create_entry_without_permission(): void
    {
        $role = Role::factory()->create([
            'key' => 'visor',
            'permissions' => [
                'view' => true,
                'create' => false,
            ],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $store = Store::factory()->create();

        $response = $this->actingAs($user)->post('/financial', [
            'date' => now()->format('Y-m-d'),
            'store_id' => $store->id,
            'type' => 'expense',
            'amount' => 100.50,
        ]);

        $response->assertStatus(403);
    }
}
