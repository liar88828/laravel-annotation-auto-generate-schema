<?php

namespace Tests\Unit;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ShopTest
 *
 * Auto-generated from App\Schema\ShopSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class ShopTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_shops_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('shops'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('shops', 'name'), 'Column [name] missing.');
        $this->assertTrue(Schema::hasColumn('shops', 'address'), 'Column [address] missing.');
        $this->assertTrue(Schema::hasColumn('shops', 'is_active'), 'Column [is_active] missing.');
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Shop;
        $this->assertContains('name', $model->getFillable(), '[name] should be fillable.');
        $this->assertContains('address', $model->getFillable(), '[address] should be fillable.');
        $this->assertContains('is_active', $model->getFillable(), '[is_active] should be fillable.');
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('shops', (new Shop)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('name'), '[name] should fail required.');
    }

    #[Test]
    public function it_can_create_a_shop(): void
    {
        $model = Shop::create($this->createData());

        $this->assertNotNull($model->id);
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    #[Test]
    public function soft_delete_works(): void
    {
        $model = Shop::create($this->createData());
        $id = $model->id;

        $model->delete();

        $this->assertNull(Shop::find($id));
        $this->assertNotNull(Shop::withTrashed()->find($id)?->deleted_at);
    }

    #[Test]
    public function soft_deleted_record_can_be_restored(): void
    {
        $model = Shop::create($this->createData());
        $model->delete();
        $model->restore();

        $this->assertNotNull(Shop::find($model->id));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimal valid data that passes all validation rules. */
    private function validData(): array
    {
        return [
            'name' => 'aa',
            'address' => null,
            'is_active' => false,
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Shop::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return Shop::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
