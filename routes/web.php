<?php

use App\Http\Controllers\UtilityAPI\ModelResourceFetchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->to('/login');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('product-variant', 'VariantController');
    Route::resource('product', 'ProductController');
    Route::resource('blog', 'BlogController');
    Route::resource('blog-category', 'BlogCategoryController');
    Route::get('get-product', ['App\Http\Controllers\ProductController', 'getProduct'])->name('get-product');

    Route::get('get-product-variants', [\App\Http\Controllers\ProductController::class, 'variantList'])->name('get-product-variants');
    Route::post('product-datatable', ['App\Http\Controllers\ProductController', 'productDatatable'])->name('product-datatable');
    Route::group(['prefix' => 'web-api', 'as' => 'web-api.'], function () {
        Route::post('model-resources', [ModelResourceFetchController::class, 'modelResources'])
            ->name('model-resources');
    });
});
