<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'branch_id', 'seller_id', 'customer_id', 'status', 'total',
        'payment_method', 'amount_tendered', 'change_due',
        'voided_at', 'voided_by', 'void_reason', 'refunded_at', 'refunded_by', 'refund_reason',
    ];

    protected $casts = [
        'status' => SaleStatus::class,
        'total' => 'decimal:2',
        'payment_method' => PaymentMethod::class,
        'amount_tendered' => 'decimal:2',
        'change_due' => 'decimal:2',
        'voided_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function voidedBy(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
    public function refundedBy(): BelongsTo { return $this->belongsTo(User::class, 'refunded_by'); }
}
