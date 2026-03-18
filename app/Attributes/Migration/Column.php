<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Defines a database column from a class property.
 *
 * Usage:
 *   #[Column(type: 'string', length: 255, nullable: false, default: null)]
 *   public string $name;
 *
 *   #[Column(type: 'decimal', precision: 12, scale: 2, nullable: false)]
 *   public float $price;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly string $type = 'string',
        public readonly ?int $length = null,
        public readonly bool $nullable = false,
        public readonly mixed $default = '__UNSET__',
        public readonly ?string $name = null,   // override column name
        public readonly bool $index = false,
        public readonly bool $unique = false,
        public readonly ?string $comment = null,
        public readonly ?int $precision = null,   // for decimal/float columns
        public readonly ?int $scale = null,   // decimal places
    ) {}
}
