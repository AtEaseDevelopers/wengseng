<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SqlSyncRecordController;

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

Route::middleware('verify.signature')->group(function () {
    Route::post('/sql-sync-records', [SqlSyncRecordController::class, 'store']);
    Route::get('/sql-sync-records', [SqlSyncRecordController::class, 'index']);
});


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
