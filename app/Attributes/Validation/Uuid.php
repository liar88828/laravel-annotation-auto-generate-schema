<?php

namespace App\Attributes\Validation;

use App\Contracts\ValidationRule;
use Attribute;
use Illuminate\Support\Str;

/**
 * Validates that the value is a valid UUID.
 *
 * Without a version: accepts any valid UUID format.
 * With version: also checks the version nibble (position 14).
 *
 * Usage:
 *   #[Uuid]                                                    any version
 *   #[Uuid(version: 4)]                                        UUIDv4 only
 *   #[Uuid(version: 4, message: ':field must be a UUIDv4.')]   custom message
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Uuid implements ValidationRule
{
    public function __construct(
        /** Optionally enforce a specific UUID version (1–5). Null accepts any version. */
        private readonly ?int $version = null,
        private readonly ?string $message = null,
    ) {}

    public function passes(string $field, mixed $value, array $data = []): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        // Use Laravel's battle-tested UUID validator for the base check
        if (! Str::isUuid($value)) {
            return false;
        }

        // If no specific version required, any valid UUID passes
        if ($this->version === null) {
            return true;
        }

        // Version nibble is the first character of the third UUID group (index 14)
        // e.g. f47ac10b-58cc-[4]372-a567-0e02b2c3d479
        //                      ^ index 14
        $versionChar = $value[14] ?? null;

        return $versionChar === (string) $this->version;
    }

    public function message(string $field): string
    {
        if ($this->message !== null) {
            return str_replace(':field', $field, $this->message);
        }

        return $this->version !== null
            ? "The {$field} must be a valid UUID version {$this->version}."
            : "The {$field} must be a valid UUID.";
    }
}
