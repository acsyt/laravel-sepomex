<?php

use Wafto\Sepomex\Support\SettlementKey;

it('builds a zero-padded deterministic key', function () {
    expect(SettlementKey::fromCodes('09', '010', '0001'))->toBe('09-010-0001')
        ->and(SettlementKey::fromCodes(9, 10, 1))->toBe('09-010-0001')
        ->and(SettlementKey::fromCodes('19', '026', '1838'))->toBe('19-026-1838');
});

it('rejects missing identity parts', function () {
    SettlementKey::fromCodes('', '010', '0001');
})->throws(InvalidArgumentException::class);

it('rejects non-numeric identity parts', function () {
    SettlementKey::fromCodes('09x', '010', '0001');
})->throws(InvalidArgumentException::class);

it('rejects out-of-range identity parts', function () {
    SettlementKey::fromCodes('99', '010', '0001');
})->throws(InvalidArgumentException::class);
