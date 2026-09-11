# Laravel Sepomex

Internal Laravel package for the Mexican postal catalog ([SEPOMEX](https://www.correosdemexico.gob.mx) / Correos de México). Forked independently from [`wafto/laravel-sepomex`](https://github.com/wafto/laravel-sepomex); this repository is **not** a GitHub fork and is **not** published on Packagist.

## Requirements

- PHP 8.3+
- Laravel 13

## Installation

Add the private VCS repository and require `^5.0`. Authentication stays in SSH or Composer `github-oauth` — never in `composer.json`.

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:acsyt/laravel-sepomex.git"
        }
    ],
    "require": {
        "acsyt/laravel-sepomex": "^5.0"
    }
}
```

```bash
composer update acsyt/laravel-sepomex
```

Package discovery registers `Wafto\Sepomex\SepomexServiceProvider` automatically.

## Setup

### 1. Publish configuration (optional)

```bash
php artisan vendor:publish --provider="Wafto\Sepomex\SepomexServiceProvider"
```

Customize `table_name` and `source_file` in `config/sepomex.php`. The default file is `storage/CPdescarga.txt`.

### 2. Sync the catalog

Dispatch `Wafto\Sepomex\Jobs\SyncSepomexCatalog`. It downloads the official TXT from [Correos de México](https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Exportar.aspx), validates headers and `min_import_rows`, skips the import when the SHA-256 matches the current file (unless `force: true`), then upserts via `sepomex:import`. Manual download is no longer the only path. **Do not** commit that dump.

```php
use Wafto\Sepomex\Jobs\SyncSepomexCatalog;

SyncSepomexCatalog::dispatch();
SyncSepomexCatalog::dispatchSync();
(new SyncSepomexCatalog(force: true))->handle(app(\Wafto\Sepomex\Contracts\CatalogDownloader::class));
```

Host schedule (elcaba):

```php
use Wafto\Sepomex\Jobs\SyncSepomexCatalog;

$schedule->job(new SyncSepomexCatalog)
    ->weeklyOn(0, '03:00')
    ->timezone('America/Mexico_City')
    ->withoutOverlapping();
```

### 3. Migrate

```bash
php artisan migrate
```

### 4. Import a local TXT

If `storage/CPdescarga.txt` (or `source_file`) is already on disk:

```bash
php artisan sepomex:import --chunk=50
```

The command upserts by `settlement_key`. It does not truncate. Rows missing from a **successful** import are marked `is_active = false` so historical references stay resolvable. You can still download the catalog by hand and save it as `storage/CPdescarga.txt` when you only want a local import.

## Settlement identity

Consumers must persist `settlement_key`, **not** the autoincrement `id`.

```text
{c_estado:02}-{c_mnpio:03}-{id_asenta_cpcons:04}
```

Example: `09-016-1838`. The key is derived from Correos fields that identify a settlement inside a municipality. Reimports keep the same key and the same internal `id`.

## Query API

Inject `Wafto\Sepomex\Contracts\SepomexContract` (or use the `Sepomex` facade).

| Method | Behavior |
|---|---|
| `getByPostal(string $postal)` | Active settlements for that postal code |
| `getStates()` | Distinct active states |
| `findSettlement(string $settlementKey)` | One settlement by key, including inactive rows; `null` if missing |

```php
use Wafto\Sepomex\Contracts\SepomexContract;

$settlements = $sepomex->getByPostal('11590');
$settlement = $sepomex->findSettlement($settlements[0]['settlement_key']);
```

`getByPostal` / `getStates` ignore inactive rows. `findSettlement` does not, so a stored key still loads after the catalog drops that settlement.

## Cache

Lookups are cached with tags when the cache store supports them (Redis, array, Memcached). File and database stores do not; the package then skips cache instead of calling `Cache::tags()`.

## Tests

```bash
composer test
composer pint
```

## License

Package code is MIT. The catalog is produced by Correos de México:

> *El Catálogo Nacional de Códigos Postales, es elaborado por Correos de México y se proporciona en forma gratuita para uso particular, no estando permitida su comercialización, total o parcial, ni su distribución a terceros bajo ningún concepto.*

The database has also been released under [LIBRE USO MX](https://datos.gob.mx/libreusomx).
