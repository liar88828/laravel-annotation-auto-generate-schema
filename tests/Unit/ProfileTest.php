<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * ProfileTest
 *
 * Auto-generated from App\Schema\ProfileSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_profiles_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('profiles'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {

    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('profiles', (new Profile)->getTable());
    }

    #[Test]
    public function it_can_create_a_profile(): void
    {
        $model = Profile::create($this->createData());

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
        return Profile::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return Profile::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}