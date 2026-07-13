<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessSettingsController extends Controller
{
    public function edit()
    {
        return view('pos.settings.edit', ['business' => Auth::user()->business]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
        ]);

        Auth::user()->business->update($data);

        return back()->with('status', 'Configuración actualizada.');
    }
}
