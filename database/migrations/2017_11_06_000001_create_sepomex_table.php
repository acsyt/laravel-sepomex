<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create(config('sepomex.table_name'), function (Blueprint $table) {
            $table->id();
            $table->string('settlement_key', 16)->unique();
            $table->string('sync_token', 36)->index();
            $table->boolean('is_active')->default(true);
            $table->string('d_codigo', 8);
            $table->string('d_asenta')->nullable();
            $table->string('d_tipo_asenta', 100)->nullable();
            $table->string('D_mnpio')->nullable();
            $table->string('d_estado', 100)->nullable();
            $table->string('d_ciudad')->nullable();
            $table->string('d_CP', 8)->nullable();
            $table->string('c_estado', 2);
            $table->string('c_oficina', 8)->nullable();
            $table->string('c_CP', 8)->nullable();
            $table->string('c_tipo_asenta', 8)->nullable();
            $table->string('c_mnpio', 3);
            $table->string('id_asenta_cpcons', 4);
            $table->string('d_zona', 100)->nullable();
            $table->string('c_cve_ciudad', 8)->nullable();
            $table->unique(['c_estado', 'c_mnpio', 'id_asenta_cpcons'], 'sepomex_settlement_identity_unique');
            $table->index(['d_codigo', 'is_active']);
            $table->index(['c_estado', 'is_active']);
        });
    }

    public function down(): void {
        Schema::drop(config('sepomex.table_name'));
    }
};
