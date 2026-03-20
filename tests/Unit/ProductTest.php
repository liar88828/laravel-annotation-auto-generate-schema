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
        $this->assertTrue(Schema::hasColumn('products', 'status'), "Column [status] missing.");
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
        $this->assertContains('status', $model->getFillable(), "[status] should be fillable.");
    }

    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        $casts = (new Product)->getCasts();
        $this->assertArrayHasKey('price', $casts);
        $this->assertSame('decimal:2', $casts['price']);
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('products', (new Product)->getTable());
    }

    #[Test]
    public function validation_fails_with_invalid_uuid(): void
    {
        $data           = $this->validData();
        $data['id'] = 'not-a-uuid';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('id'));
    }

    #[Test]
    public function it_can_create_a_product(): void
    {
        $model = Product::create($this->createData());

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

        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses factory()->raw() to preserve hidden fields (e.g. password)
     * that toArray() would strip out.
     */
    private function createData(): array
    {
        return Product::factory()->raw();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return Product::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}