<?php

namespace Wafto\Sepomex\Support;

use InvalidArgumentException;

final class SettlementKey {
    public static function fromCodes(
        int|string $estado,
        int|string $municipio,
        int|string $asenta
    ): string {
        return sprintf(
            '%s-%s-%s',
            self::normalize($estado, 2, 1, 32),
            self::normalize($municipio, 3, 1, 999),
            self::normalize($asenta, 4, 1, 9999)
        );
    }

    public static function fromRow(array $row): string {
        return self::fromCodes(
            $row['c_estado'] ?? '',
            $row['c_mnpio'] ?? '',
            $row['id_asenta_cpcons'] ?? ''
        );
    }

    private static function normalize(
        int|string $value,
        int $width,
        int $min,
        int $max
    ): string {
        $raw = trim((string) $value);
        if ($raw === '' ||
            ! ctype_digit($raw) ||
            strlen($raw) > $width
        ) throw new InvalidArgumentException('SEPOMEX settlement identity is invalid.');

        $int = (int) $raw;
        if ($int < $min || $int > $max)
            throw new InvalidArgumentException('SEPOMEX settlement identity is out of range.');

        return str_pad((string) $int, $width, '0', STR_PAD_LEFT);
    }
}
