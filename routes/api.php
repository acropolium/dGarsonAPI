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

$this->post('login', 'Auth\LoginController@login');
$this->post('register', 'Auth\RegisterController@register');
$this->post('verify', 'Auth\RegisterController@verify');

Route::get('/', function () {
    return 'welcome';
});

Route::get('companies/{company}/menu', 'MenuController@index');
Route::get('companies/{company}/locations', 'LocationController@index');
Route::get('location/{id}', 'LocationController@item');
Route::post('companies/{id}/logo', 'CompanyController@logo');
Route::post('menu/{id}/logo', 'MenuController@logo');
Route::get('orders/last', 'OrderController@lastOrder');
Route::put('users/refresh-token', 'UserController@refreshDeviceToken');
Route::put('users/remove-token', 'UserController@removeDeviceToken');
Route::get('users/companies', 'UserController@companies');
Route::get('clients/', 'UserController@getClients');
Route::get('client/{id}', 'UserController@getClient');

Route::resource('users', 'UserController', ['except' => ['create', 'edit']]);
Route::resource('companies', 'CompanyController', [
    'except' => ['create', 'edit']
]);
Route::resource('menu', 'MenuController', [
    'except' => ['index', 'create', 'edit']
]);
Route::resource('options', 'MenuOptionController', [
    'except' => ['index', 'create', 'edit']
]);
Route::resource('orders', 'OrderController', [
    'except' => ['create', 'edit', 'destroy']
]);
Route::resource('locations', 'LocationController', [
    'except' => ['index', 'create', 'edit']
]);
