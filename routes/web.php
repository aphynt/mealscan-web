<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;

// Public Routes
Route::get('/', [AttendanceController::class, 'index'])->name('home');
Route::post('/checkin', [AttendanceController::class, 'checkIn'])->name('checkin');

// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Employee Management
    Route::get('/employees', [AdminController::class, 'employees'])->name('employees');
    Route::get('/employees/create', [AdminController::class, 'createEmployee'])->name('employees.create');
    Route::post('/employees', [AdminController::class, 'storeEmployee'])->name('employees.store');
    Route::get('/employees/{employee}/edit', [AdminController::class, 'editEmployee'])->name('employees.edit');
    Route::put('/employees/{employee}', [AdminController::class, 'updateEmployee'])->name('employees.update');
    Route::delete('/employees/{employee}', [AdminController::class, 'deleteEmployee'])->name('employees.delete');

    // Face Registration
    Route::post('/employees/{employee}/register-face', [AdminController::class, 'registerFace'])->name('employees.register-face');
    Route::delete('/employees/{employee}/delete-face', [AdminController::class, 'deleteFace'])->name('employees.delete-face');

    // Employee Detail
    Route::get('/employees/{employee}/show', [AdminController::class, 'showEmployee'])->name('employees.show');

    // Meal Time Settings
    Route::get('/meal-times', [AdminController::class, 'mealTimeSettings'])->name('meal-times');
    Route::put('/meal-times/{mealType}', [AdminController::class, 'updateMealTime'])->name('meal-times.update');

    // Attendance Report
    Route::get('/attendance-report', [AdminController::class, 'attendanceReport'])->name('attendance-report');
    Route::get('/attendance-report/export', [AdminController::class, 'exportAttendance'])->name('attendance-report.export');
});

