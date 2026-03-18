<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\TransactionSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsesSchema(TransactionSchema::class)]
class Transaction extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'shop_id',
        'quantity',
        'price',
        'total',
        'status',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
