<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $superAdminRoleId = DB::table('roles')->where('key', 'super_admin')->value('id');
        if ($superAdminRoleId === null) {
            return;
        }

        $users = DB::table('users')
            ->whereNotNull('company_id')
            ->where('role_id', '!=', $superAdminRoleId)
            ->get(['id', 'company_id', 'role_id', 'store_id']);

        foreach ($users as $user) {
            DB::table('company_user')->insertOrIgnore([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role_id' => $user->role_id,
                'store_id' => $user->store_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No eliminar datos en down; la tabla se borra en la migración anterior
    }
};
