<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Product;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => '/r/wholesaler-firms'], function () use ($router) {
    $router->get('{id}', function ($id) {
        // return view('retailer-app', ['id' => $id]);
        return redirect()->to("https://zaveribazaar.co.in/applink/wholesaler.php?i=$id");
    });
});

$router->get('pi/{productId}', function ($productId) {
    $product = Product::find($productId);
    if (empty($product)) abort(404, 'Product not found!');
    return view('product-image', ['imageUrl' => $product->image_url]);
});
