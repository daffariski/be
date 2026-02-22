<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\LightControllerHelper;

class ProductController extends Controller
{
    use LightControllerHelper;

    // ========================================>
    // ## Display a listing of the resource.
    // ========================================>
    public function index(Request $request)
    {
        // ? Initial params
        $params = $this->getParams($request);

        // ? Begin
        $query = Product::query()
            ->search($params["search"] ?? '')
            ->filter(json_decode($params["filter"]))
            ->orderBy($params["sortBy"], $params["sortDirection"])
            ->selectableColumns()
            ->paginate($params["paginate"]);

        $data = $query->items();

        // ? Response
        return $this->responseData($data, $query->total());
    }

    // =============================================>
    // ## Store a newly created resource in storage.
    // =============================================>
    public function store(Request $request)
    {
        // ? Validate request
        $this->validation($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'uom'         => 'nullable|string|max:50',
        ]);

        // ? Initial
        DB::beginTransaction();
        $product = new Product();

        // ? Dump data
        $product->fill($request->all());

        // ? Executing
        try {
            $product->save();
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->responseError($th, 'Create Product');
        }

        // ? final
        DB::commit();
        return $this->responseSaved($product->toArray());
    }

    // ============================================>
    // ## Display the specified resource.
    // ============================================>
    public function show(string $id)
    {
        // ? Initial
        $product = Product::query()
            ->selectableColumns()
            ->findOrFail($id);

        // ? Response
        return $this->responseData([$product->toArray()]);
    }

    // ============================================>
    // ## Update the specified resource in storage.
    // ============================================>
    public function update(Request $request, string $id)
    {
        // ? Initial
        DB::beginTransaction();
        $product = Product::findOrFail($id);
        //
        // return response()->json($request);
        // dd($request->all());

        // ? Validate request
        $this->validation($request->all(), [
            'name'   => 'sometimes|required|string|max:255',
            'series' => 'nullable|string',
            'price'  => 'sometimes|required|numeric|min:0',
            'stock'  => 'sometimes|required|integer|min:0',
            'uom'    => 'nullable|string|max:50',
        ]);

        // ? Dump data
        $product->fill($request->except(['id']));

        // ? Executing
        try {
            $product->save();
            DB::commit();

            return $this->responseSaved($product->toArray());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseError($th, 'Update Product');
        }

        // ? final
    }

    // ===============================================>
    // ## Remove the specified resource from storage.
    // ===============================================>
    public function destroy(string $id)
    {
        // ? Initial
        $product = Product::findOrFail($id);

        // ? Executing
        try {
            $product->delete();
        } catch (\Throwable $th) {
            return $this->responseError($th, 'Delete Product');
        }

        // ? final
        return $this->responseData([], null, 'Product deleted successfully');
    }

    // ================================================>
    // ## Return products as select options
    // ================================================>
    public function getProductOptions(Request $request)
    {
        $search = $request->get('search', '');

        $products = Product::query()
            ->when($search, fn($q) => $q->search($search))
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock', 'uom']);

        return response()->json(
            $products->map(fn($p) => [
                'label' => $p->name . ' — Rp ' . number_format($p->price, 0, ',', '.') . ' / ' . $p->uom,
                'value' => $p->id,
                'price' => $p->price,
                'stock' => $p->stock,
                'uom'   => $p->uom,
            ])
        );
    }
}
