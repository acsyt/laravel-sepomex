<?php

namespace Wafto\Sepomex\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;
use Wafto\Sepomex\Contracts\CatalogDownloader;
use Wafto\Sepomex\Support\DelimitedFileIterator;

final class SyncSepomexCatalog implements ShouldBeUnique, ShouldQueue {
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly int $chunk = 50,
        public readonly bool $force = false,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string {
        return 'sepomex-catalog-sync';
    }

    public function uniqueFor(): int {
        return 600;
    }

    public function handle(CatalogDownloader $downloader): void {
        $permanent = (string) config('sepomex.source_file');
        $tmp = $permanent . '.tmp';
        $chunk = max(1, $this->chunk > 0 ? $this->chunk : (int) config('sepomex.chunk', 50));

        try {
            $downloader->downloadTo($tmp);
            $this->assertMinimumRows($tmp);

            if (
                !$this->force &&
                is_file($permanent) &&
                hash_equals(hash_file('sha256', $permanent), hash_file('sha256', $tmp))
            ) return;

            $original = $permanent;
            config(['sepomex.source_file' => $tmp]);
            $exit = Artisan::call('sepomex:import', ['--chunk' => $chunk]);
            config(['sepomex.source_file' => $original]);

            if ($exit !== 0) throw new RuntimeException(Artisan::output());

            File::copy($tmp, $original);
        } finally {
            if (is_file($tmp)) File::delete($tmp);
        }
    }

    private function assertMinimumRows(string $path): void {
        $iterator = new DelimitedFileIterator(
            $path,
            '|',
            config('sepomex.encoding_input'),
            config('sepomex.encoding_output'),
            1
        );
        $iterator->readHeaders();
        $rows = max(0, $iterator->countLines() - 1);
        $min = (int) config('sepomex.min_import_rows', 100000);

        if ($rows < $min) throw new InvalidArgumentException('SEPOMEX source file has too few rows.');
    }
}
