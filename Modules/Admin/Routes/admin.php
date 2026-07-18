<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Web\AdminAuthController;
use Modules\Admin\Http\Controllers\Api\AdminDashboardController;
use Modules\Admin\Http\Controllers\Api\AdminDashboardApiController;
use Modules\Admin\Http\Controllers\Api\AdminCatalogApiController;
use Modules\Admin\Http\Controllers\Api\AdminLabTestApiController;
use Modules\Admin\Http\Controllers\Api\AdminMedicineApiController;
use Modules\Admin\Http\Controllers\Api\AdminOrdersApiController;
use Modules\Admin\Http\Controllers\Api\AdminSupportContactApiController;
use Modules\Admin\Http\Controllers\Web\AdminCsrfController;
use Modules\Admin\Http\Controllers\Web\AdminDashboardWebController;
use Modules\Admin\Http\Controllers\Web\AdminReportPdfController;
use Modules\Auth\Http\Controllers\Admin\AdminUserController;

Route::middleware(['session.scope:admin', 'web'])->group(function () {
    Route::redirect('/admin', '/admin/login');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/api/csrf-token', [AdminCsrfController::class, 'token']);

        Route::middleware('guest:web')->group(function () {
            Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AdminAuthController::class, 'login']);
        });

        Route::middleware(['auth:web', 'admin'])->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

            Route::get('/dashboard', [AdminDashboardWebController::class, 'dashboard'])->name('dashboard');
            Route::get('/dashboard/doctors', [AdminDashboardWebController::class, 'doctors'])->name('doctors.index');
            Route::get('/dashboard/doctors/{id}', [AdminDashboardWebController::class, 'doctorShow'])->name('doctors.show');
            Route::get('/dashboard/laboratories', [AdminDashboardWebController::class, 'laboratories'])->name('laboratories.index');
            Route::get('/dashboard/laboratories/{id}', [AdminDashboardWebController::class, 'laboratoryShow'])->name('laboratories.show');
            Route::get('/dashboard/pharmacies', [AdminDashboardWebController::class, 'pharmacies'])->name('pharmacies.index');
            Route::get('/dashboard/pharmacies/{id}', [AdminDashboardWebController::class, 'pharmacyShow'])->name('pharmacies.show');
            Route::get('/dashboard/patients', [AdminDashboardWebController::class, 'patients'])->name('patients.index');
            Route::get('/dashboard/patients/{id}', [AdminDashboardWebController::class, 'patientShow'])->name('patients.show');
            Route::get('/dashboard/appointments', [AdminDashboardWebController::class, 'appointments'])->name('appointments.index');
            Route::get('/dashboard/revenue', [AdminDashboardWebController::class, 'revenue'])->name('revenue');
            Route::get('/dashboard/subscriptions', [AdminDashboardWebController::class, 'subscriptions'])->name('subscriptions.index');
            Route::get('/dashboard/reviews', [AdminDashboardWebController::class, 'reviews'])->name('reviews.index');
            Route::get('/dashboard/analytics', [AdminDashboardWebController::class, 'analytics'])->name('analytics');
            Route::get('/dashboard/specialities', [AdminDashboardWebController::class, 'specialities'])->name('specialities.index');
            Route::get('/dashboard/governorates', [AdminDashboardWebController::class, 'governorates'])->name('governorates.index');
            Route::get('/dashboard/lab-tests', [AdminDashboardWebController::class, 'labTests'])->name('lab-tests.index');
            Route::get('/dashboard/medicines', [AdminDashboardWebController::class, 'medicines'])->name('medicines.index');
            Route::get('/dashboard/orders', [AdminDashboardWebController::class, 'orders'])->name('orders.index');
            Route::get('/dashboard/reports', [AdminDashboardWebController::class, 'reports'])->name('reports.index');
            Route::get('/dashboard/reports/orders/pdf', [AdminReportPdfController::class, 'orders'])->name('reports.orders.pdf');
            Route::get('/dashboard/revenue/pdf', [AdminReportPdfController::class, 'revenue'])->name('revenue.pdf');
            Route::get('/dashboard/support-contacts', [AdminDashboardWebController::class, 'supportContacts'])->name('support-contacts.index');
            Route::get('/users', [AdminDashboardWebController::class, 'users'])->name('users.index');
            Route::redirect('/subscriptions', '/admin/dashboard/subscriptions')->name('subscriptions');

            Route::post('/doctors/{id}/approve', [AdminDashboardController::class, 'approveDoctor'])->name('doctors.approve');
            Route::post('/doctors/{id}/reject', [AdminDashboardController::class, 'rejectDoctor'])->name('doctors.reject');
            Route::post('/doctors/{id}/suspend', [AdminDashboardController::class, 'suspendDoctor'])->name('doctors.suspend');
            Route::post('/doctors/{id}/activate', [AdminDashboardController::class, 'activateDoctor'])->name('doctors.activate');

            Route::prefix('api')->group(function () {
                Route::get('/metrics', [AdminDashboardApiController::class, 'metrics']);
                Route::get('/notifications/unread', [AdminDashboardApiController::class, 'unreadNotifications']);
                Route::post('/notifications/{notificationId}/read', [AdminDashboardApiController::class, 'markNotificationRead']);
                Route::post('/notifications/read-all', [AdminDashboardApiController::class, 'markAllNotificationsRead']);
                Route::get('/doctors', [AdminDashboardApiController::class, 'doctors']);
                Route::get('/doctors/{id}', [AdminDashboardApiController::class, 'doctorDetails']);
                Route::delete('/doctors/{id}', [AdminDashboardApiController::class, 'destroyDoctor']);
                Route::post('/doctors/{id}/approve', [AdminDashboardApiController::class, 'approveDoctor']);
                Route::post('/doctors/{id}/reject', [AdminDashboardApiController::class, 'rejectDoctor']);
                Route::post('/doctors/{id}/suspend', [AdminDashboardApiController::class, 'suspendDoctor']);
                Route::post('/doctors/{id}/activate', [AdminDashboardApiController::class, 'activateDoctor']);
                Route::get('/laboratories', [AdminDashboardApiController::class, 'laboratories']);
                Route::get('/laboratories/{id}', [AdminDashboardApiController::class, 'laboratoryDetails']);
                Route::post('/laboratories/{id}/approve', [AdminDashboardApiController::class, 'approveLaboratory']);
                Route::post('/laboratories/{id}/reject', [AdminDashboardApiController::class, 'rejectLaboratory']);
                Route::post('/laboratories/{id}/suspend', [AdminDashboardApiController::class, 'suspendLaboratory']);
                Route::get('/pharmacies', [AdminDashboardApiController::class, 'pharmacies']);
                Route::get('/pharmacies/{id}', [AdminDashboardApiController::class, 'pharmacyDetails']);
                Route::post('/pharmacies/{id}/approve', [AdminDashboardApiController::class, 'approvePharmacy']);
                Route::post('/pharmacies/{id}/reject', [AdminDashboardApiController::class, 'rejectPharmacy']);
                Route::post('/pharmacies/{id}/suspend', [AdminDashboardApiController::class, 'suspendPharmacy']);
                Route::get('/patients', [AdminDashboardApiController::class, 'patients']);
                Route::get('/patients/{id}', [AdminDashboardApiController::class, 'patientDetails']);
                Route::get('/appointments', [AdminDashboardApiController::class, 'appointments']);
                Route::get('/revenue', [AdminDashboardApiController::class, 'revenue']);
                Route::get('/subscriptions', [AdminDashboardApiController::class, 'subscriptions']);
                Route::get('/subscriptions/plans', [AdminDashboardApiController::class, 'subscriptionPlans']);
                Route::post('/subscriptions/plans', [AdminDashboardApiController::class, 'storeSubscriptionPlan']);
                Route::put('/subscriptions/plans/{id}', [AdminDashboardApiController::class, 'updateSubscriptionPlan']);
                Route::delete('/subscriptions/plans/{id}', [AdminDashboardApiController::class, 'deleteSubscriptionPlan']);
                Route::get('/payment-settings', [AdminDashboardApiController::class, 'paymentSettings']);
                Route::put('/payment-settings', [AdminDashboardApiController::class, 'updatePaymentSettings']);
                Route::post('/subscriptions/{id}/confirm', [AdminDashboardApiController::class, 'confirmSubscription']);
                Route::post('/subscriptions/{id}/reject', [AdminDashboardApiController::class, 'rejectSubscription']);
                Route::get('/analytics', [AdminDashboardApiController::class, 'analytics']);
                Route::get('/reviews', [AdminDashboardApiController::class, 'reviews']);
                Route::post('/reviews/{id}/approve', [AdminDashboardApiController::class, 'approveReview']);
                Route::post('/reviews/{id}/reject', [AdminDashboardApiController::class, 'rejectReview']);
                Route::post('/patients/{id}/block', [AdminDashboardApiController::class, 'blockPatient']);
                Route::post('/patients/{id}/unblock', [AdminDashboardApiController::class, 'unblockPatient']);
                Route::delete('/patients/{id}', [AdminDashboardApiController::class, 'deletePatient']);
                Route::post('/patients/{id}/reset-password', [AdminDashboardApiController::class, 'resetPatientPassword']);
                Route::post('/appointments/{id}/confirm', [AdminDashboardApiController::class, 'confirmAppointment']);
                Route::post('/appointments/{id}/cancel', [AdminDashboardApiController::class, 'cancelAppointment']);
                Route::get('/users', [AdminUserController::class, 'index']);
                Route::post('/users/admins', [AdminUserController::class, 'createAdmin']);
                Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
                Route::post('/users/{id}/block', [AdminUserController::class, 'block']);
                Route::post('/users/{id}/unblock', [AdminUserController::class, 'unblock']);

                Route::get('/specialities', [AdminCatalogApiController::class, 'specialities']);
                Route::post('/specialities', [AdminCatalogApiController::class, 'storeSpeciality']);
                Route::put('/specialities/{id}', [AdminCatalogApiController::class, 'updateSpeciality']);
                Route::delete('/specialities/{id}', [AdminCatalogApiController::class, 'destroySpeciality']);
                Route::get('/governorates', [AdminCatalogApiController::class, 'governorates']);
                Route::post('/governorates', [AdminCatalogApiController::class, 'storeGovernorate']);
                Route::put('/governorates/{id}', [AdminCatalogApiController::class, 'updateGovernorate']);
                Route::delete('/governorates/{id}', [AdminCatalogApiController::class, 'destroyGovernorate']);

                Route::get('/lab-test-categories', [AdminLabTestApiController::class, 'categories']);
                Route::post('/lab-test-categories', [AdminLabTestApiController::class, 'storeCategory']);
                Route::put('/lab-test-categories/{id}', [AdminLabTestApiController::class, 'updateCategory']);
                Route::delete('/lab-test-categories/{id}', [AdminLabTestApiController::class, 'destroyCategory']);
                Route::get('/lab-tests', [AdminLabTestApiController::class, 'tests']);
                Route::post('/lab-tests', [AdminLabTestApiController::class, 'storeTest']);
                Route::put('/lab-tests/{id}', [AdminLabTestApiController::class, 'updateTest']);
                Route::delete('/lab-tests/{id}', [AdminLabTestApiController::class, 'destroyTest']);

                Route::get('/medicine-categories', [AdminMedicineApiController::class, 'categories']);
                Route::post('/medicine-categories', [AdminMedicineApiController::class, 'storeCategory']);
                Route::put('/medicine-categories/{id}', [AdminMedicineApiController::class, 'updateCategory']);
                Route::delete('/medicine-categories/{id}', [AdminMedicineApiController::class, 'destroyCategory']);
                Route::get('/medicines', [AdminMedicineApiController::class, 'medicines']);
                Route::post('/medicines', [AdminMedicineApiController::class, 'storeMedicine']);
                Route::put('/medicines/{id}', [AdminMedicineApiController::class, 'updateMedicine']);
                Route::delete('/medicines/{id}', [AdminMedicineApiController::class, 'destroyMedicine']);

                Route::get('/laboratory-orders', [AdminOrdersApiController::class, 'laboratoryOrders']);
                Route::get('/laboratory-orders/{id}', [AdminOrdersApiController::class, 'laboratoryOrderDetails']);
                Route::get('/pharmacy-orders', [AdminOrdersApiController::class, 'pharmacyOrders']);
                Route::get('/pharmacy-orders/{id}', [AdminOrdersApiController::class, 'pharmacyOrderDetails']);
                Route::get('/reports/orders', [AdminOrdersApiController::class, 'ordersReport']);

                Route::get('/support-contacts', [AdminSupportContactApiController::class, 'index']);
                Route::post('/support-contacts', [AdminSupportContactApiController::class, 'store']);
                Route::put('/support-contacts/{id}', [AdminSupportContactApiController::class, 'update']);
                Route::delete('/support-contacts/{id}', [AdminSupportContactApiController::class, 'destroy']);
            });
        });
    });
});
