<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'supplier_id', 'branch_id', 'status', 'invoice_number',
        'purchase_date', 'total', 'notes', 'created_by',
    ];

    protected $casts = [
        'status' => PurchaseStatus::class,
        'purchase_date' => 'date',
        'total' => 'decimal:2',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
}
