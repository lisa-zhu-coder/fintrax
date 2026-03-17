<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'rrhh_mail_from_address')) {
                $table->string('rrhh_mail_from_address')->nullable()->after('daily_close_vouchers_enabled');
            }
            if (!Schema::hasColumn('companies', 'rrhh_mail_from_name')) {
                $table->string('rrhh_mail_from_name')->nullable()->after('rrhh_mail_from_address');
            }
            if (!Schema::hasColumn('companies', 'rrhh_mail_smtp_host')) {
                $table->string('rrhh_mail_smtp_host')->nullable()->after('rrhh_mail_from_name');
            }
            if (!Schema::hasColumn('companies', 'rrhh_mail_smtp_port')) {
                $table->unsignedInteger('rrhh_mail_smtp_port')->nullable()->after('rrhh_mail_smtp_host');
            }
            if (!Schema::hasColumn('companies', 'rrhh_mail_smtp_username')) {
                $table->string('rrhh_mail_smtp_username')->nullable()->after('rrhh_mail_smtp_port');
            }
            if (!Schema::hasColumn('companies', 'rrhh_mail_smtp_password')) {
                $table->string('rrhh_mail_smtp_password')->nullable()->after('rrhh_mail_smtp_username');
            }
            if (!Schema::hasColumn('companies', 'rrhh_mail_encryption')) {
                $table->string('rrhh_mail_encryption')->nullable()->after('rrhh_mail_smtp_password'); // tls / ssl
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $cols = [
                'rrhh_mail_from_address', 'rrhh_mail_from_name', 'rrhh_mail_smtp_host',
                'rrhh_mail_smtp_port', 'rrhh_mail_smtp_username', 'rrhh_mail_smtp_password',
                'rrhh_mail_encryption',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
