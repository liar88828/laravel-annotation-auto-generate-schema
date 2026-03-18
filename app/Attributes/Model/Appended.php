<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Marks a property (accessor) as appended to the model's array/JSON form ($appends).
 *
 * The property must have a corresponding Eloquent accessor defined in the model,
 * or one will be noted in generated output.
 *
 * Usage:
 *   #[Appended]
 *   public string $full_name;   // expects getFullNameAttribute() or Attribute::get()
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Appended {}
