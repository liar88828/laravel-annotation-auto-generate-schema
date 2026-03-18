<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Marks the generated Eloquent model as an authenticatable user model.
 *
 * When present, the generated model will:
 *   - Extend Illuminate\Foundation\Auth\User instead of Illuminate\Database\Eloquent\Model
 *   - Use the Notifiable trait
 *
 * Usage:
 *   #[EloquentModel(model: UserExample::class)]
 *   #[Authenticatable]
 *   #[Table(name: 'users')]
 *   class UserExampleSchema { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Authenticatable
{
    public function __construct() {}
}
