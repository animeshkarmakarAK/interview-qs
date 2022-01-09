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
    public function getProduct($id): JsonResponse
    {
        return response()->json([
                'product' => Product::find($id),
            ]
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(): View
    {
        $products = Product::query()->with(['variants', 'productVariantPrices']);
        $products = $products->paginate(2);
        $variants = ProductVariant::select([
            'product_variants.id',
            'product_variants.variant',
            'variants.title',
            'variants.id as variant_id',
        ])->join('variants', 'variants.id', '=', 'product_variants.variant_id')->get();

        $variants = $variants->groupBy('title');

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
        $data = [
            'scope' => 'create',
        ];
        return view('products.create', compact('variants'))->with($data);
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
                'unique:products,sku,' . $id,
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
     * @param $id
     * @return View
     */
    public function edit($id): View
    {
        $variants = Variant::all();
        $product = Product::find($id);
        $productVariants = $product->variants()->get();
        $productVariantPrices = $product->productVariantPrices()->get();

        return view('products.edit', compact('variants', 'product'))->with([
            'productVariants' => $productVariants,
            'productVariantPrices' => $productVariantPrices
        ]);
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

        if (!empty($request->input('data.title'))) {
            $productList->where('title', '%LIKE%', $request->input('data.title'));
        }

        if (!empty($request->input('data.date'))) {
            $productList->whereDate('products.created_at', '>=', $request->input('data.date'));
        }


        if (!empty($request->input('data.variant'))) {
            $productList->with(['variants' => function ($query) use ($productList, $request) {
                $query->where('id', $request->input('data.variant'));
                $filterWithProductId = $query->get()->pluck('product_id')->toArray();

                if (!empty($filterWithProductId)) {
                    $productList->whereIn('products.id', $filterWithProductId);
                }
            }]);
        } else {
            $productList->with(['variants']);
        }

        $productList->with(['productVariantPrices' => function ($query) use ($productList, $request) {
            if ($request->input('data.price_from')) {
                $query->where('price', '>=', $request->input('data.price_from'));
            }

            if ($request->input('data.price_to')) {
                $query->where('price', '<=', $request->input('data.price_to'));
            }

            $filter = $query->get()->pluck('product_id')->toArray();

            if (($request->input('data.price_from') || $request->input('data.price_to')) && !empty($filter)) {
                $productList->whereIn('products.id', $filter);
            }
        }]);

        $total = $productList->count();
        $from = 1;
        $to = $perPage;

        $paginationResult = $productList->paginate($perPage)->links()->render();

        if ($page) {
            $from = $perPage * ($page - 1);
            $to = $from + $perPage;
            $productList = $productList->skip($perPage * ($page - 1))->take($perPage)->get();
            $to = $productList->count() > $perPage ? ($from + $perPage) : $productList->count();
            return response()->json([
                'data' => $productList,
                'links' => $paginationResult,
                'total' => $total,
                'from' => $from,
                'to' => $from + $productList->count()
            ]);
        }

        return response()->json([
            'data' => $productList->paginate($perPage),
            'links' => $paginationResult,
            'total' => $productList->count(),
            'from' => $total == 0 ? $total : $from,
            'to' => $total == 0 ? $total : ($productList->count() > $perPage ? $perPage : $productList->count())
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
