# Laravel Schema Annotation System

A PHP 8 attribute-driven system that turns a single schema class into the single source of truth for your Eloquent model's migration, validation, relations, and model configuration - no duplication, no generated files required at runtime.

---

## The Core Idea

Instead of maintaining four separate files that all describe the same data:

```
users migration     -> column types, nullability, defaults, indexes
UserRequest         -> validation rules
User model          -> $fillable, $hidden, $casts, $appends, relations
```

You write one schema class:

```php
#[EloquentModel(model: User::class)]
#[Table(name: 'users', timestamps: true, softDeletes: true)]
class UserSchema
{
    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    #[Column(type: 'string', length: 100)]
    #[Fillable] #[Required] #[Min(2)] #[Max(100)]
    public string $name;

    #[Column(type: 'string', length: 191, unique: true)]
    #[Fillable] #[Required] #[Email] #[Unique(table: 'users')]
    #[Hidden] #[Cast('hashed')]
    public string $password;
}
```

And the model is just:

```php
#[UsesSchema(UserSchema::class)]
class User extends Model
{
    use HasSchema;
}
```

---

## Requirements

- PHP 8.0+
- Laravel 10+ / Laravel 11+
- No additional Composer packages required

---

## Highlights

- `app/Console/Commands` - Artisan commands that generate migrations, relations, and models.
- `app/Support` - Core generator engines and the schema validator.
- `app/Traits` - Runtime wiring for models and migrations.
- `app/Attributes` - Attribute definitions for migration, model, and validation metadata.
- `app/Schema` - Schema classes that define your models in one place.

---

## Directory Structure

```
app/
|-- Actions/
|-- Attributes/
|   |-- Migration/          Column definition and relation attributes
|   |-- Model/              Model wiring attributes
|   |-- Validation/         Validation rule attributes
|-- Concerns/
|-- Console/
|   |-- Commands/           Artisan commands
|-- Contracts/              ValidationRule interface
|-- Example/                UserSchema example
|-- Http/
|   |-- Controllers/        UserController example
|   |-- routes_users.php    Route definitions
|-- Models/                 User model
|-- Providers/
|-- Schema/                 Schema classes
|-- Support/                Generator engines and Validator
|-- Traits/                 HasSchema, RunsSchemaMigration
|-- database/migrations/    Annotation-driven migration files
|-- tests/Unit/             UserTest
```

---

## Attributes Reference

### Migration Attributes

Applied to the schema class to define the database structure.

#### Class-level

| Attribute | Description |
|---|---|
| `#[Table(name, timestamps, softDeletes)]` | Defines the table name and options |

```php
#[Table(name: 'users', timestamps: true, softDeletes: true)]
class UserSchema { }
```

#### Property-level

| Attribute | Description |
|---|---|
| `#[PrimaryKey(type)]` | Primary key column - `bigIncrements`, `uuid`, `ulid` |
| `#[Column(type, length, nullable, default, name, index, unique, comment)]` | Regular column |
| `#[ForeignKey(references, on, onDelete, onUpdate)]` | Foreign key constraint - pair with `#[Column]` |

```php
#[PrimaryKey(type: 'bigIncrements')]
public int $id;

#[Column(type: 'string', length: 191, nullable: false, unique: true)]
public string $email;

#[Column(type: 'unsignedBigInteger', nullable: true)]
#[ForeignKey(references: 'id', on: 'departments', onDelete: 'set null')]
public ?int $department_id;
```

---

### Relation Attributes

| Attribute | Eloquent equivalent | FK lives on |
|---|---|---|
| `#[HasOne(related, foreignKey, localKey, eager)]` | `hasOne()` | Related table |
| `#[HasMany(related, foreignKey, localKey, eager)]` | `hasMany()` | Related table |
| `#[BelongsTo(related, foreignKey, ownerKey, eager)]` | `belongsTo()` | This table |
| `#[BelongsToMany(related, pivotTable, foreignPivotKey, relatedPivotKey, withTimestamps, pivotColumns)]` | `belongsToMany()` | Pivot table |

```php
// HasOne - FK on profiles table
#[HasOne(related: ProfileSchema::class, foreignKey: 'user_id')]
public ProfileSchema $profile;

// HasMany - FK on posts table
#[HasMany(related: PostSchema::class, foreignKey: 'user_id')]
public array $posts;

// BelongsTo - FK on THIS table, pair with #[Column] + #[ForeignKey]
#[Column(type: 'unsignedBigInteger', nullable: true)]
#[ForeignKey(references: 'id', on: 'departments', onDelete: 'set null')]
#[BelongsTo(related: DepartmentSchema::class)]
public ?int $department_id;

// BelongsToMany with pivot columns
#[BelongsToMany(
    related:         RoleSchema::class,
    pivotTable:      'role_user',
    foreignPivotKey: 'user_id',
    relatedPivotKey: 'role_id',
    withTimestamps:  true,
    pivotColumns:    ['date:joined_at'],  // format: 'type:column_name'
)]
public array $roles;
```

---

### Validation Attributes

| Attribute | Rule |
|---|---|
| `#[Required]` | Not null, not empty string or array |
| `#[Min(n)]` | String length / numeric value / array count >= n |
| `#[Max(n)]` | String length / numeric value / array count <= n |
| `#[Email]` | Valid email via `filter_var` |
| `#[Numeric]` | Must be numeric |
| `#[Regex('/pattern/')]` | Must match regex |
| `#[In('a', 'b', 'c')]` | Must be one of the given values |
| `#[Confirmed]` | Must match `{field}_confirmation` in input |
| `#[Unique(table, column, ignoreId)]` | Must not exist in database |

```php
#[Column(type: 'string', length: 191, unique: true)]
#[Required] #[Email] #[Max(191)] #[Unique(table: 'users', column: 'email')]
public string $email;

#[Column(type: 'string', length: 255)]
#[Required] #[Min(8)] #[Confirmed]
public string $password;

#[Column(type: 'string', length: 20, default: 'active')]
#[In('active', 'inactive', 'suspended')]
public ?string $status = 'active';
```

---

### Model Attributes

#### Class-level

| Attribute | Description |
|---|---|
| `#[EloquentModel(model, connection)]` | Declares which model this schema generates |
| `#[UsesSchema(schema)]` | Applied on the model - links it back to the schema |

#### Property-level

| Attribute | Maps to |
|---|---|
| `#[Fillable]` | `$fillable[]` |
| `#[Hidden]` | `$hidden[]` |
| `#[Cast('type')]` | `$casts[]` - supports all Laravel cast strings and class names |
| `#[Appended]` | `$appends[]` - for virtual accessors |

```php
#[Column(type: 'string', length: 255)]
#[Fillable] #[Hidden] #[Cast('hashed')]
public string $password;

#[Column(type: 'json', nullable: true)]
#[Cast('array')]
public ?string $settings;

#[Appended]
public string $full_name;  // implement the accessor in the Model
```

---

### Migration Attribute

| Attribute | Description |
|---|---|
| `#[UsesSchemaMigration(schema)]` | Applied on a migration class - drives `up()` and `down()` from schema annotations at runtime |

```php
#[UsesSchemaMigration(UserSchema::class)]
return new class extends Migration {
    use RunsSchemaMigration;
};
```

---

## Traits Reference

### `HasSchema`

Mix into any Eloquent Model that carries `#[UsesSchema]`.

Boot wiring - resolved automatically at `parent::boot()`:

| Model property | Driven by |
|---|---|
| `$table` | `#[Table(name: '...')]` |
| `$primaryKey` / `$keyType` / `$incrementing` | `#[PrimaryKey(type: '...')]` |
| `$fillable` | `#[Fillable]` on schema + model properties |
| `$hidden` | `#[Hidden]` on schema + model properties |
| `$casts` | `#[Cast('...')]` on schema properties |
| `$appends` | `#[Appended]` on schema properties |
| `SoftDeletingScope` | `#[Table(softDeletes: true)]` - applied automatically |

Relation resolution - `__call()` intercepts any relation method name not defined on the model and resolves it live from the schema's `#[HasOne]`, `#[HasMany]`, `#[BelongsTo]`, `#[BelongsToMany]` annotations.

Validation helpers:

```php
// Returns MessageBag - empty means valid
$errors = User::schemaValidate($data);

// Throws ValidationException on failure
User::schemaValidateOrFail($data);

// For update - auto-ignores $user->id in all #[Unique] checks
$user->schemaValidateForUpdate($data);

// Introspection
User::schemaFillable();  // -> ['name', 'email', ...]
User::schemaCasts();     // -> ['password' => 'hashed', ...]
```

---

### `RunsSchemaMigration`

Mix into a migration class that carries `#[UsesSchemaMigration]`.

Provides `up()` and `down()` by reflecting on the schema at runtime:

- Reads `#[PrimaryKey]`, `#[Column]`, `#[ForeignKey]` to build the Blueprint
- Calls `$blueprint->timestamps()` if `#[Table(timestamps: true)]`
- Calls `$blueprint->softDeletes()` if `#[Table(softDeletes: true)]`
- `down()` calls `Schema::dropIfExists($table->name)`

No generated file needed - works with `RefreshDatabase` in tests out of the box.

---

## Artisan Commands

```bash
# Generate a migration file from a schema class
php artisan schema:migrate "App\Example\UserSchema"
php artisan schema:migrate "App\Example\UserSchema" --print   # preview only

# Generate Eloquent relation methods and pivot migrations
php artisan schema:relations "App\Example\UserSchema"
php artisan schema:relations "App\Example\UserSchema" --pivot    # also write pivot migrations
php artisan schema:relations "App\Example\UserSchema" --summary  # human-readable summary

# Generate the Eloquent Model file
php artisan schema:model "App\Example\UserSchema"
php artisan schema:model "App\Example\UserSchema" --print   # preview only
php artisan schema:model "App\Example\UserSchema" --force   # overwrite existing
```

---

## Full Workflow

```bash
# 1. Write your schema class (UserSchema.php)

# 2. Create the annotation-driven migration file
#    (or write it manually - see database/migrations example)
php artisan schema:migrate "App\Example\UserSchema"

# 3. Generate Eloquent relation methods
php artisan schema:relations "App\Example\UserSchema"

# 4. Write pivot migrations if you have BelongsToMany relations
php artisan schema:relations "App\Example\UserSchema" --pivot

# 5. Generate the Model file
php artisan schema:model "App\Example\UserSchema"

# 6. Run migrations
php artisan migrate
```

After step 5, your model is:

```php
#[UsesSchema(UserSchema::class)]
class User extends Model
{
    use HasSchema;
}
```

Everything else - `$fillable`, `$casts`, `$hidden`, `$appends`, `$table`, soft deletes, and all relation methods - is resolved at runtime from `UserSchema`.

---

## Validation Usage

### In a Controller

```php
// store - full validation
User::schemaValidateOrFail($request->all());

// update - skips Required for absent fields, ignores own record in Unique
$user->schemaValidateForUpdate($request->all());

// manual - returns MessageBag instead of throwing
$errors = User::schemaValidate($request->all());
if ($errors->isNotEmpty()) {
    return response()->json(['errors' => $errors], 422);
}
```

### Ignoring a specific record in Unique checks (PUT update)

```php
Validator::validateOrFail(
    UserSchema::class,
    $request->all(),
    ignoreUniqueFor: ['email' => $user->id],
);
```

### PATCH semantics (only validate present fields)

```php
Validator::validateOrFail(
    UserSchema::class,
    $request->all(),
    ignoreUniqueFor: ['email' => $user->id],
    skipMissing: true,
);
```

---

## Writing a Migration (annotation-driven)

```php
// database/migrations/2024_01_01_000001_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use App\Attributes\Migration\UsesSchemaMigration;
use App\Traits\RunsSchemaMigration;
use App\Schema\UserExampleSchema;

#[UsesSchemaMigration(UserExampleSchema::class)]
return new class extends Migration {
    use RunsSchemaMigration;
};
```

This replaces all hand-written `Schema::create()` calls. `RunsSchemaMigration` reads the schema at runtime and applies every column, constraint, and modifier fluently to the `Blueprint`.

---

## Unit Testing

Because `RunsSchemaMigration` applies the schema at runtime, `RefreshDatabase` works with no prior Artisan commands:

```php
class UserTest extends TestCase
{
    use RefreshDatabase;

    public function it_creates_the_table(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function it_validates_correctly(): void
    {
        $errors = User::schemaValidate(['email' => 'bad']);
        $this->assertTrue($errors->has('email'));
    }

    public function it_can_soft_delete(): void
    {
        $user = User::create([...]);
        $user->delete();
        $this->assertNull(User::find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }
}
```

---

## Adding Extra Fillable / Hidden at the Model Layer

Fields managed internally (not mass-assigned from a form) can be annotated directly on the model property. `HasSchema` merges them with the schema-level annotations automatically:

```php
#[UsesSchema(UserSchema::class)]
class User extends Model
{
    use HasSchema;

    #[Fillable]
    public string $remember_token;

    #[Hidden]
    public string $two_factor_secret;
}
```

---

## Custom Validation Rules

Implement `ValidationRule` and mark it as a PHP 8 `#[Attribute]`:

```php
#[Attribute(Attribute::TARGET_PROPERTY)]
class PhoneNumber implements ValidationRule
{
    public function passes(string $field, mixed $value, array $data = []): bool
    {
        return preg_match('/^\+?[0-9]{7,15}$/', (string) $value);
    }

    public function message(string $field): string
    {
        return "The {$field} must be a valid phone number.";
    }
}
```

Then use it in any schema:

```php
#[Column(type: 'string', length: 20)]
#[Required] #[PhoneNumber]
public string $phone;
```

---

## File Index

| File | Purpose |
|---|---|
| `Attributes/Migration/Table.php` | `#[Table]` - table name, timestamps, soft deletes |
| `Attributes/Migration/Column.php` | `#[Column]` - column type, length, nullable, default |
| `Attributes/Migration/PrimaryKey.php` | `#[PrimaryKey]` - pk type |
| `Attributes/Migration/ForeignKey.php` | `#[ForeignKey]` - FK constraint |
| `Attributes/Migration/HasOne.php` | `#[HasOne]` relation |
| `Attributes/Migration/HasMany.php` | `#[HasMany]` relation |
| `Attributes/Migration/BelongsTo.php` | `#[BelongsTo]` relation |
| `Attributes/Migration/BelongsToMany.php` | `#[BelongsToMany]` relation + pivot |
| `Attributes/Migration/UsesSchemaMigration.php` | `#[UsesSchemaMigration]` - links migration to schema |
| `Attributes/Model/EloquentModel.php` | `#[EloquentModel]` - links schema to model class |
| `Attributes/Model/UsesSchema.php` | `#[UsesSchema]` - links model to schema |
| `Attributes/Model/Fillable.php` | `#[Fillable]` |
| `Attributes/Model/Hidden.php` | `#[Hidden]` |
| `Attributes/Model/Cast.php` | `#[Cast]` |
| `Attributes/Model/Appended.php` | `#[Appended]` |
| `Attributes/Validation/*.php` | Validation rule attributes |
| `Contracts/ValidationRule.php` | Interface all validation attributes implement |
| `Support/Validator.php` | Reflects schema and runs validation rules |
| `Support/MigrationGenerator.php` | Generates migration PHP source from schema |
| `Support/RelationGenerator.php` | Generates Eloquent relation methods and pivot migrations |
| `Support/ModelGenerator.php` | Generates Eloquent Model PHP file from schema |
| `Traits/HasSchema.php` | Boot wiring, relation resolver, validation helpers |
| `Traits/RunsSchemaMigration.php` | Runtime `up()` / `down()` from schema annotations |
| `Console/Commands/SchemaMigrate.php` | `php artisan schema:migrate` |
| `Console/Commands/SchemaRelations.php` | `php artisan schema:relations` |
| `Console/Commands/SchemaModel.php` | `php artisan schema:model` |
| `Example/UserSchema.php` | Full example schema class |
| `Models/User.php` | Minimal model using `HasSchema` |
| `Http/Controllers/UserController.php` | Full CRUD controller example |
| `Http/routes_users.php` | Route definitions |
| `database/migrations/..._create_users_table.php` | Annotation-driven migration |
| `tests/Unit/UserTest.php` | Unit tests with `RefreshDatabase` |
