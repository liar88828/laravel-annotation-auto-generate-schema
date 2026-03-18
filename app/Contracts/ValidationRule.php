<?php

namespace App\Contracts;

interface ValidationRule
{
    /**
     * Validate the given value.
     *
     * @param  string  $field  The field name being validated
     * @param  mixed  $value  The value to validate
     * @param  array  $data  The full input data (for cross-field rules)
     */
    public function passes(string $field, mixed $value, array $data = []): bool;

    /**
     * Return the validation error message.
     */
    public function message(string $field): string;
}
