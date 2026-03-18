<?php

namespace Tests\Unit;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * TeamTest
 *
 * Auto-generated from App\Schema\TeamSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class TeamTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_teams_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('teams'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('teams', 'name'), 'Column [name] missing.');
        $this->assertTrue(Schema::hasColumn('teams', 'slug'), 'Column [slug] missing.');
        $this->assertTrue(Schema::hasColumn('teams', 'description'), 'Column [description] missing.');
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Team;
        $this->assertContains('name', $model->getFillable(), '[name] should be fillable.');
        $this->assertContains('slug', $model->getFillable(), '[slug] should be fillable.');
        $this->assertContains('description', $model->getFillable(), '[description] should be fillable.');
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('teams', (new Team)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('name'), '[name] should fail required.');
    }

    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        $errors = $this->schemaValidate($this->validData());

        $this->assertTrue($errors->isEmpty());
    }

    #[Test]
    public function update_validation_ignores_own_record_in_unique_check(): void
    {
        $model = Team::create($this->createData());
        $errors = $this->schemaValidate(
            ['slug' => $model->slug],
            ignoreUniqueFor: ['slug' => $model->id],
            skipMissing: true,
        );

        $this->assertTrue($errors->isEmpty());
    }

    #[Test]
    public function it_can_create_a_team(): void
    {
        $model = Team::create($this->createData());

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
            'slug' => 'aa',
            'description' => null,
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses factory()->raw() to preserve hidden fields (e.g. password)
     * that toArray() would strip out.
     */
    private function createData(): array
    {
        return Team::factory()->raw();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return Team::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
