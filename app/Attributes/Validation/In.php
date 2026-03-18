<?php

namespace App\Attributes\Validation;

use App\Contracts\ValidationRule;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class In implements ValidationRule
{
    public array $allowed;

    public ?string $message;

    public function __construct(mixed ...$allowed)
    {
        // Support trailing named message: #[In('a', 'b', message: 'Pick a or b.')]
        if (array_key_exists('message', $allowed)) {
            $this->message = $allowed['message'];
            unset($allowed['message']);
        } else {
            $this->message = null;
        }

        $this->allowed = array_values($allowed);
    }

    public function passes(string $field, mixed $value, array $data = []): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return in_array($value, $this->allowed, strict: true);
    }

    public function message(string $field): string
    {
        if ($this->message !== null) {
            return str_replace(':field', $field, $this->message);
        }

        $list = implode(', ', array_map(
            fn ($v) => is_string($v) ? "\"{$v}\"" : (string) $v,
            $this->allowed
        ));

        return "The {$field} must be one of: {$list}.";
    }
}
