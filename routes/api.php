<?php

use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampusEventController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PortfolioPdfController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherDirectoryController;
use App\Http\Controllers\Api\TechnologyController;
use Illuminate\Support\Facades\Route;

Route::get('/technologies', [TechnologyController::class, 'index']);

Route::get('/students', [StudentController::class, 'index']);
Route::get('/students/{student}', [StudentController::class, 'show']);

Route::get('/events', [CampusEventController::class, 'index']);
Route::get('/events/{event}', [CampusEventController::class, 'show']);
Route::get('/events/{event}/projects', [CampusEventController::class, 'projects']);
Route::get('/projects/filters', [ProjectController::class, 'publishedFeedFilters']);
Route::get('/projects', [ProjectController::class, 'publishedFeed']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordController::class, 'forgot']);
Route::post('/reset-password', [PasswordController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/teachers', [TeacherDirectoryController::class, 'index']);

    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);

    Route::get('/my/projects', [ProjectController::class, 'my']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::patch('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{project}/like', [ProjectController::class, 'like']);
    Route::post('/projects/{project}/preview', [ProjectController::class, 'uploadPreview']);
    Route::post('/projects/{project}/gallery', [ProjectController::class, 'uploadGallery']);
    Route::delete('/projects/{project}/gallery/{index}', [ProjectController::class, 'deleteGalleryImage'])
        ->whereNumber('index');
    Route::post('/projects/{project}/inline-image', [ProjectController::class, 'uploadInlineImage']);

    Route::post('/projects/import-from-pdf', [PortfolioPdfController::class, 'import']);
    Route::get('/projects/{project}/pdf', [PortfolioPdfController::class, 'downloadProject']);
    Route::get('/students/{student}/portfolio-pdf', [PortfolioPdfController::class, 'download']);

    Route::post('/events', [CampusEventController::class, 'store']);
    Route::patch('/events/{event}', [CampusEventController::class, 'update']);
    Route::delete('/events/{event}', [CampusEventController::class, 'destroy']);

    Route::get('/admin/users', [UserAdminController::class, 'index']);
    Route::post('/admin/users', [UserAdminController::class, 'store']);
    Route::patch('/admin/users/{user}', [UserAdminController::class, 'update']);
    Route::delete('/admin/users/{user}', [UserAdminController::class, 'destroy']);
});
