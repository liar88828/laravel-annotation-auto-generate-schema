<?php

namespace App\Contracts;

/**
 * CValidationRule
 *
 * Interface for custom validation attributes used in schema classes.
 * Implement this to create custom validators like #[Rupiah], #[NIK], #[PhoneID].
 *
 * Benefits over Option A (extends):
 *   - Can also implement Laravel's \Illuminate\Contracts\Validation\Rule
 *   - Can implement multiple interfaces
 *   - Not tied to base class hierarchy
 *
 * Usage:
 *
 *   #[Attribute(Attribute::TARGET_PROPERTY)]
 *   class Rupiah implements CValidationRule
 *   {
 *       public function rule(): string
 *       {
 *           return 'regex:/^\d+(\.\d{3})*(,\d{2})?$/';
 *       }
 *
 *       public function message(): string
 *       {
 *           return 'The :attribute must be a valid Rupiah format (e.g. 1.000.000,00).';
 *       }
 *   }
 *
 * Then in schema:
 *
 *   #[CMigration(type: 'integer', nullable: false)]
 *   #[CModel(fillable: true)]
 *   #[CValidation(required: true, min: 0)]
 *   #[Rupiah]
 *   public int $price;
 *
 * Also works directly in Laravel validation:
 *
 *   $request->validate([
 *       'price' => ['required', new Rupiah],
 *   ]);
 */
interface CValidationRule
{
    /**
     * The Laravel validation rule string or array.
     * e.g. 'regex:/^\d+$/'  or  ['regex:/^\d+$/', 'min:0']
     */
    public function rule(): string|array;

    /**
     * The error message when validation fails.
     * Use :attribute as placeholder for the field name.
     * e.g. 'The :attribute must be a valid Rupiah format.'
     */
    public function message(): string;
}
