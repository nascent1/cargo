<?php

namespace App\Enums;

/**
 * What a cargo type is charged on (cargo_types.charge_bases, from Phase 1).
 * A type lists one or more of these, and every basis in the list is added to the price.
 */
enum ChargeBasis: string
{
    case Volume = 'volume';
    case Weight = 'weight';
    case Value = 'value';
    case Unit = 'unit';

    public function label(): string
    {
        return match ($this) {
            self::Volume => 'Volume (CBM)',
            self::Weight => 'Weight (kg)',
            self::Value => 'Declared value (%)',
            self::Unit => 'Per piece',
        };
    }

    /** What the rate of this basis multiplies. */
    public function per(): string
    {
        return match ($this) {
            self::Volume => 'per CBM',
            self::Weight => 'per kg',
            self::Value => '% of declared value',
            self::Unit => 'per piece',
        };
    }

    /**
     * Reads a stored list, dropping unknown values and duplicates, in the fixed order above.
     *
     * @param  iterable<string|self>|null  $values
     * @return list<self>
     */
    public static function listFrom(?iterable $values): array
    {
        $found = [];
        foreach ($values ?? [] as $value) {
            $basis = $value instanceof self ? $value : self::tryFrom((string) $value);
            if ($basis !== null) {
                $found[$basis->value] = $basis;
            }
        }

        return array_values(array_filter(self::cases(), fn (self $basis) => isset($found[$basis->value])));
    }
}
