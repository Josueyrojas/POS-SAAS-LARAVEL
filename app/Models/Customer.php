<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = [
        'name', 'phone', 'email', 'document_id', 'address', 'notes', 'is_active', 'business_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sales(): HasMany { return $this->hasMany(Sale::class); }
    public function payments(): HasMany { return $this->hasMany(CustomerPayment::class); }

    /** Saldo pendiente de fiado: ventas a crédito completadas menos abonos registrados. */
    public function creditBalance(): float
    {
        $credited = $this->sales()
            ->where('payment_method', PaymentMethod::CREDIT->value)
            ->where('status', SaleStatus::COMPLETED->value)
            ->sum('total');

        $paid = $this->payments()->sum('amount');

        return round((float) $credited - (float) $paid, 2);
    }
}
