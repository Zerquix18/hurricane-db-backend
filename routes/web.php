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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function() {
    $hurricane = App\Hurricane::find(1435); // Harvey (2017)
    $wikipedia = new Lib\WikipediaParser($hurricane);
    $data = $wikipedia->getData();
    dd($data);
});
