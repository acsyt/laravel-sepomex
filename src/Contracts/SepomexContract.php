<?php

namespace Wafto\Sepomex\Contracts;

use Wafto\Sepomex\Entities\Settlement;

interface SepomexContract {
    public function getByPostal(string $postal): array;

    public function getStates(): array;

    public function findSettlement(string $settlementKey): ?Settlement;
}
