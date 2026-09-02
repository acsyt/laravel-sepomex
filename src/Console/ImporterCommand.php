<?php

namespace Wafto\Sepomex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Wafto\Sepomex\Models\Sepomex;
use Wafto\Sepomex\Support\DelimitedFileIterator;
use Wafto\Sepomex\Support\SepomexCache;
use Wafto\Sepomex\Support\SettlementKey;

class ImporterCommand extends Command {
    protected $signature = 'sepomex:import {--chunk=50}';

    protected $description = 'Imports sepomex data from the txt file provided by "Correos de México".';

    private const EXPECTED_HEADERS = [
        'd_codigo',
        'd_asenta',
        'd_tipo_asenta',
        'D_mnpio',
        'd_estado',
        'd_ciudad',
        'd_CP',
        'c_estado',
        'c_oficina',
        'c_CP',
        'c_tipo_asenta',
        'c_mnpio',
        'id_asenta_cpcons',
        'd_zona',
        'c_cve_ciudad',
    ];

    private const UPSERT_COLUMNS = [
        'd_codigo',
        'd_asenta',
        'd_tipo_asenta',
        'D_mnpio',
        'd_estado',
        'd_ciudad',
        'd_CP',
        'c_estado',
        'c_oficina',
        'c_CP',
        'c_tipo_asenta',
        'c_mnpio',
        'id_asenta_cpcons',
        'd_zona',
        'c_cve_ciudad',
        'sync_token',
        'is_active',
    ];

    public function handle(Sepomex $model): int {
        try {
            $iterator = new DelimitedFileIterator(
                config('sepomex.source_file'),
                '|',
                config('sepomex.encoding_input'),
                config('sepomex.encoding_output'),
                1
            );
            $iterator->readHeaders();
            $this->assertHeaders($iterator->getHeaders() ?? []);

            $syncToken = (string) Str::uuid();
            $chunkSize = max(1, (int) $this->option('chunk'));
            $lines = max(0, $iterator->countLines() - 1);
            $this->comment(sprintf('Parsing [%s] rows from file...', $lines));

            $imported = $this->importRows($model, $iterator, $syncToken, $chunkSize, $lines);
            $this->deactivateMissing($model, $syncToken);
            (new SepomexCache(Cache::driver()))->flush();

            $this->info(sprintf(
                "Imported [%s] rows from [%s] file lines in %s table.\n",
                $imported,
                $lines,
                $model->getTable()
            ));
            $this->info('Cache cleared.');

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 1;
        }
    }

    private function importRows(
        Sepomex $model,
        DelimitedFileIterator $iterator,
        string $syncToken,
        int $chunkSize,
        int $lines
    ): int {
        $bar = $this->output->createProgressBar($lines);
        $accumulator = [];
        $seen = [];
        $imported = 0;

        foreach ($iterator as $row) {
            $payload = $this->mapRow($row, $syncToken, $seen);
            $accumulator[] = $payload;

            if (count($accumulator) >= $chunkSize) {
                $imported += $this->upsertChunk($model, $accumulator);
                $bar->advance(count($accumulator));
                $accumulator = [];
            }
        }

        if (count($accumulator) > 0) {
            $imported += $this->upsertChunk($model, $accumulator);
            $bar->advance(count($accumulator));
        }

        $bar->finish();

        return $imported;
    }

    private function mapRow(mixed $row, string $syncToken, array &$seen): array {
        if (! is_array($row) || array_is_list($row))
            throw new InvalidArgumentException('SEPOMEX source row is malformed.');

        foreach (self::EXPECTED_HEADERS as $header) {
            if (! array_key_exists($header, $row))
                throw new InvalidArgumentException('SEPOMEX source row is missing required columns.');
        }

        $key = SettlementKey::fromRow($row);
        if (isset($seen[$key]))
            throw new InvalidArgumentException(sprintf('Duplicate SEPOMEX settlement identity [%s].', $key));

        $seen[$key] = true;
        [$estado, $municipio, $asenta] = explode('-', $key);

        return [
            'settlement_key'    => $key,
            'sync_token'        => $syncToken,
            'is_active'         => true,
            'd_codigo'          => $row['d_codigo'],
            'd_asenta'          => $row['d_asenta'],
            'd_tipo_asenta'     => $row['d_tipo_asenta'],
            'D_mnpio'           => $row['D_mnpio'],
            'd_estado'          => $row['d_estado'],
            'd_ciudad'          => $row['d_ciudad'],
            'd_CP'              => $row['d_CP'],
            'c_estado'          => $estado,
            'c_oficina'         => $row['c_oficina'],
            'c_CP'              => $row['c_CP'],
            'c_tipo_asenta'     => $row['c_tipo_asenta'],
            'c_mnpio'           => $municipio,
            'id_asenta_cpcons'  => $asenta,
            'd_zona'            => $row['d_zona'],
            'c_cve_ciudad'      => $row['c_cve_ciudad'],
        ];
    }

    private function upsertChunk(Sepomex $model, array $rows): int {
        DB::transaction(function () use ($model, $rows) {
            $model->upsert($rows, ['settlement_key'], self::UPSERT_COLUMNS);
        });

        return count($rows);
    }

    private function deactivateMissing(Sepomex $model, string $syncToken): void {
        $model->newQuery()
            ->where('sync_token', '!=', $syncToken)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function assertHeaders(array $headers): void {
        if ($headers !== self::EXPECTED_HEADERS)
            throw new InvalidArgumentException('SEPOMEX source file has an invalid header.');
    }
}
