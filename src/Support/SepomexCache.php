<?php

namespace Wafto\Sepomex\Support;

use Illuminate\Contracts\Cache\Repository;

final class SepomexCache {
    public function __construct(private Repository $cache) {}

    public function remember(string $key, callable $callback): mixed {
        if ($this->supportsTags())
            return $this->cache->tags(['sepomex'])->rememberForever($key, $callback);

        return $callback();
    }

    public function flush(): void {
        if ($this->supportsTags())
            $this->cache->tags(['sepomex'])->flush();
    }

    private function supportsTags(): bool {
        return method_exists($this->cache, 'supportsTags') &&
            $this->cache->supportsTags();
    }
}
