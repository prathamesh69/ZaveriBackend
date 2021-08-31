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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'users'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin', 'uses' => 'UserController@index']);
    $router->post('', ['middleware' => 'role:admin', 'uses' => 'UserController@create']);
    $router->post('', ['middleware' => 'role:admin', 'uses'=> 'UserController@create_retailer']);
    $router->post('signup', ['uses' => 'UserController@signup']);
    $router->post('login', ['uses' => 'UserController@login']);
    $router->post('reset-password', ['uses' => 'UserController@resetPassword']);
    $router->post('me/password', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'UserController@password']);

    $router->get('pincodes', ['middleware' => 'role:admin', 'uses' => 'UserController@pincodes']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'UserController@show']);
    $router->post('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'UserController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'UserController@delete']);

    $router->post('{id}/approved/{status}', ['middleware' => 'role:admin', 'uses' => 'UserController@updateApproved']);
});

$router->group(['prefix' => 'wholesaler-firms'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'WholesalerFirmController@index']);
    $router->post('', ['middleware' => 'role:admin,wholesaler', 'uses' => 'WholesalerFirmController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'WholesalerFirmController@show']);
    $router->post('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'WholesalerFirmController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'WholesalerFirmController@delete']);

    $router->get('{id}/profile', ['uses' => 'WholesalerFirmController@showPublic']);
});

$router->group(['prefix' => 'wholesaler-firms/{firmId}/images'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'WholesalerFirmImageController@index']);
    $router->post('', ['middleware' => 'role:admin,wholesaler', 'uses' => 'WholesalerFirmImageController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'WholesalerFirmImageController@show']);
    $router->post('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'WholesalerFirmImageController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'WholesalerFirmImageController@delete']);
});

$router->group(['prefix' => 'categories'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'CategoryController@index']);
    $router->post('', ['middleware' => 'role:admin', 'uses' => 'CategoryController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'CategoryController@show']);
    $router->post('{id}', ['middleware' => 'role:admin', 'uses' => 'CategoryController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'CategoryController@delete']);
});

$router->group(['prefix' => 'subcategories'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'SubcategoryController@index']);
    $router->post('', ['middleware' => 'role:admin', 'uses' => 'SubcategoryController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'SubcategoryController@show']);
    $router->post('{id}', ['middleware' => 'role:admin', 'uses' => 'SubcategoryController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'SubcategoryController@delete']);
});

$router->group(['prefix' => 'brands'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'BrandController@index']);
    $router->post('', ['middleware' => 'role:admin', 'uses' => 'BrandController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'BrandController@show']);
    $router->post('{id}', ['middleware' => 'role:admin', 'uses' => 'BrandController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'BrandController@delete']);
});

$router->group(['prefix' => 'product-types'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductTypeController@index']);
    $router->post('', ['middleware' => 'role:admin', 'uses' => 'ProductTypeController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductTypeController@show']);
    $router->post('{id}', ['middleware' => 'role:admin', 'uses' => 'ProductTypeController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'ProductTypeController@delete']);
});

$router->group(['prefix' => 'products'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductController@index']);
    $router->post('', ['middleware' => 'role:admin,wholesaler', 'uses' => 'ProductController@create']);

    $router->get('meltings', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductController@meltings']);
    $router->get('weight-range', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductController@weightRange']);
    $router->get('tags', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductController@tags']);
    $router->get('cities', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductController@cities']);

    $router->get('categorywise', ['middleware' => 'role:wholesaler,retailer', 'uses' => 'ProductController@categorywise']);
    $router->get('subcategorywise/{categoryId}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductController@subcategorywise']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'ProductController@show']);
    $router->post('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'ProductController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'ProductController@delete']);

    $router->get('{id}/public', ['uses' => 'ProductController@showPublic']);
    $router->post('{id}/bookmark/{status}', ['middleware' => 'role:retailer', 'uses' => 'ProductController@updateBookmark']);
});

$router->group(['prefix' => 'follows'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'FollowController@index']);
    $router->post('', ['middleware' => 'role:admin,retailer', 'uses' => 'FollowController@create']);

    $router->get('{id}/retailer', ['middleware' => 'role:wholesaler', 'uses' => 'FollowController@showRetailer']);
    $router->get('{id}/wholesaler', ['middleware' => 'role:retailer', 'uses' => 'FollowController@showWholesaler']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'FollowController@show']);
    $router->post('{id}/accept', ['middleware' => 'role:admin,wholesaler', 'uses' => 'FollowController@accept']);
    $router->post('{id}/ignore', ['middleware' => 'role:admin,wholesaler', 'uses' => 'FollowController@delete']);
    $router->delete('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'FollowController@delete']);
});

$router->group(['prefix' => 'posts'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'PostController@index']);
    $router->post('', ['middleware' => 'role:admin,wholesaler', 'uses' => 'PostController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'PostController@show']);
    $router->post('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'PostController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'PostController@delete']);
});

$router->group(['prefix' => 'user-contacts'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'c@index']);
    $router->post('', ['middleware' => 'role:wholesaler,retailer', 'uses' => 'UserContactController@create']);

    $router->post('sync', ['middleware' => 'role:wholesaler,retailer', 'uses' => 'UserContactController@sync']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'UserContactController@show']);
    $router->post('{id}', ['middleware' => 'role:admin', 'uses' => 'UserContactController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'UserContactController@delete']);
});

$router->group(['prefix' => 'cities'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'CityController@index']);
    $router->post('', ['middleware' => 'role:admin', 'uses' => 'CityController@create']);

    $router->get('states', ['uses' => 'CityController@states']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'CityController@show']);
    $router->post('{id}', ['middleware' => 'role:admin', 'uses' => 'CityController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'CityController@delete']);
});

$router->group(['prefix' => 'retailer-ratings'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler', 'uses' => 'RetailerRatingController@index']);
    $router->post('', ['middleware' => 'role:wholesaler', 'uses' => 'RetailerRatingController@create']);

    $router->get('overview', ['middleware' => 'role:wholesaler', 'uses' => 'RetailerRatingController@overview']);
    $router->get('cities', ['middleware' => 'role:wholesaler', 'uses' => 'RetailerRatingController@cities']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'RetailerRatingController@show']);
    $router->get('{id}/retailer', ['middleware' => 'role:admin,wholesaler', 'uses' => 'RetailerRatingController@showRetailer']);
    $router->post('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'RetailerRatingController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin,wholesaler', 'uses' => 'RetailerRatingController@delete']);
});

$router->group(['prefix' => 'wholesaler-ratings'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,retailer', 'uses' => 'WholesalerRatingController@index']);
    $router->post('', ['middleware' => 'role:retailer', 'uses' => 'WholesalerRatingController@create']);

    $router->get('cities', ['middleware' => 'role:retailer', 'uses' => 'WholesalerRatingController@cities']);

    $router->get('{id}', ['middleware' => 'role:admin,retailer', 'uses' => 'WholesalerRatingController@show']);
    $router->get('{id}/wholesaler', ['middleware' => 'role:admin,retailer', 'uses' => 'WholesalerRatingController@showWholesaler']);
    $router->post('{id}', ['middleware' => 'role:admin,retailer', 'uses' => 'WholesalerRatingController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin,retailer', 'uses' => 'WholesalerRatingController@delete']);
});

$router->group(['prefix' => 'subscription-plans'], function () use ($router) {
    $router->get('', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'SubscriptionPlanController@index']);
    $router->post('', ['middleware' => 'role:admin', 'uses' => 'SubscriptionPlanController@create']);

    $router->get('{id}', ['middleware' => 'role:admin,wholesaler,retailer', 'uses' => 'SubscriptionPlanController@show']);
    $router->post('{id}', ['middleware' => 'role:admin', 'uses' => 'SubscriptionPlanController@update']);
    $router->delete('{id}', ['middleware' => 'role:admin', 'uses' => 'SubscriptionPlanController@delete']);
});
