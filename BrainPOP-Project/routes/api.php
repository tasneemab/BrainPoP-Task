<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PeriodController;


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

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

//login for users who's not teachers or students
Route::post('/login',[UserController::class, 'login']);

//login apis for students & teachers
Route::post('/teachers/login',[TeacherController::class, 'login']);
Route::post('/students/login',[StudentController::class, 'login']);

//apis can be reached without authorization (adding student/teacher)
Route::post('/teachers/', [TeacherController::class, 'store']);
Route::post('/students', [StudentController::class, 'store']);

//only authorized users
Route::middleware('auth')->group(function () {
  Route::get('/', function () {
    return response('ok');
  });
  Route::apiResource('/teachers', TeacherController::class);
  Route::apiResource('/students', StudentController::class);
  Route::apiResource('/periods', PeriodController::class);

});
