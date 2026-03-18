<?php

namespace Tests\Unit;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * OrderTest
 *
 * Auto-generated from App\Schema\OrderSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_orders_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('orders'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('orders', 'name'), 'Column [name] missing.');
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Order;
        $this->assertContains('name', $model->getFillable(), '[name] should be fillable.');
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('orders', (new Order)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('name'), '[name] should fail required.');
    }

    #[Test]
    public function it_can_create_a_order(): void
    {
        $model = Order::create($this->createData());

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
            'name' => 'aa',
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Order::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return Order::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
