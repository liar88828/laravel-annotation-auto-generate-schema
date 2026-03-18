<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Marks a property as mass-assignable ($fillable).
 *
 * Usage:
 *   #[Fillable]
 *   public string $name;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Fillable {}
