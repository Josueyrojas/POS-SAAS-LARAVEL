<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasUuids, BelongsToBusiness;

    protected $fillable = ['name', 'address', 'is_active', 'business_id'];

    protected $casts = ['is_active' => 'boolean'];
}
