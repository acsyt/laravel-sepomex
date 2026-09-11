<?php

use Wafto\Sepomex\Contracts\CatalogDownloader;
use Wafto\Sepomex\Downloader\ForbiddenCatalogDownloader;
use Wafto\Sepomex\Jobs\SyncSepomexCatalog;
use Wafto\Sepomex\Models\Sepomex;
use Wafto\Sepomex\Tests\FakeCatalogDownloader;

function copySepomexFixture(string $name): string {
    $path = sys_get_temp_dir() . '/sepomex-' . uniqid('', true) . '.txt';
    copy(__DIR__ . '/files/' . $name, $path);

    return $path;
}

beforeEach(function () {
    $this->permanent = copySepomexFixture('test.txt');
    config(['sepomex.source_file' => $this->permanent]);
});

afterEach(function () {
    foreach ([$this->permanent ?? null, ($this->permanent ?? '') . '.tmp'] as $path) {
        if (is_string($path) && is_file($path)) unlink($path);
    }
});

it('skips import when the downloaded file has the same hash', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    $token = Sepomex::query()->value('sync_token');
    $count = Sepomex::query()->count();

    app()->instance(CatalogDownloader::class, new FakeCatalogDownloader(__DIR__ . '/files/test.txt'));

    (new SyncSepomexCatalog)->handle(app(CatalogDownloader::class));

    expect(Sepomex::query()->value('sync_token'))->toBe($token)
        ->and(Sepomex::query()->count())->toBe($count);
});

it('imports a changed catalog', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    config(['sepomex.min_import_rows' => 1]);

    app()->instance(CatalogDownloader::class, new FakeCatalogDownloader(__DIR__ . '/files/sync_changed.txt'));

    (new SyncSepomexCatalog)->handle(app(CatalogDownloader::class));

    expect(Sepomex::query()->where('settlement_key', '09-016-1838')->value('d_asenta'))
        ->toBe('Anzures Norte');
});

it('keeps the catalog when the download has an invalid header', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    $count = Sepomex::query()->count();
    $hash = hash_file('sha256', $this->permanent);
    config(['sepomex.min_import_rows' => 1]);

    app()->instance(CatalogDownloader::class, new FakeCatalogDownloader(__DIR__ . '/files/invalid_header.txt'));

    expect(fn() => (new SyncSepomexCatalog)->handle(app(CatalogDownloader::class)))
        ->toThrow(RuntimeException::class);

    expect(Sepomex::query()->count())->toBe($count)
        ->and($count)->toBeGreaterThan(0)
        ->and(hash_file('sha256', $this->permanent))->toBe($hash);
});

it('keeps the catalog when the download has too few rows', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    $count = Sepomex::query()->count();
    $hash = hash_file('sha256', $this->permanent);
    config(['sepomex.min_import_rows' => 10000]);

    app()->instance(CatalogDownloader::class, new FakeCatalogDownloader(__DIR__ . '/files/test.txt'));

    expect(fn() => (new SyncSepomexCatalog)->handle(app(CatalogDownloader::class)))
        ->toThrow(InvalidArgumentException::class);

    expect(Sepomex::query()->count())->toBe($count)
        ->and(hash_file('sha256', $this->permanent))->toBe($hash);
});

it('keeps the catalog when the downloader fails', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    $count = Sepomex::query()->count();
    $hash = hash_file('sha256', $this->permanent);

    app()->instance(CatalogDownloader::class, new ForbiddenCatalogDownloader);

    expect(fn() => (new SyncSepomexCatalog)->handle(app(CatalogDownloader::class)))
        ->toThrow(RuntimeException::class);

    expect(Sepomex::query()->count())->toBe($count)
        ->and(hash_file('sha256', $this->permanent))->toBe($hash);
});

it('imports when force is true even if the hash matches', function () {
    $this->artisan('sepomex:import', ['--chunk' => 50])->assertExitCode(0);
    $token = Sepomex::query()->value('sync_token');

    app()->instance(CatalogDownloader::class, new FakeCatalogDownloader(__DIR__ . '/files/test.txt'));

    (new SyncSepomexCatalog(force: true))->handle(app(CatalogDownloader::class));

    expect(Sepomex::query()->value('sync_token'))->not->toBe($token);
});
