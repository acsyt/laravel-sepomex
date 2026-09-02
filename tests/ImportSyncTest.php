<?php

use Wafto\Sepomex\Models\Sepomex;

it('keeps ids and keys on a second identical import', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    $first = Sepomex::query()->orderBy('settlement_key')->get(['id', 'settlement_key', 'd_asenta', 'is_active']);

    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    $second = Sepomex::query()->orderBy('settlement_key')->get(['id', 'settlement_key', 'd_asenta', 'is_active']);

    expect($second->count())->toBe($first->count())
        ->and($second->pluck('id')->all())->toBe($first->pluck('id')->all())
        ->and($second->pluck('settlement_key')->all())->toBe($first->pluck('settlement_key')->all())
        ->and($second->pluck('is_active')->all())->toBe($first->pluck('is_active')->all());
});

it('inserts updates and deactivates across sync fixtures', function () {
    config(['sepomex.source_file' => __DIR__.'/files/sync_base.txt']);
    $this->artisan('sepomex:import')->assertExitCode(0);

    $keptId = Sepomex::query()->where('settlement_key', '09-016-1838')->value('id');
    expect($keptId)->not->toBeNull()
        ->and(Sepomex::query()->where('settlement_key', '09-010-0001')->value('is_active'))->toBeTrue();

    config(['sepomex.source_file' => __DIR__.'/files/sync_changed.txt']);
    $this->artisan('sepomex:import')->assertExitCode(0);

    $updated = Sepomex::query()->where('settlement_key', '09-016-1838')->first();
    expect($updated->id)->toBe($keptId)
        ->and($updated->is_active)->toBeTrue()
        ->and($updated->d_asenta)->toBe('Anzures Norte');

    expect(Sepomex::query()->where('settlement_key', '09-016-2785')->value('is_active'))->toBeTrue()
        ->and(Sepomex::query()->where('settlement_key', '09-010-0001')->value('is_active'))->toBeFalse();
});
