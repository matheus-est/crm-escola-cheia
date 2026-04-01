<?php

use App\Http\Controllers\Acl\MenuGroupController;
use App\Http\Controllers\Acl\ModuleController;
use App\Http\Controllers\Acl\RoleController;
use App\Http\Controllers\Acl\TermController;
use App\Http\Controllers\Acl\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('acl')->group(function () {
    Route::middleware('can:users_list')->group(function () {
        Route::get('/users/clearFilters', [UserController::class, 'clearFilters'])->name('users.clear_filters');
        Route::match(['get', 'post'], '/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->middleware('can:users_add')->name('users.create');
        Route::post('/users/store', [UserController::class, 'store'])->middleware('can:users_add')->name('users.store');
        Route::get('/users/check-email', [UserController::class, 'checkEmail'])
            ->middleware('throttle:5,1')
            ->name('users.check-email');
        Route::get('/users/{user_uuid}/edit', [UserController::class, 'edit'])->middleware('can:users_edit')->name('users.edit');
        Route::put('/users/{user_uuid}', [UserController::class, 'update'])->middleware('can:users_edit')->name('users.update');
        Route::match(['get', 'post'], '/users/{user_uuid}/login-audits', [UserController::class, 'loginAudits'])->middleware('can:users_audit_login')->name('users.login-audits');
        Route::post('/users/{user_uuid}/confirm-delete', [UserController::class, 'confirmDelete'])->middleware('can:users_delete')->name('users.confirm-delete');
        Route::delete('/users/{user_uuid}', [UserController::class, 'destroy'])->middleware('can:users_delete')->name('users.destroy');
        Route::post('/users/{user_uuid}/restore', [UserController::class, 'restore'])->middleware('can:users_restore')->name('users.restore');
        Route::get('/users/{user_uuid}/export', [UserController::class, 'export'])->middleware('can:users_export_data')->name('users.export');
    });

    Route::middleware('can:roles_list')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->middleware('can:roles_add')->name('roles.create');
        Route::post('/roles/store', [RoleController::class, 'store'])->middleware('can:roles_add')->name('roles.store');
        Route::get('/roles/{role_uuid}/edit', [RoleController::class, 'edit'])->middleware('can:roles_edit')->name('roles.edit');
        Route::put('/roles/{role_uuid}/update', [RoleController::class, 'update'])->middleware('can:roles_edit')->name('roles.update');
        Route::post('/roles/{role_uuid}/confirm-delete', [RoleController::class, 'confirmDelete'])->middleware('can:roles_delete')->name('roles.confirm-delete');
        Route::delete('/roles/{role_uuid}', [RoleController::class, 'destroy'])->middleware('can:roles_delete')->name('roles.destroy');

        Route::get('/roles/{role_uuid}/permissions', [RoleController::class, 'permissions'])->middleware('can:permissions_edit')->name('roles.permissions');
        Route::put('/roles/{role_uuid}/permissions', [RoleController::class, 'updatePermissions'])->middleware('can:permissions_edit')->name('roles.permissions.update');
    });

    Route::middleware('can:modules_list')->group(function () {
        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/{module_uuid}/edit', [ModuleController::class, 'edit'])->middleware('can:modules_edit')->name('modules.edit');
        Route::put('/modules/{module_uuid}', [ModuleController::class, 'update'])->middleware('can:modules_edit')->name('modules.update');
    });   

    Route::middleware('can:menu_groups_list')->group(function () {
        Route::get('/menu-groups', [MenuGroupController::class, 'index'])->name('menu-groups.index');
        Route::get('/menu-groups/create', [MenuGroupController::class, 'create'])->middleware('can:menu_groups_add')->name('menu-groups.create');
        Route::post('/menu-groups/store', [MenuGroupController::class, 'store'])->middleware('can:menu_groups_add')->name('menu-groups.store');
        Route::get('/menu-groups/{menu_group_uuid}/edit', [MenuGroupController::class, 'edit'])->middleware('can:menu_groups_edit')->name('menu-groups.edit');
        Route::put('/menu-groups/{menu_group_uuid}', [MenuGroupController::class, 'update'])->middleware('can:menu_groups_edit')->name('menu-groups.update');
        Route::delete('/menu-groups/{menu_group_uuid}', [MenuGroupController::class, 'destroy'])->middleware('can:menu_groups_delete')->name('menu-groups.destroy');
    });
    
    Route::middleware('can:terms_list')->group(function () {
        Route::get('/terms', [TermController::class, 'index'])->name('terms.index');
        Route::get('/terms/create', [TermController::class, 'create'])->name('terms.create');
        Route::post('/terms/store', [TermController::class, 'store'])->name('terms.store');
        Route::get('/terms/{term_uuid}/edit', [TermController::class, 'edit'])->name('terms.edit');
        Route::put('/terms/{term_uuid}', [TermController::class, 'update'])->name('terms.update');
        Route::delete('/terms/{term_uuid}', [TermController::class, 'destroy'])->name('terms.destroy');
    });
});

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/terms/accept', [TermController::class, 'showAcceptForm'])->name('terms.accept.form');
    Route::post('/terms/{term_uuid}/accept', [TermController::class, 'accept'])->name('terms.accept');
});
