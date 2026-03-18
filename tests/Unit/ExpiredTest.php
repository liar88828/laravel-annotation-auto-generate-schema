<?php

namespace Tests\Unit;

use App\Models\Expired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ExpiredTest
 *
 * Auto-generated from App\Schema\ExpiredSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class ExpiredTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_expireds_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('expireds'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void {}

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('expireds', (new Expired)->getTable());
    }

    #[Test]
    public function it_can_create_a_expired(): void
    {
        $model = Expired::create($this->createData());

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
        return Expired::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return Expired::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
