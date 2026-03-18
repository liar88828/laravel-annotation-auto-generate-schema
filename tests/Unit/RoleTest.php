<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * RoleTest
 *
 * Auto-generated from App\Schema\RoleSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class RoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_roles_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('roles', 'public_id'), "Column [public_id] missing.");
        $this->assertTrue(Schema::hasColumn('roles', 'name'), "Column [name] missing.");
        $this->assertTrue(Schema::hasColumn('roles', 'status'), "Column [status] missing.");
        $this->assertTrue(Schema::hasColumn('roles', 'age'), "Column [age] missing.");
        $this->assertTrue(Schema::hasColumn('roles', 'born_at'), "Column [born_at] missing.");
        $this->assertTrue(Schema::hasColumn('roles', 'department_id'), "Column [department_id] missing.");
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Role;
        $this->assertContains('public_id', $model->getFillable(), "[public_id] should be fillable.");
        $this->assertContains('name', $model->getFillable(), "[name] should be fillable.");
        $this->assertContains('status', $model->getFillable(), "[status] should be fillable.");
        $this->assertContains('age', $model->getFillable(), "[age] should be fillable.");
        $this->assertContains('born_at', $model->getFillable(), "[born_at] should be fillable.");
        $this->assertContains('department_id', $model->getFillable(), "[department_id] should be fillable.");
    }

    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        $casts = (new Role)->getCasts();
        $this->assertArrayHasKey('born_at', $casts);
        $this->assertSame('date:Y-m-d', $casts['born_at']);
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('roles', (new Role)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('public_id'), "[public_id] should fail required.");
        $this->assertTrue($errors->has('name'), "[name] should fail required.");
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
    public function validation_fails_with_invalid_uuid(): void
    {
        $data           = $this->validData();
        $data['public_id'] = 'not-a-uuid';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('public_id'));
    }

    #[Test]
    public function it_can_create_a_role(): void
    {
        $model = Role::create($this->createData());

        $this->assertNotNull($model->id);
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    #[Test]
    public function soft_delete_works(): void
    {
        $model = Role::create($this->createData());
        $id    = $model->id;

        $model->delete();

        $this->assertNull(Role::find($id));
        $this->assertNotNull(Role::withTrashed()->find($id)?->deleted_at);
    }

    #[Test]
    public function soft_deleted_record_can_be_restored(): void
    {
        $model = Role::create($this->createData());
        $model->delete();
        $model->restore();

        $this->assertNotNull(Role::find($model->id));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimal valid data that passes all validation rules. */
    private function validData(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'name' => 'aa',
            'status' => 'active',
            'age' => 1,
            'born_at' => now()->toDateString(),
            'department_id' => 1,
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return Role::factory()->make()->toArray();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): \Illuminate\Support\MessageBag
    {
        return Role::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}