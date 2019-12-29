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



Route::get('/basin/{basin}/', 'HurricaneController@getBasin')->where('basin', '[a-z]+');

Route::get('/basin/{basin}/season/{season}', 'HurricaneController@getSeason')->where([
    'basin' => '[a-z]+',
    'season' => '[0-9]+',
]);

Route::get('/basin/{basin}/season/{season}/hurricane/{hurricane}', 'HurricaneController@getHurricane')->where([
    'basin' => '[a-z]+',
    'season' => '[0-9]+',
    'hurricane' => '[a-z]+',
]);

Route::get('/test', function () {
    dump(Lib\HurricaneRecord::topByMonth());
});
