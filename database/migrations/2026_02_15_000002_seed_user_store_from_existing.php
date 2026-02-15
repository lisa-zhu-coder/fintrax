<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $seen = [];
        $unique = [];
        foreach (DB::table('users')->whereNotNull('store_id')->get() as $user) {
            $k = $user->id . '-' . $user->store_id;
            if (empty($seen[$k])) {
                $seen[$k] = true;
                $unique[] = [
                    'user_id' => $user->id,
                    'store_id' => $user->store_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        foreach (DB::table('company_user')->whereNotNull('store_id')->get() as $cu) {
            $k = $cu->user_id . '-' . $cu->store_id;
            if (empty($seen[$k])) {
                $seen[$k] = true;
                $unique[] = [
                    'user_id' => $cu->user_id,
                    'store_id' => $cu->store_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        if (!empty($unique)) {
            DB::table('user_store')->insert($unique);
        }
    }

    public function down(): void
    {
        DB::table('user_store')->truncate();
    }
};
