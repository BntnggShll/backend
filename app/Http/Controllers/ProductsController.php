<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductsController extends Controller
{
    public function index()
    {
        try {
            $products = Product::with('productUnits.unit')
                ->withSum('orderItems', 'jumlah')
                ->orderByDesc('order_items_sum_jumlah')
                ->get();


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
    public function stok()
    {
        try {
            $products = Product::with([
                'productUnits.unit',
                'productUnits.inventory'
            ])->get();

            $data = $products->map(function ($product) {

                $units = $product->productUnits;

                if ($units->isEmpty()) {
                    return ['product_id' => $product->id, 'nama_produk' => $product->nama_produk, 'units' => [], 'total_in_base' => 0, 'display_stock' => 'Tidak ada unit'];
                }
                $baseUnit = $units->firstWhere('is_base_unit', true);
                if (!$baseUnit) {
                    return ['product_id' => $product->id, 'nama_produk' => $product->nama_produk, 'units' => [], 'total_in_base' => 0, 'display_stock' => 'Base unit tidak di-set'];
                }

                // Langkah 1 & 2: Hitung faktor konversi dan total stok (tetap sama dan sudah benar)
                $conversionFactors = [];
                $calculateFactor = function ($unit) use (&$calculateFactor, &$conversionFactors, $units) {
                    if (isset($conversionFactors[$unit->id]))
                        return $conversionFactors[$unit->id];
                    if ($unit->is_base_unit)
                        return $conversionFactors[$unit->id] = 1;
                    $childUnit = $units->firstWhere('parent_id', $unit->id);
                    if (!$childUnit)
                        return $conversionFactors[$unit->id] = 0;
                    return $conversionFactors[$unit->id] = $childUnit->conversion_rate * $calculateFactor($childUnit);
                };
                foreach ($units as $unit) {
                    $calculateFactor($unit);
                }

                $totalInBase = 0;
                foreach ($units as $unit) {
                    $quantity = $unit->inventory->quantity ?? 0;
                    $factor = $conversionFactors[$unit->id] ?? 0;
                    $totalInBase += $quantity * $factor;
                }

                // Langkah 3: Bangun `resultUnits` dengan logika "Tangga"
                $resultUnits = $units
                    ->sortByDesc(function ($unit) use ($conversionFactors) {
                        return $conversionFactors[$unit->id] ?? 0;
                    })
                    ->map(function ($unit) use ($conversionFactors, $totalInBase) {

                        // ### INI PERUBAHAN UTAMANYA (LOGIKA TANGGA) ###
                        $factor = $conversionFactors[$unit->id] ?? 0;
                        $quantityInThisUnit = ($factor > 0) ? floor($totalInBase / $factor) : 0;

                        return [
                            'unit_id' => $unit->id,
                            'unit_name' => $unit->unit->nama_unit ?? '-',
                            'is_base' => $unit->is_base_unit ?? false,
                            'conversion' => $factor,
                            'quantity' => $quantityInThisUnit, // Kuantitas adalah total stok dalam satuan ini
                        ];
                    })->values()->all();

                // Langkah 4: `display_stock` tetap menggunakan logika distribusi (pecahan) agar mudah dibaca manusia
                $displayString = '';
                $remainingStockForDisplay = $totalInBase;
                // Kita perlu array terurut untuk display string
                $sortedForDisplay = collect($resultUnits)->sortByDesc('conversion');
                foreach ($sortedForDisplay as $unit) {
                    $factor = $unit['conversion'];
                    if ($factor <= 0)
                        continue;
                    if ($remainingStockForDisplay >= $factor) {
                        $count = floor($remainingStockForDisplay / $factor);
                        $displayString .= $count . ' ' . $unit['unit_name'] . ', ';
                        $remainingStockForDisplay -= $count * $factor;
                    }
                }
                $displayString = rtrim($displayString, ', ') ?: '0 ' . ($baseUnit->unit->nama_unit ?? 'satuan dasar');

                // --- Hasil Akhir ---
                return [
                    'product_id' => $product->id,
                    'nama_produk' => $product->nama_produk,
                    'units' => $resultUnits,
                    'total_in_base' => $totalInBase,
                    'display_stock' => $displayString,
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            Log::error('Gagal ambil stok: ' . $e->getMessage() . ' di baris ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada server.'], 500);
        }
    }


}
