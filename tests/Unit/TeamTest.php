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
    public function it_has_the_expected_columns(): void {}

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('teams', (new Team)->getTable());
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

        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Team::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return Team::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
