<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class UnitController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('role:ADMIN')];
    }

    public function index(): View
    {
        $units = Unit::withCount('products')->orderBy('name')->get();

        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        return view('units.form', [
            'unit' => new Unit(),
        ]);
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $unit = Unit::create($request->validated());

        return redirect()
            ->route('units.index')
            ->with('status', "Satuan {$unit->name} berhasil ditambahkan.");
    }

    public function edit(Unit $unit): View
    {
        return view('units.form', compact('unit'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()
            ->route('units.index')
            ->with('status', "Satuan {$unit->name} berhasil diperbarui.");
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->products()->exists()) {
            return back()->withErrors(['unit' => "Satuan {$unit->name} tidak bisa dihapus karena masih digunakan oleh produk."]);
        }

        $name = $unit->name;
        $unit->delete();

        return redirect()
            ->route('units.index')
            ->with('status', "Satuan {$name} berhasil dihapus.");
    }
}
