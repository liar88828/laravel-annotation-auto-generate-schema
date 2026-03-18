<?php

namespace App\Attributes\Validation;

use App\Contracts\ValidationRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Regex implements ValidationRule
{
    public function __construct(
        private readonly string $pattern,
        private readonly ?string $message = null,
    ) {}

    public function passes(string $field, mixed $value, array $data = []): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return (bool) preg_match($this->pattern, (string) $value);
    }

    public function message(string $field): string
    {
        return $this->message !== null
            ? str_replace(':field', $field, $this->message)
            : "The {$field} format is invalid.";
    }
}
