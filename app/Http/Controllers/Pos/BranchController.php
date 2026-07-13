<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $includeArchived = $request->query('archived') === '1';

        $branches = Branch::query()
            ->when(! $includeArchived, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('pos.branches.index', compact('branches', 'includeArchived'));
    }

    public function store(Request $request)
    {
        Branch::create($this->validated($request));

        return back()->with('status', 'Sucursal creada.');
    }

    public function update(Request $request, string $branch)
    {
        Branch::findOrFail($branch)->update($this->validated($request));

        return back()->with('status', 'Sucursal actualizada.');
    }

    public function setActive(Request $request, string $branch)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        Branch::findOrFail($branch)->update(['is_active' => $data['is_active']]);

        return back()->with('status', 'Sucursal actualizada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
