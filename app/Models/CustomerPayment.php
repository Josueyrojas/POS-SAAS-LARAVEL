<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Abono de un cliente contra su saldo de fiado (ver Customer::creditBalance()). */
class CustomerPayment extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = ['business_id', 'customer_id', 'amount', 'payment_method', 'notes', 'created_by'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
