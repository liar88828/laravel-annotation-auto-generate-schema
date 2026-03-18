<?php

namespace App\Attributes\Validation;

use App\Contracts\ValidationRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max implements ValidationRule
{
    public function __construct(
        public readonly int|float $max,
        public readonly ?string $message = null,
    ) {}

    public function passes(string $field, mixed $value, array $data = []): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $this->max;
        }

        if (is_numeric($value)) {
            return $value <= $this->max;
        }

        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        return false;
    }

    public function message(string $field): string
    {
        if ($this->message !== null) {
            return str_replace(':field', $field, $this->message);
        }

        return "The {$field} must not exceed {$this->max}.";
    }
}
