<?php

namespace Wafto\Sepomex\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Wafto\Sepomex\Entities\City;
use Wafto\Sepomex\Entities\District;
use Wafto\Sepomex\Entities\Location;
use Wafto\Sepomex\Entities\Settlement;
use Wafto\Sepomex\Entities\State;

class Sepomex extends Model {
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'id'        => 'integer',
        'is_active' => 'boolean',
    ];

    public function __construct(array $attributes = []) {
        $this->table = config('sepomex.table_name');
        parent::__construct($attributes);
    }

    public function scopePostalCode(Builder $query, string $code): Builder {
        return $query->where('d_codigo', $code);
    }

    public function scopeActive(Builder $query): Builder {
        return $query->where('is_active', true);
    }

    public function toEntity(): Settlement {
        $settlement = new Settlement;
        $settlement->setPostal((string) Arr::get($this->attributes, 'd_codigo'));
        $settlement->setSettlementKey((string) Arr::get($this->attributes, 'settlement_key', ''));
        $settlement->setActive((bool) Arr::get($this->attributes, 'is_active', true));

        if ($this->hasAttributes(['c_estado', 'd_estado']))
            $settlement->setState(new State((int) $this->attributes['c_estado'], $this->attributes['d_estado']));

        if ($this->hasAttributes(['c_cve_ciudad', 'd_ciudad']))
            $settlement->setCity(new City((int) $this->attributes['c_cve_ciudad'], $this->attributes['d_ciudad']));

        if ($this->hasAttributes(['c_mnpio', 'D_mnpio']))
            $settlement->setDistrict(new District((int) $this->attributes['c_mnpio'], $this->attributes['D_mnpio']));

        if ($this->hasAttributes(['d_tipo_asenta', 'd_asenta']))
            $settlement->setLocation(new Location($this->attributes['d_tipo_asenta'], $this->attributes['d_asenta']));

        return $settlement;
    }

    protected function hasAttributes(array $attributes): bool {
        foreach ($attributes as $attr) {
            if (! isset($this->attributes[$attr]))
                return false;
        }

        return true;
    }
}
