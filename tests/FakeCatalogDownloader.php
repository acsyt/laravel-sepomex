<?php

namespace Wafto\Sepomex\Tests;

use RuntimeException;
use Wafto\Sepomex\Contracts\CatalogDownloader;

final class FakeCatalogDownloader implements CatalogDownloader {
    public function __construct(
        private readonly string $source,
    ) {}

    public function downloadTo(string $destination): void {
        if (!copy($this->source, $destination))
            throw new RuntimeException('Unable to copy SEPOMEX fixture.');
    }
}
