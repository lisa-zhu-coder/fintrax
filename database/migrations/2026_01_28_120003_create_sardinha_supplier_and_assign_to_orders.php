<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear proveedor Sardinha de Artesanato S.L.
        $sardinhaId = DB::table('suppliers')->insertGetId([
            'name' => 'Sardinha de Artesanato S.L.',
            'cif' => null,
            'address' => null,
            'email' => null,
            'phone' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asignar Sardinha a todos los pedidos existentes
        DB::table('orders')->update(['supplier_id' => $sardinhaId]);

        // Asignar Sardinha a gastos que provienen de pedidos (order_id en notes)
        $entries = DB::table('financial_entries')
            ->where('type', 'expense')
            ->whereNotNull('notes')
            ->get();

        foreach ($entries as $entry) {
            $notes = json_decode($entry->notes, true);
            if (is_array($notes) && isset($notes['order_id'])) {
                $order = DB::table('orders')->find($notes['order_id']);
                if ($order && $order->supplier_id) {
                    DB::table('financial_entries')
                        ->where('id', $entry->id)
                        ->update(['supplier_id' => $order->supplier_id]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('orders')->update(['supplier_id' => null]);
        DB::table('financial_entries')->update(['supplier_id' => null]);
        DB::table('suppliers')->where('name', 'Sardinha de Artesanato S.L.')->delete();
    }
};
