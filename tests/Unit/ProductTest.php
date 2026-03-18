<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * ProductTest
 *
 * Auto-generated from App\Schema\ProductSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class ProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_products_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('products'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'name'), "Column [name] missing.");
        $this->assertTrue(Schema::hasColumn('products', 'description'), "Column [description] missing.");
        $this->assertTrue(Schema::hasColumn('products', 'price'), "Column [price] missing.");
        $this->assertTrue(Schema::hasColumn('products', 'stock'), "Column [stock] missing.");
        $this->assertTrue(Schema::hasColumn('products', 'sku'), "Column [sku] missing.");
        $this->assertTrue(Schema::hasColumn('products', 'is_active'), "Column [is_active] missing.");
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Product;
        $this->assertContains('name', $model->getFillable(), "[name] should be fillable.");
        $this->assertContains('description', $model->getFillable(), "[description] should be fillable.");
        $this->assertContains('price', $model->getFillable(), "[price] should be fillable.");
        $this->assertContains('stock', $model->getFillable(), "[stock] should be fillable.");
        $this->assertContains('sku', $model->getFillable(), "[sku] should be fillable.");
        $this->assertContains('is_active', $model->getFillable(), "[is_active] should be fillable.");
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('products', (new Product)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('name'), "[name] should fail required.");
        $this->assertTrue($errors->has('price'), "[price] should fail required.");
        $this->assertTrue($errors->has('stock'), "[stock] should fail required.");
    }

    #[Test]
    public function it_can_create_a_product(): void
    {
        $model = Product::create($this->createData());

        $this->assertNotNull($model->id);
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    #[Test]
    public function soft_delete_works(): void
    {
        $model = Product::create($this->createData());
        $id    = $model->id;

        $model->delete();

        $this->assertNull(Product::find($id));
        $this->assertNotNull(Product::withTrashed()->find($id)?->deleted_at);
    }

    #[Test]
    public function soft_deleted_record_can_be_restored(): void
    {
        $model = Product::create($this->createData());
        $model->delete();
        $model->restore();

        $this->assertNotNull(Product::find($model->id));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimal valid data that passes all validation rules. */
    private function validData(): array
    {
        return [
            'name' => 'aa',
            'description' => null,
            'price' => 1.00,
            'stock' => 1,
            'sku' => null,
            'is_active' => false,
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Product::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return Product::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}