<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductsController extends Controller
{
    public function index()
    {
        try {
            // Ambil semua produk dan relasinya secara efisien dengan Eager Loading
            $products = Product::with('productUnits.unit')->latest()->get();

            $productsWithDetails = $products->map(function ($product) {
                $stockInfo = $product->calculateStock();
                $unitsData = $product->productUnits->map(function ($productUnit) {
                    return [
                        'id' => $productUnit->id,
                        'nama_unit' => $productUnit->unit ? $productUnit->unit->nama_unit : 'N/A',
                        'price' => (float) $productUnit->harga_jual, // Menggunakan 'price'
                        'is_base_unit' => (bool) $productUnit->is_base_unit,
                    ];
                });

                return [
                    'id' => $product->id,
                    'nama_produk' => $product->nama_produk,
                    'jenis_produk' => $product->jenis_produk,
                    'image_url' => $product->image ? asset('storage/' . $product->image) : null,
                    'stock_display' => $stockInfo['display'],
                    'product_units' => $unitsData,
                ];
            })
            ->filter(function ($product) {
                return !is_null($product['stock_display']);
            })
            ->values();
            return response()->json(['success' => true, 'data' => $productsWithDetails]);

        } catch (\Exception $e) {
            Log::error('Error saat mengambil data produk: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data produk.'], 500);
        }
    }

    /**
     * Menampilkan detail satu produk berdasarkan ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // 1. Cari produk berdasarkan ID beserta relasinya.
            $product = Product::with('productUnits.unit')->findOrFail($id);

            // 2. Panggil fungsi untuk menghitung stok.
            $stockInfo = $product->calculateStock();

            // 3. Format data unit produk.
            $unitsData = $product->productUnits->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,
                    'nama_unit' => $productUnit->unit->nama_unit ?? 'N/A',
                    'price' => (float) $productUnit->harga_jual, // Menggunakan 'price'
                    'is_base_unit' => (bool) $productUnit->is_base_unit,
                ];
            });

            // 4. Susun data respons yang akan dikirim.
            $productData = [
                'id' => $product->id,
                'nama_produk' => $product->nama_produk,
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
                // Deskripsi diambil dari produk utama, bukan dari unit
                'description' => $product->jenis_produk ?? 'Deskripsi untuk produk ini belum tersedia.',
                'stock_display' => $stockInfo['display'],
                'product_units' => $unitsData,
            ];

            // 5. Kembalikan respons JSON yang sukses.
            return response()->json([
                'success' => true,
                'data' => $productData,
            ]);

        } catch (ModelNotFoundException $e) {
            // Tangani jika produk tidak ditemukan.
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);

        } catch (\Exception $e) {
            // Tangani error tak terduga lainnya.
            Log::error("Error saat mengambil detail produk ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail produk.',
            ], 500);
        }
    }

    private function calculateChildrenUnits($unit, $baseQty, $conversion = 1, &$result = [])
    {
        foreach ($unit->children as $child) {
            $totalConversion = $conversion * $child->conversion_rate;
            $quantity = $baseQty * $totalConversion;

            $result[] = [
                'unit_id' => $child->id,
                'unit_name' => $child->unit->nama_unit ?? '-',
                'conversion' => $totalConversion,
                'quantity' => $quantity,
                'is_base' => false
            ];

            // Rekursi: cari cucu unit
            $this->calculateChildrenUnits($child, $baseQty, $totalConversion, $result);
        }

        return $result;
    }

    public function stok()
    {
        try {
            $products = Product::with([
                'productUnits.unit',
                'productUnits.inventory',
                'productUnits.children.unit',
                'productUnits.children.children' // Untuk eager loading awal
            ])->get();

            $data = $products->map(function ($product) {
                $units = $product->productUnits ?? collect();

                $baseUnit = $units->firstWhere('parent_id', null);

                $baseQty = $baseUnit && $baseUnit->inventory
                    ? $baseUnit->inventory->quantity
                    : 0;

                $resultUnits = [];

                // Base unit
                $resultUnits[] = [
                    'unit_id' => $baseUnit->id,
                    'unit_name' => $baseUnit->unit->nama_unit ?? '-',
                    'conversion' => 1,
                    'quantity' => $baseQty,
                    'is_base' => true
                ];

                // Ambil anak-anaknya secara rekursif
                $this->calculateChildrenUnits($baseUnit, $baseQty, 1, $resultUnits);

                return [
                    'product_id' => $product->id,
                    'nama_produk' => $product->nama_produk,
                    'units' => $resultUnits
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            Log::error('Gagal ambil stok: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan.'], 500);
        }


    }

}
