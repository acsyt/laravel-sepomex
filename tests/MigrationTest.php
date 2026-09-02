<?php

use Illuminate\Support\Facades\Schema;

it('creates the sepomex table with identity columns', function () {
    $table = config('sepomex.table_name');

    expect(Schema::hasTable($table))->toBeTrue()
        ->and(Schema::hasColumns($table, [
            'id',
            'settlement_key',
            'sync_token',
            'is_active',
            'd_codigo',
            'c_estado',
            'c_mnpio',
            'id_asenta_cpcons',
        ]))->toBeTrue();
});

it('rolls back the sepomex table', function () {
    $this->artisan('migrate:rollback', ['--database' => 'testing']);

    expect(Schema::hasTable(config('sepomex.table_name')))->toBeFalse();
});
