<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\History;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * HistoryTest
 *
 * Auto-generated from App\Schema\HistorySchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class HistoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_histories_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('histories'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('histories', 'action'), "Column [action] missing.");
        $this->assertTrue(Schema::hasColumn('histories', 'description'), "Column [description] missing.");
        $this->assertTrue(Schema::hasColumn('histories', 'meta'), "Column [meta] missing.");
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new History;
        $this->assertContains('action', $model->getFillable(), "[action] should be fillable.");
        $this->assertContains('description', $model->getFillable(), "[description] should be fillable.");
    }

    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        $casts = (new History)->getCasts();
        $this->assertArrayHasKey('meta', $casts);
        $this->assertSame('array', $casts['meta']);
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('histories', (new History)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('action'), "[action] should fail required.");
    }

    #[Test]
    public function it_can_create_a_history(): void
    {
        $model = History::create($this->createData());

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
            'action' => 'aa',
            'description' => null,
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return History::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return History::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}