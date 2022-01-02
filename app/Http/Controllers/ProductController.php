<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Variant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(): View
    {
        $products = Product::query()->with(['variants', 'productVariantPrices']);
        $products = $products->paginate(2);

        $variants = ProductVariant::all()->groupBy('variant_id')->toArray();

        return view('products.index', compact('products', 'variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    private function validator(Request $request, $id = null): Validator
    {
        $rules = [
            'title' => [
                'required',
                'string'
            ],

            'sku' => [
                'required',
                'string',
                'unique:products,' . $id,
            ],

            'description' => [
                'required',
                'string'
            ],

            'product_variant' => [
                'array',
                'required'
            ],
            'product_variant_prices' => [
                'array',
                'required'
            ],
            'product_image' => [
                'nullable'
            ]
        ];

        return \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validator($request)->validate();

        DB::beginTransaction();
        try {
            $product = Arr::only($data, ['title', 'sku', 'description']);
            $product = Product::create($product);

            foreach ($data['product_variant'] as $key => $variant) {
                $variantData = [];
                $variantData['variant_id'] = $variant['option'];

                foreach ($variant['tags'] as $variantItem) {
                    $variantData['variant'] = $variantItem;
                    $product->variants()->create($variantData);
                }

                $productVariantPriceData = [];
                switch ($key) {
                    case 0:
                        $productVariantPriceData['product_variant_one'] = $variant['option'];
                        break;

                    case 1:
                        $productVariantPriceData['product_variant_two'] = $variant['option'];
                        break;

                    case 2:
                        $productVariantPriceData['product_variant_three'] = $variant['option'];
                        break;
                }

                $productVariantPriceData['title'] = $data['product_variant_prices'][$key]['title'];
                $productVariantPriceData['price'] = $data['product_variant_prices'][$key]['price'];
                $productVariantPriceData['stock'] = $data['product_variant_prices'][$key]['stock'];

                $product->productVariantPrices()->create($productVariantPriceData);
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::debug($exception->getMessage());
            return response()->json([
                'message' => 'something_wrong_try_again',
                'alert-type' => 'error'
            ]);
        }

        return response()->json([
            'message' => 'product added successfully!',
            'alert-type' => 'success'
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Product $product
     * @return View
     */
    public function edit(Product $product): View
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants', 'product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function productDatatable(Request $request): JsonResponse
    {
        $page = $request->input('data.page');
        $perPage = 2;

        $productList = Product::query();

        if (!empty($request->input('title'))) {
            $productList->where('title', '%LIKE%', $request->input('title'));
        }

        if (!empty($request->input('date'))) {
            $productList->whereDate('date', '>=', $request->input('date'));
        }

        if (!empty($request->input('variant'))) {
            $productList->with(['variants' => function ($query) use ($request) {
                $query->where('id', $request->input('variant'));
            }]);
        } else {
            $productList->with(['variants']);
        }

        $productList->with(['productVariantPrices' => function ($query) use ($request) {
            if (!empty($request->input('price_from'))) {
                $query->where('price', '>=', $request->input('price_from'));
            }

            if (!empty($request->input('price_to'))) {
                $query->where('price', '<=', $request->input('price_to'));
            }
        }]);

        $paginationResult = $productList->paginate($perPage)->links()->render();

        if ($page) {
            $productList = $productList->skip($perPage * ($page - 1))->take($perPage)->get();
            return response()->json([
                'data' => $productList,
                'links' => $paginationResult,
            ]);
        }

        return response()->json([
            'data' => $productList->paginate($perPage),
            'links' => $paginationResult,
        ]);

    }

    /**
     * @return JsonResponse
     */
    public function variantList(): JsonResponse
    {
        $variants = Variant::all();

        return response()->json([
            'variants' => $variants
        ]);
    }
}
