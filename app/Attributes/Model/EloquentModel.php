<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Marks a schema class as the source of truth for an Eloquent Model.
 *
 * Usage (basic):
 *   #[EloquentModel(model: User::class)]
 *
 * Usage (auth model with custom base + extra traits):
 *   #[EloquentModel(
 *       model:   UserExample::class,
 *       extend:  \Illuminate\Foundation\Auth\User::class,
 *       traits:  [\Illuminate\Notifications\Notifiable::class,
 *                 \Laravel\Fortify\TwoFactorAuthenticatable::class],
 *   )]
 *
 * Note: parameter is named 'traits' not 'use' because 'use' is a PHP reserved keyword
 * and cannot be used as a named argument in attribute syntax.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class EloquentModel
{
    /** @param string[] $traits */
    public function __construct(
        public readonly string $model,
        public readonly ?string $extend = null,
        public readonly array $traits = [],
        public readonly ?string $connection = null,
    ) {}
}
