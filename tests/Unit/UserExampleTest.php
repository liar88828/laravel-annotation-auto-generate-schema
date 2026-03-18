<?php

namespace Tests\Unit;

use App\Models\UserExample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * UserExampleTest
 *
 * Auto-generated from App\Schema\UserExampleSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class UserExampleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_users_example_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('usersExample'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('usersExample', 'public_id'), 'Column [public_id] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'name'), 'Column [name] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'email'), 'Column [email] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'password'), 'Column [password] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'status'), 'Column [status] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'age'), 'Column [age] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'is_verified'), 'Column [is_verified] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'born_at'), 'Column [born_at] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'settings'), 'Column [settings] missing.');
        $this->assertTrue(Schema::hasColumn('usersExample', 'department_id'), 'Column [department_id] missing.');
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new UserExample;
        $this->assertContains('public_id', $model->getFillable(), '[public_id] should be fillable.');
        $this->assertContains('name', $model->getFillable(), '[name] should be fillable.');
        $this->assertContains('email', $model->getFillable(), '[email] should be fillable.');
        $this->assertContains('password', $model->getFillable(), '[password] should be fillable.');
        $this->assertContains('status', $model->getFillable(), '[status] should be fillable.');
        $this->assertContains('age', $model->getFillable(), '[age] should be fillable.');
        $this->assertContains('born_at', $model->getFillable(), '[born_at] should be fillable.');
        $this->assertContains('department_id', $model->getFillable(), '[department_id] should be fillable.');
    }

    #[Test]
    public function model_hidden_is_resolved_from_schema(): void
    {
        $model = new UserExample;
        $this->assertContains('password', $model->getHidden(), '[password] should be hidden.');
    }

    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        $casts = (new UserExample)->getCasts();
        $this->assertArrayHasKey('password', $casts);
        $this->assertSame('hashed', $casts['password']);
        $this->assertArrayHasKey('is_verified', $casts);
        $this->assertSame('boolean', $casts['is_verified']);
        $this->assertArrayHasKey('born_at', $casts);
        $this->assertSame('date:Y-m-d', $casts['born_at']);
        $this->assertArrayHasKey('settings', $casts);
        $this->assertSame('array', $casts['settings']);
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('usersExample', (new UserExample)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('public_id'), '[public_id] should fail required.');
        $this->assertTrue($errors->has('name'), '[name] should fail required.');
        $this->assertTrue($errors->has('email'), '[email] should fail required.');
        $this->assertTrue($errors->has('password'), '[password] should fail required.');
    }

    #[Test]
    public function validation_fails_with_invalid_email(): void
    {
        $data = $this->validData();
        $data['email'] = 'not-an-email';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('email'));
    }

    #[Test]
    public function validation_fails_when_status_is_not_in_allowed_values(): void
    {
        $data = $this->validData();
        $data['status'] = '__invalid__';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('status'));
    }

    #[Test]
    public function validation_fails_with_invalid_uuid(): void
    {
        $data = $this->validData();
        $data['public_id'] = 'not-a-uuid';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('public_id'));
    }

    #[Test]
    public function validation_fails_when_password_confirmation_does_not_match(): void
    {
        $data = $this->validData();
        $data['password_confirmation'] = 'different_value';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('password'));
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
        $model = UserExample::create($this->createData());
        $errors = $this->schemaValidate(
            ['email' => $model->email],
            ignoreUniqueFor: ['email' => $model->id],
            skipMissing: true,
        );

        $this->assertTrue($errors->isEmpty());
    }

    #[Test]
    public function it_can_create_a_user_example(): void
    {
        $model = UserExample::create($this->createData());

        $this->assertNotNull($model->id);
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    #[Test]
    public function hidden_fields_are_not_visible_in_serialization(): void
    {
        $model = UserExample::create($this->createData());

        $this->assertArrayNotHasKey('password', $model->toArray());
    }

    #[Test]
    public function soft_delete_works(): void
    {
        $model = UserExample::create($this->createData());
        $id = $model->id;

        $model->delete();

        $this->assertNull(UserExample::find($id));
        $this->assertNotNull(UserExample::withTrashed()->find($id)?->deleted_at);
    }

    #[Test]
    public function soft_deleted_record_can_be_restored(): void
    {
        $model = UserExample::create($this->createData());
        $model->delete();
        $model->restore();

        $this->assertNotNull(UserExample::find($model->id));
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
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => 'active',
            'age' => 1,
            'born_at' => now()->toDateString(),
            'department_id' => 1,
            'password_confirmation' => 'password123',
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     * Merges confirmation fields on top that factories don't include.
     */
    private function createData(): array
    {
        return array_merge(
            UserExample::factory()->make()->toArray(),
            [
                'password_confirmation' => 'password123',
            ]
        );
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return UserExample::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
