<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\Column;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
// use App\Attributes\Migration\ForeignKey;
// use App\Attributes\Migration\HasOne;
// use App\Attributes\Migration\HasMany;
// use App\Attributes\Migration\BelongsTo;
// use App\Attributes\Migration\BelongsToMany;

// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Validation\Max;
// use App\Attributes\Validation\Email;
// use App\Attributes\Validation\Numeric;
// use App\Attributes\Validation\In;
// use App\Attributes\Validation\Unique;
// use App\Attributes\Validation\Confirmed;
// use App\Attributes\Validation\Regex;
// use App\Attributes\Validation\Uuid;

// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Required;
// use App\Attributes\Model\Hidden;
// use App\Attributes\Model\Cast;
// use App\Attributes\Model\Appended;

// NOTE: The model class name is 'Role' (without 'Schema' suffix).
// This import is required so PHP resolves Role::class to App\Models\Role
// and not to App\Schema\Role.
use App\Models\Role;

#[EloquentModel(model: Role::class)]
#[Table(name: 'roles', timestamps: true, softDeletes: false)]
class RoleSchema
{
    // ── Primary key ────────────────────────────────────────────────────────

    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    // ── Add your columns below ─────────────────────────────────────────────
    //
    // #[Column(type: 'string', length: 100, nullable: false)]
    // #[Fillable]
    // #[Required(message: 'RoleSchema name is required.')]
    // #[Min(2,   message: 'Name must be at least 2 characters.')]
    // #[Max(100, message: 'Name must not exceed 100 characters.')]
    // public string $name;
}
