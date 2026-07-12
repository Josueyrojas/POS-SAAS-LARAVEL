<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $includeArchived = $request->query('archived') === '1';

        $categories = Category::query()
            ->withCount('products')
            ->when(! $includeArchived, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('pos.categories.index', compact('categories', 'includeArchived'));
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return back()->with('status', 'Categoría creada.');
    }

    public function update(Request $request, string $category)
    {
        Category::findOrFail($category)->update($this->validated($request));

        return back()->with('status', 'Categoría actualizada.');
    }

    public function setActive(Request $request, string $category)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        Category::findOrFail($category)->update(['is_active' => $data['is_active']]);

        return back()->with('status', 'Categoría actualizada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
