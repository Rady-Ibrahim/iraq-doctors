<?php

use Illuminate\Support\Facades\Route;
use Modules\Laboratory\Http\Controllers\Web\Data\LaboratoryMetricsDataController;
use Modules\Laboratory\Http\Controllers\Web\Data\LaboratoryOrderDataController;
use Modules\Laboratory\Http\Controllers\Web\Data\LaboratoryOrderResultDataController;
use Modules\Laboratory\Http\Controllers\Web\Data\LaboratoryProfileDataController;
use Modules\Laboratory\Http\Controllers\Web\Data\LaboratorySubscriptionDataController;
use Modules\Laboratory\Http\Controllers\Web\Data\LaboratoryTestDataController;
use Modules\Laboratory\Http\Controllers\Web\LaboratoryAuthController;
use Modules\Laboratory\Http\Controllers\Web\LaboratoryCsrfController;
use Modules\Laboratory\Http\Controllers\Web\LaboratoryDashboardWebController;
use Modules\Laboratory\Http\Controllers\Web\LaboratoryReportPdfController;
use Modules\Laboratory\Http\Controllers\Web\LaboratoryVerificationController;

Route::middleware(['session.scope:laboratory', 'web'])->group(function () {
    Route::redirect('/laboratory', '/laboratory/login');

    Route::prefix('laboratory')->name('laboratory.')->group(function () {
        Route::get('/api/csrf-token', [LaboratoryCsrfController::class, 'token']);

        Route::middleware('guest:web')->group(function () {
            Route::get('/login', [LaboratoryAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [LaboratoryAuthController::class, 'login']);
            Route::get('/register', [LaboratoryAuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [LaboratoryAuthController::class, 'register']);
        });

        Route::middleware(['auth:web', 'laboratory'])->group(function () {
            Route::get('/verify-phone', [LaboratoryAuthController::class, 'showVerifyPhone'])->name('verify-phone');
            Route::post('/verify-phone', [LaboratoryAuthController::class, 'verifyPhone'])->name('verify-phone.submit');
            Route::post('/logout', [LaboratoryAuthController::class, 'logout'])->name('logout');

            Route::middleware('laboratory.phone.verified')->group(function () {
                Route::get('/pending', [LaboratoryVerificationController::class, 'pending'])->name('pending');
                Route::get('/rejected', [LaboratoryVerificationController::class, 'rejected'])->name('rejected');
                Route::get('/suspended', [LaboratoryVerificationController::class, 'suspended'])->name('suspended');
                Route::post('/resubmit-documents', [LaboratoryVerificationController::class, 'resubmit'])->name('resubmit');

                Route::middleware('laboratory.approved')->group(function () {
                    Route::get('/dashboard', [LaboratoryDashboardWebController::class, 'dashboard'])->name('dashboard');
                    Route::get('/dashboard/settings', [LaboratoryDashboardWebController::class, 'settings'])->name('settings');
                    Route::get('/dashboard/branches', [LaboratoryDashboardWebController::class, 'branches'])->name('branches');
                    Route::get('/dashboard/subscription/plans', [LaboratoryDashboardWebController::class, 'subscriptionPlans'])->name('subscription.plans');
                    Route::get('/dashboard/tests', [LaboratoryDashboardWebController::class, 'tests'])->name('tests.index');
                    Route::get('/dashboard/orders', [LaboratoryDashboardWebController::class, 'orders'])->name('orders.index');
                    Route::get('/dashboard/orders/{orderId}', [LaboratoryDashboardWebController::class, 'orderShow'])->name('orders.show');
                    Route::get('/dashboard/reports', [LaboratoryDashboardWebController::class, 'reports'])->name('reports');
                    Route::get('/dashboard/reports/pdf', [LaboratoryReportPdfController::class, 'download'])->name('reports.pdf');

                    Route::prefix('api')->group(function () {
                        Route::get('/profile', [LaboratoryProfileDataController::class, 'show']);
                        Route::post('/profile', [LaboratoryProfileDataController::class, 'update']);
                        Route::put('/profile', [LaboratoryProfileDataController::class, 'update']);

                        Route::get('/branches', [LaboratoryProfileDataController::class, 'branches']);
                        Route::post('/branches', [LaboratoryProfileDataController::class, 'storeBranch']);
                        Route::put('/branches/{branchId}', [LaboratoryProfileDataController::class, 'updateBranch']);
                        Route::delete('/branches/{branchId}', [LaboratoryProfileDataController::class, 'destroyBranch']);

                        Route::get('/subscription', [LaboratorySubscriptionDataController::class, 'status']);
                        Route::get('/subscription/plans', [LaboratorySubscriptionDataController::class, 'plans']);
                        Route::get('/payment-settings', [LaboratorySubscriptionDataController::class, 'paymentSettings']);
                        Route::post('/subscription/subscribe', [LaboratorySubscriptionDataController::class, 'subscribe']);

                        Route::get('/tests', [LaboratoryTestDataController::class, 'index']);
                        Route::get('/tests/suggest', [LaboratoryTestDataController::class, 'suggest']);
                        Route::get('/tests/catalog', [LaboratoryTestDataController::class, 'catalog']);
                        Route::get('/tests/categories', [LaboratoryTestDataController::class, 'categories']);
                        Route::post('/tests/categories', [LaboratoryTestDataController::class, 'storeCategory']);
                        Route::put('/tests/categories/{categoryId}', [LaboratoryTestDataController::class, 'updateCategory']);
                        Route::post('/tests', [LaboratoryTestDataController::class, 'store']);
                        Route::put('/tests/{itemId}', [LaboratoryTestDataController::class, 'update']);
                        Route::delete('/tests/{itemId}', [LaboratoryTestDataController::class, 'destroy']);

                        Route::get('/orders', [LaboratoryOrderDataController::class, 'index']);
                        Route::get('/orders/{orderId}', [LaboratoryOrderDataController::class, 'show']);
                        Route::post('/orders/{orderId}/review', [LaboratoryOrderDataController::class, 'review']);
                        Route::post('/orders/{orderId}/quote', [LaboratoryOrderDataController::class, 'quote']);
                        Route::post('/orders/{orderId}/transition', [LaboratoryOrderDataController::class, 'transition']);

                        Route::get('/metrics', [LaboratoryMetricsDataController::class, 'index']);
                        Route::get('/reports/history', [LaboratoryMetricsDataController::class, 'history']);

                        Route::get('/orders/{orderId}/results', [LaboratoryOrderResultDataController::class, 'index']);
                        Route::post('/orders/{orderId}/results', [LaboratoryOrderResultDataController::class, 'store']);
                        Route::delete('/orders/{orderId}/results/{resultId}', [LaboratoryOrderResultDataController::class, 'destroy']);
                    });
                });
            });
        });
    });
});
