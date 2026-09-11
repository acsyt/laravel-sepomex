<?php

namespace Wafto\Sepomex\Downloader;

use RuntimeException;
use Wafto\Sepomex\Contracts\CatalogDownloader;

final class ForbiddenCatalogDownloader implements CatalogDownloader {
    public function downloadTo(string $destination): void {
        throw new RuntimeException('SEPOMEX catalog download is forbidden in testing.');
    }
}
