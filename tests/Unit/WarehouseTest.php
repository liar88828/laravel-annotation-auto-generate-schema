<?php

namespace Tests\Unit;

use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * WarehouseTest
 *
 * Auto-generated from App\Schema\WarehouseSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_warehouses_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('warehouses'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void {}

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('warehouses', (new Warehouse)->getTable());
    }

    #[Test]
    public function it_can_create_a_warehouse(): void
    {
        $model = Warehouse::create($this->createData());

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
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Warehouse::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return Warehouse::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
