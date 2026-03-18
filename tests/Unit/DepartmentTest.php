<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * DepartmentTest
 *
 * Auto-generated from App\Schema\DepartmentSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_departments_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('departments'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('departments', 'name'), "Column [name] missing.");
        $this->assertTrue(Schema::hasColumn('departments', 'code'), "Column [code] missing.");
        $this->assertTrue(Schema::hasColumn('departments', 'slug'), "Column [slug] missing.");
        $this->assertTrue(Schema::hasColumn('departments', 'description'), "Column [description] missing.");
        $this->assertTrue(Schema::hasColumn('departments', 'status'), "Column [status] missing.");
        $this->assertTrue(Schema::hasColumn('departments', 'budget'), "Column [budget] missing.");
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Department;
        $this->assertContains('name', $model->getFillable(), "[name] should be fillable.");
        $this->assertContains('code', $model->getFillable(), "[code] should be fillable.");
        $this->assertContains('slug', $model->getFillable(), "[slug] should be fillable.");
        $this->assertContains('description', $model->getFillable(), "[description] should be fillable.");
        $this->assertContains('status', $model->getFillable(), "[status] should be fillable.");
        $this->assertContains('budget', $model->getFillable(), "[budget] should be fillable.");
    }

    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        $casts = (new Department)->getCasts();
        $this->assertArrayHasKey('budget', $casts);
        $this->assertSame('decimal:2', $casts['budget']);
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('departments', (new Department)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('name'), "[name] should fail required.");
        $this->assertTrue($errors->has('code'), "[code] should fail required.");
    }

    #[Test]
    public function validation_fails_when_status_is_not_in_allowed_values(): void
    {
        $data           = $this->validData();
        $data['status'] = '__invalid__';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('status'));
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
        $model  = Department::create($this->createData());
        $errors = $this->schemaValidate(
            ['code' => $model->code],
            ignoreUniqueFor: ['code' => $model->id],
            skipMissing: true,
        );

        $this->assertTrue($errors->isEmpty());
    }

    #[Test]
    public function it_can_create_a_department(): void
    {
        $model = Department::create($this->createData());

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
            'code' => 'aa',
            'slug' => 'aa',
            'description' => null,
            'status' => 'active',
            'budget' => 1.00,
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Department::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return Department::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}