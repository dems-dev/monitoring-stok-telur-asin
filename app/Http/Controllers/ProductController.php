<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    /**
     * Laravel 11+ tidak lagi menyediakan $this->middleware() di controller,
     * jadi otorisasi dideklarasikan di sini alih-alih authorizeResource().
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Product::class, only: ['index']),
            new Middleware('can:create,'.Product::class, only: ['create', 'store']),
            new Middleware('can:update,product', only: ['edit', 'update']),
            new Middleware('can:delete,product', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product(),
            'categories' => Product::CATEGORIES,
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "Produk {$product->name} berhasil ditambahkan.");
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Product::CATEGORIES,
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "Produk {$product->name} berhasil diperbarui.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Soft delete: riwayat mutasi di stock_logs tetap bisa dibaca di laporan.
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('status', "Produk {$product->name} dihapus. Riwayat mutasinya tetap tersimpan.");
    }
}
