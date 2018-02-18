<?php

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

Route::get('/default', function() {
    return view('welcome');
});

Route::get('/', 'PageController@home');
Route::get('/search', 'ProductController@search');
Route::get('/item/{id}', 'ProductController@item');
Route::post('/watchlist/add', 'ProductController@addWatchProduct');
Route::get('/watchlist', 'ProductController@getWatchProduct');