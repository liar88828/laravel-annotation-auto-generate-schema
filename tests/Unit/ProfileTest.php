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
        $this->assertTrue(Schema::hasColumn('profiles', 'role_id'), "Column [role_id] missing.");
        $this->assertTrue(Schema::hasColumn('profiles', 'bio'), "Column [bio] missing.");
        $this->assertTrue(Schema::hasColumn('profiles', 'avatar'), "Column [avatar] missing.");
        $this->assertTrue(Schema::hasColumn('profiles', 'phone'), "Column [phone] missing.");
        $this->assertTrue(Schema::hasColumn('profiles', 'address'), "Column [address] missing.");
        $this->assertTrue(Schema::hasColumn('profiles', 'birth_date'), "Column [birth_date] missing.");
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Profile;
        $this->assertContains('role_id', $model->getFillable(), "[role_id] should be fillable.");
        $this->assertContains('bio', $model->getFillable(), "[bio] should be fillable.");
        $this->assertContains('avatar', $model->getFillable(), "[avatar] should be fillable.");
        $this->assertContains('phone', $model->getFillable(), "[phone] should be fillable.");
        $this->assertContains('address', $model->getFillable(), "[address] should be fillable.");
        $this->assertContains('birth_date', $model->getFillable(), "[birth_date] should be fillable.");
    }

    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        $casts = (new Profile)->getCasts();
        $this->assertArrayHasKey('birth_date', $casts);
        $this->assertSame('date:Y-m-d', $casts['birth_date']);
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('profiles', (new Profile)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('role_id'), "[role_id] should fail required.");
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
            'bio' => null,
            'avatar' => null,
            'phone' => null,
            'address' => null,
            'birth_date' => now()->toDateString(),
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses factory()->raw() to preserve hidden fields (e.g. password)
     * that toArray() would strip out.
     */
    private function createData(): array
    {
        return Profile::factory()->raw();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return Profile::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}