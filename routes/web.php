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
use Illuminate\Http\Request;
Route::get('artisan/{commands}',function($commands){    
    echo $commands;
    Artisan::call($commands);
});

Route::get('cache-clear',function(){
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
});

Route::get('/','InstallationController@index');
Route::get('/shopify','InstallationController@registerMe');

Route::post('/shopifyIns','InstallationController@installapp')->name('shopifyIns');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('login/shopify', 'LoginShopifyController@redirectToProvider')->name('login.shopify');
Route::get('shopify/callback', 'LoginShopifyController@handleProviderCallback');
Route::get('/createsection', 'LoginShopifyController@createSection');



// // Web-Hook Routes
Route::post('/orderpaid','HomeController@orderpaid');
// Route::post('/orderpaid',function(){
// 	echo "Test";
// });
Route::post('/test',function(Request $req){
	return $req;
});
// Route::any('/appuninstall', 'HomeController@uninstall');


Route::get('/frontend','AppController@frontend'); 

