<?php

use Wafto\Sepomex\Contracts\SepomexContract;
use Wafto\Sepomex\Repositories\CachedRepository;

it('resolves the contract from the container', function () {
    expect(app(SepomexContract::class))->toBeInstanceOf(CachedRepository::class);
});
