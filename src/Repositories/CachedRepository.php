<?php

namespace Wafto\Sepomex\Repositories;

use Illuminate\Contracts\Cache\Repository;
use Wafto\Sepomex\Contracts\SepomexContract;
use Wafto\Sepomex\Entities\Settlement;
use Wafto\Sepomex\Support\SepomexCache;

class CachedRepository implements SepomexContract {
    protected SepomexContract $next;

    protected SepomexCache $cache;

    public function __construct(SepomexContract $next, Repository $cache) {
        $this->next = $next;
        $this->cache = new SepomexCache($cache);
    }

    public function getByPostal(string $postal): array {
        $hash = sprintf('%s.postal.%s', config('sepomex.table_name'), $postal);

        return $this->cache->remember($hash, fn () => $this->next->getByPostal($postal));
    }

    public function getStates(): array {
        $hash = sprintf('%s.states', config('sepomex.table_name'));

        return $this->cache->remember($hash, fn () => $this->next->getStates());
    }

    public function findSettlement(string $settlementKey): ?Settlement {
        $hash = sprintf('%s.settlement.%s', config('sepomex.table_name'), $settlementKey);

        return $this->cache->remember($hash, fn () => $this->next->findSettlement($settlementKey));
    }
}
