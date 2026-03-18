<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Marks a property as hidden from serialization ($hidden).
 *
 * Usage:
 *   #[Hidden]
 *   public string $password;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Hidden {}
