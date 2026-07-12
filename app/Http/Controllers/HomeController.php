<?php

namespace App\Http\Controllers;

use App\Enums\BusinessStatus;
use App\Models\Business;

class HomeController extends Controller
{
    // Portada pública. Solo negocios ACTIVE y solo campos no sensibles.
    public function index()
    {
        $businesses = Business::query()
            ->where('status', BusinessStatus::ACTIVE->value)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('home', compact('businesses'));
    }
}
