<?php

namespace Wafto\Sepomex\Repositories;

use Wafto\Sepomex\Contracts\SepomexContract;
use Wafto\Sepomex\Entities\Settlement;
use Wafto\Sepomex\Entities\State;
use Wafto\Sepomex\Models\Sepomex;

class DatabaseRepository implements SepomexContract {
    public function __construct(protected Sepomex $model) {}

    public function getByPostal(string $postal): array {
        return $this->model->active()
            ->postalCode($postal)
            ->get()
            ->map(fn (Sepomex $item) => $item->toEntity()->toArray())
            ->all();
    }

    public function getStates(): array {
        return $this->model->active()
            ->select(['c_estado', 'd_estado'])
            ->distinct()
            ->get()
            ->map(fn ($row) => new State((int) $row->c_estado, $row->d_estado))
            ->toArray();
    }

    public function findSettlement(string $settlementKey): ?Settlement {
        $row = $this->model->newQuery()
            ->where('settlement_key', $settlementKey)
            ->first();

        if ($row === null)
            return null;

        return $row->toEntity();
    }
}
