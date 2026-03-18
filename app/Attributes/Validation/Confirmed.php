<?php

namespace App\Attributes\Validation;

use App\Contracts\ValidationRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Confirmed implements ValidationRule
{
    public function __construct(
        private readonly ?string $confirmationField = null,
        private readonly ?string $message = null,
    ) {}

    public function passes(string $field, mixed $value, array $data = []): bool
    {
        $confirmKey = $this->confirmationField ?? "{$field}_confirmation";

        return isset($data[$confirmKey]) && $data[$confirmKey] === $value;
    }

    public function message(string $field): string
    {
        return $this->message !== null
            ? str_replace(':field', $field, $this->message)
            : "The {$field} confirmation does not match.";
    }
}
