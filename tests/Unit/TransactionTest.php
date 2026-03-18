<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * TransactionTest
 *
 * Auto-generated from App\Schema\TransactionSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class TransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_transactions_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('transactions'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('transactions', 'product_id'), "Column [product_id] missing.");
        $this->assertTrue(Schema::hasColumn('transactions', 'shop_id'), "Column [shop_id] missing.");
        $this->assertTrue(Schema::hasColumn('transactions', 'quantity'), "Column [quantity] missing.");
        $this->assertTrue(Schema::hasColumn('transactions', 'price'), "Column [price] missing.");
        $this->assertTrue(Schema::hasColumn('transactions', 'total'), "Column [total] missing.");
        $this->assertTrue(Schema::hasColumn('transactions', 'status'), "Column [status] missing.");
        $this->assertTrue(Schema::hasColumn('transactions', 'notes'), "Column [notes] missing.");
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Transaction;
        $this->assertContains('product_id', $model->getFillable(), "[product_id] should be fillable.");
        $this->assertContains('shop_id', $model->getFillable(), "[shop_id] should be fillable.");
        $this->assertContains('quantity', $model->getFillable(), "[quantity] should be fillable.");
        $this->assertContains('price', $model->getFillable(), "[price] should be fillable.");
        $this->assertContains('total', $model->getFillable(), "[total] should be fillable.");
        $this->assertContains('status', $model->getFillable(), "[status] should be fillable.");
        $this->assertContains('notes', $model->getFillable(), "[notes] should be fillable.");
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('transactions', (new Transaction)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('product_id'), "[product_id] should fail required.");
        $this->assertTrue($errors->has('quantity'), "[quantity] should fail required.");
        $this->assertTrue($errors->has('price'), "[price] should fail required.");
    }

    #[Test]
    public function validation_fails_when_status_is_not_in_allowed_values(): void
    {
        $data           = $this->validData();
        $data['status'] = '__invalid__';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('status'));
    }

    #[Test]
    public function it_can_create_a_transaction(): void
    {
        $model = Transaction::create($this->createData());

        $this->assertNotNull($model->id);
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimal valid data that passes all validation rules. */
    private function validData(): array
    {
        return [
            'product_id' => 1,
            'shop_id' => 1,
            'quantity' => 1,
            'price' => 1.00,
            'total' => 1.00,
            'status' => 'pending',
            'notes' => null,
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Transaction::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return Transaction::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}