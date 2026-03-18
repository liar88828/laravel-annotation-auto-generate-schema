<?php

namespace App\Attributes\Validation;

use App\Contracts\ValidationRule;
use Attribute;
use Illuminate\Support\Facades\DB;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Unique implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly ?string $column = null,
        private readonly mixed $ignoreId = null,
        private readonly string $idColumn = 'id',
        private readonly ?string $message = null,
    ) {}

    public function passes(string $field, mixed $value, array $data = []): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $column = $this->column ?? $field;
        $query = DB::table($this->table)->where($column, $value);

        if ($this->ignoreId !== null) {
            $query->where($this->idColumn, '!=', $this->ignoreId);
        }

        return ! $query->exists();
    }

    public function message(string $field): string
    {
        return $this->message !== null
            ? str_replace(':field', $field, $this->message)
            : "The {$field} has already been taken.";
    }
}
