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
use App\Attributes\Validation\Min;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Uuid;
// use App\Attributes\Model\Hidden;
// use App\Attributes\Model\Cast;
// use App\Attributes\Model\Appended;

// NOTE: The model class name is 'Setting' (without 'Schema' suffix).
// This import is required so PHP resolves Setting::class to App\Models\Setting
// and not to App\Schema\Setting.
use App\Models\Setting;

#[EloquentModel(model: Setting::class)]
#[Table(name: 'settings', timestamps: true, softDeletes: false)]
class SettingSchema
{
    // ── Primary key (UUID v4) ──────────────────────────────────────────────
    // $incrementing = false and $keyType = 'string' are set automatically
    // by HasSchema when it reads #[PrimaryKey(type: 'uuid')].

    #[PrimaryKey(type: 'uuid')]
    #[Uuid(version: 4)]
    public string $id;

    // ── Add your columns below ─────────────────────────────────────────────
    //
    // #[Column(type: 'string', length: 100, nullable: false)]
    // #[Fillable]
    // #[Required(message: 'SettingSchema name is required.')]
    // #[Min(2,   message: 'Name must be at least 2 characters.')]
    // #[Max(100, message: 'Name must not exceed 100 characters.')]
    // public string $name;
}
