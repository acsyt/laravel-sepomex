<?php

namespace Wafto\Sepomex\Downloader;

use RuntimeException;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Wafto\Sepomex\Contracts\CatalogDownloader;
use ZipArchive;

final class CorreosCatalogDownloader implements CatalogDownloader {
    public function downloadTo(string $destination): void {
        $client = new HttpBrowser(HttpClient::create(['timeout' => 120]));
        $crawler = $client->request('GET', self::LINK);
        $form = $crawler->selectButton('btnDescarga')->form();
        $client->submit($form, ['rblTipo' => 'txt']);
        $response = $client->getResponse();

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                sprintf('Received a non 200 HTTP Status Code (code: %s)', $response->getStatusCode()),
            );
        }

        $zipTempFile = tempnam(sys_get_temp_dir(), 'sepomex-');

        if ($zipTempFile === false) throw new RuntimeException('Unable to create a temporary file');

        try {
            if (file_put_contents($zipTempFile, $response->getContent()) === false)
                throw new RuntimeException('Unable to write SEPOMEX zip data.');

            $this->extractFirstTxt($zipTempFile, $destination);
        } finally {
            if (is_file($zipTempFile)) unlink($zipTempFile);
        }
    }

    private function extractFirstTxt(string $zipPath, string $destination): void {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true)
            throw new RuntimeException('Cannot open downloaded SEPOMEX zip.');

        try {
            $selected = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);

                if (!fnmatch('*.txt', $name)) continue;

                $selected = $name;
                break;
            }

            if ($selected === null)
                throw new RuntimeException('Cannot find a text file inside the downloaded SEPOMEX zip.');

            $contents = $zip->getFromName($selected);

            if ($contents === false)
                throw new RuntimeException('Unable to read SEPOMEX catalog from zip.');

            if (file_put_contents($destination, $contents) === false)
                throw new RuntimeException('Unable to write SEPOMEX destination file.');
        } finally {
            $zip->close();
        }
    }
}
