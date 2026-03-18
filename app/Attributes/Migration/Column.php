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
 *
 *   #[Column(type: 'string', primary: true)]   // string primary key (e.g. email, slug)
 *   public string $email;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly string  $type      = 'string',
        public readonly ?int    $length    = null,
        public readonly bool    $nullable  = false,
        public readonly mixed   $default   = '__UNSET__',
        public readonly ?string $name      = null,
        public readonly bool    $index     = false,
        public readonly bool    $unique    = false,
        public readonly ?string $comment   = null,
        public readonly ?int    $precision = null,
        public readonly ?int    $scale     = null,
        public readonly bool    $primary   = false,  // string/non-auto primary key
    ) {}
}
