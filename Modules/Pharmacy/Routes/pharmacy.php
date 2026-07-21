<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharmacy\Http\Controllers\Web\Data\PharmacyMedicineDataController;
use Modules\Pharmacy\Http\Controllers\Web\Data\PharmacyMetricsDataController;
use Modules\Pharmacy\Http\Controllers\Web\Data\PharmacyOrderDataController;
use Modules\Pharmacy\Http\Controllers\Web\Data\PharmacyProfileDataController;
use Modules\Pharmacy\Http\Controllers\Web\Data\PharmacySubscriptionDataController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyAuthController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyCsrfController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyDashboardWebController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyReportPdfController;
use Modules\Pharmacy\Http\Controllers\Web\PharmacyVerificationController;

Route::middleware(['session.scope:pharmacy', 'web'])->group(function () {
    Route::redirect('/pharmacy', '/pharmacy/login');

    Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
        Route::get('/api/csrf-token', [PharmacyCsrfController::class, 'token']);

        Route::middleware('guest:web')->group(function () {
            Route::get('/login', [PharmacyAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [PharmacyAuthController::class, 'login']);
            Route::get('/register', [PharmacyAuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [PharmacyAuthController::class, 'register']);
        });

        Route::middleware(['auth:web', 'pharmacy'])->group(function () {
            Route::get('/verify-phone', [PharmacyAuthController::class, 'showVerifyPhone'])->name('verify-phone');
            Route::post('/verify-phone/send', [PharmacyAuthController::class, 'sendPhoneOtp'])->name('verify-phone.send');
            Route::post('/verify-phone', [PharmacyAuthController::class, 'verifyPhone'])->name('verify-phone.submit');
            Route::post('/logout', [PharmacyAuthController::class, 'logout'])->name('logout');

            Route::middleware('pharmacy.phone.verified')->group(function () {
                Route::get('/pending', [PharmacyVerificationController::class, 'pending'])->name('pending');
                Route::get('/rejected', [PharmacyVerificationController::class, 'rejected'])->name('rejected');
                Route::get('/suspended', [PharmacyVerificationController::class, 'suspended'])->name('suspended');
                Route::post('/resubmit-documents', [PharmacyVerificationController::class, 'resubmit'])->name('resubmit');

                Route::middleware('pharmacy.approved')->group(function () {
                    Route::get('/dashboard', [PharmacyDashboardWebController::class, 'dashboard'])->name('dashboard');
                    Route::get('/dashboard/settings', [PharmacyDashboardWebController::class, 'settings'])->name('settings');
                    Route::get('/dashboard/branches', [PharmacyDashboardWebController::class, 'branches'])->name('branches');
                    Route::get('/dashboard/support', [PharmacyDashboardWebController::class, 'support'])->name('support');
                    Route::get('/dashboard/subscription/plans', [PharmacyDashboardWebController::class, 'subscriptionPlans'])->name('subscription.plans');
                    Route::get('/dashboard/medicines', [PharmacyDashboardWebController::class, 'medicines'])->name('medicines.index');
                    Route::get('/dashboard/orders', [PharmacyDashboardWebController::class, 'orders'])->name('orders.index');
                    Route::get('/dashboard/orders/{orderId}', [PharmacyDashboardWebController::class, 'orderShow'])->name('orders.show');
                    Route::get('/dashboard/reports', [PharmacyDashboardWebController::class, 'reports'])->name('reports');
                    Route::get('/dashboard/reports/pdf', [PharmacyReportPdfController::class, 'download'])->name('reports.pdf');

                    Route::prefix('api')->group(function () {
                        Route::get('/profile', [PharmacyProfileDataController::class, 'show']);
                        Route::post('/profile', [PharmacyProfileDataController::class, 'update']);
                        Route::put('/profile', [PharmacyProfileDataController::class, 'update']);

                        Route::get('/branches', [PharmacyProfileDataController::class, 'branches']);
                        Route::post('/branches', [PharmacyProfileDataController::class, 'storeBranch']);
                        Route::put('/branches/{branchId}', [PharmacyProfileDataController::class, 'updateBranch']);
                        Route::delete('/branches/{branchId}', [PharmacyProfileDataController::class, 'destroyBranch']);

                        Route::get('/subscription', [PharmacySubscriptionDataController::class, 'status']);
                        Route::get('/subscription/plans', [PharmacySubscriptionDataController::class, 'plans']);
                        Route::get('/payment-settings', [PharmacySubscriptionDataController::class, 'paymentSettings']);
                        Route::post('/subscription/subscribe', [PharmacySubscriptionDataController::class, 'subscribe']);

                        Route::get('/medicines', [PharmacyMedicineDataController::class, 'index']);
                        Route::get('/medicines/suggest', [PharmacyMedicineDataController::class, 'suggest']);
                        Route::get('/medicines/catalog', [PharmacyMedicineDataController::class, 'catalog']);
                        Route::get('/medicines/categories', [PharmacyMedicineDataController::class, 'categories']);
                        Route::post('/medicines/categories', [PharmacyMedicineDataController::class, 'storeCategory']);
                        Route::put('/medicines/categories/{categoryId}', [PharmacyMedicineDataController::class, 'updateCategory']);
                        Route::post('/medicines', [PharmacyMedicineDataController::class, 'store']);
                        Route::put('/medicines/{itemId}', [PharmacyMedicineDataController::class, 'update']);
                        Route::delete('/medicines/{itemId}', [PharmacyMedicineDataController::class, 'destroy']);

                        Route::get('/orders', [PharmacyOrderDataController::class, 'index']);
                        Route::get('/orders/{orderId}', [PharmacyOrderDataController::class, 'show']);
                        Route::post('/orders/{orderId}/review', [PharmacyOrderDataController::class, 'review']);
                        Route::post('/orders/{orderId}/quote', [PharmacyOrderDataController::class, 'quote']);
                        Route::post('/orders/{orderId}/transition', [PharmacyOrderDataController::class, 'transition']);

                        Route::get('/metrics', [PharmacyMetricsDataController::class, 'index']);
                        Route::get('/reports/history', [PharmacyMetricsDataController::class, 'history']);

                        // Support contacts — read & manage (uses shared SupportContact model)
                        Route::get('/support-contacts', [\Modules\Admin\Http\Controllers\Api\AdminSupportContactApiController::class, 'index']);
                        Route::post('/support-contacts', [\Modules\Admin\Http\Controllers\Api\AdminSupportContactApiController::class, 'store']);
                        Route::put('/support-contacts/{id}', [\Modules\Admin\Http\Controllers\Api\AdminSupportContactApiController::class, 'update']);
                        Route::delete('/support-contacts/{id}', [\Modules\Admin\Http\Controllers\Api\AdminSupportContactApiController::class, 'destroy']);
                    });
                });
            });
        });
    });
});
