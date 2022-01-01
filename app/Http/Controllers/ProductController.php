<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $products = Product::query()->with(['variants','productVariantPrices']);
        $products = $products->paginate(2);

        return view('products.index', compact('products'));
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

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {

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
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
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
    public function productDatatable(Request $request)
    {
        $page = $request->input('data.page');
        $perPage = 2;

        $productList = Product::query();

        if (!empty($request->input('search_title'))) {
            $productList->where('title', '%LIKE%', $request->input('search_title'));
        }

        if (!empty($request->input('date'))) {
            $productList->where('title', $request->input('date'));
        }

        if (!empty($request->input('variant_id'))) {
            $productList->with(['variants' => function ($query) use ($request) {
                $query->where('id', $request->input('variant_id'));
            }]);
        } else {
            $productList->with(['variants']);
        }

        $productList->with(['productVariantPrices' => function ($query) use ($request) {
            if (!empty($request->input('price_range.start'))) {
                $query->where('price', '>=', $request->input('price_range.start'));
            }

            if (!empty($request->input('price_range.end'))) {
                $query->where('price', '<=', $request->input('price_range.end'));
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
}
