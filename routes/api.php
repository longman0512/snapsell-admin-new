<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/

Route::post('/signup', 'ApiController@signup')->name('/signup');
Route::post('/resendcode', 'ApiController@resendcode')->name('/resendcode');
Route::post('/login', 'ApiController@login')->name('/login');
Route::post('/verify', 'ApiController@verify')->name('/verify');

Route::get('/getcategories', 'ApiController@getcategories')->name('/getcategories');
Route::post('/filterproducts', 'ApiController@filterproducts')->name('/filterproducts');
Route::post('/getprofile', 'ApiController@getprofile')->name('/getprofile');
Route::post('/getreviews', 'ApiController@getreviews')->name('/getreviews');
Route::post('/getproducts', 'ApiController@getproducts')->name('/getproducts');
Route::post('/togglefollow', 'ApiController@togglefollow')->name('/togglefollow');
Route::post('/getfollow', 'ApiController@getfollow')->name('/getfollow');
Route::post('/uploadFile', 'ApiController@uploadFile')->name('/uploadFile');
Route::post('/getpacctterms', 'ApiController@getpacctterms')->name('/getpacctterms');
Route::post('/resetpassword', 'ApiController@resetpassword')->name('/resetpassword');
Route::post('/updatefcmtoken', 'ApiController@updatefcmtoken')->name('/updatefcmtoken');
