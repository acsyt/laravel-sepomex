<?php

namespace Wafto\Sepomex\Contracts;

interface CatalogDownloader {
    public const LINK = 'https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Exportar.aspx';

    public function downloadTo(string $destination): void;
}
