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

Route::get('/positions/{id}', 'HurricaneController@getPositions');
Route::get('/wind_speeds/{id}', 'HurricaneController@getWindspeeds');
Route::get('/pressures/{id}', 'HurricaneController@getPressures');

Route::prefix('ranking')->group(function () {
    Route::get('top_by_lowest_pressure', 'RankingController@topByLowestPressure');
    Route::get('top_by_highest_windspeed', 'RankingController@topByHighestWindSpeed');
    Route::get('top_by_fatalities', 'RankingController@topByFatalities');
    Route::get('top_by_damage', 'RankingController@topByFatalities');
    Route::get('top_by_month', 'RankingController@topByMonth');
    Route::get('top_by_season', 'RankingController@topBySeason');
    Route::get('top_by_fastest_movement', 'RankingController@fastestMovement');
    Route::get('top_by_largest_path', 'RankingController@topByLargestPath');
    Route::get('top_by_landfalls', 'RankingController@topByLandfalls');
    Route::get('earliest_formation_by_category', 'RankingController@earlierFormationByCategory');
    Route::get('latest_formation_by_category', 'RankingController@latestFormationByCategory');
});

Route::get('search', 'SearchController');
