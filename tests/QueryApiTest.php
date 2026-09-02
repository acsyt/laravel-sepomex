<?php

use Illuminate\Support\Facades\Cache;
use Wafto\Sepomex\Contracts\SepomexContract;
use Wafto\Sepomex\Models\Sepomex;

it('returns multiple settlements for a postal code', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);

    $rows = app(SepomexContract::class)->getByPostal('67118');

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toHaveKeys(['postal', 'settlement_key', 'active', 'state', 'city', 'district', 'location']);
});

it('returns an empty array for an unknown postal code', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);

    expect(app(SepomexContract::class)->getByPostal('00000'))->toBe([]);
});

it('finds a settlement by stable key including inactive rows', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);

    $sepomex = app(SepomexContract::class);
    $found = $sepomex->findSettlement('09-016-1838');

    expect($found)->not->toBeNull()
        ->and($found->getSettlementKey())->toBe('09-016-1838')
        ->and($sepomex->findSettlement('00-000-0000'))->toBeNull();

    Sepomex::query()->where('settlement_key', '09-016-1838')->update(['is_active' => false]);
    if (Cache::supportsTags())
        Cache::tags(['sepomex'])->flush();

    expect($sepomex->getByPostal('11590'))->toBe([])
        ->and($sepomex->findSettlement('09-016-1838')?->isActive())->toBeFalse();
});
