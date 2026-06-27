<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Doctor\DoctorDashboardController;
use Modules\Doctor\Http\Controllers\Web\DoctorAuthController;
use Modules\Doctor\Http\Controllers\Web\DoctorBranchController;
use Modules\Doctor\Http\Controllers\Web\DoctorDashboardWebController;
use Modules\Doctor\Http\Controllers\Web\DoctorSubscriptionController;
use Modules\Doctor\Http\Controllers\Web\DoctorVerificationController;

Route::middleware(['session.scope:doctor', 'web'])->group(function () {
    Route::redirect('/', '/doctor/login');

    Route::prefix('doctor')->name('doctor.')->group(function () {
        Route::middleware('guest:web')->group(function () {
            Route::get('/login', [DoctorAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [DoctorAuthController::class, 'login']);
            Route::get('/register', [DoctorAuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [DoctorAuthController::class, 'register']);
        });

        Route::middleware(['auth:web', 'doctor'])->group(function () {
            Route::get('/api/csrf-token', fn () => response()->json(['token' => csrf_token()]));

            Route::get('/verify-phone', [DoctorAuthController::class, 'showVerifyPhone'])->name('verify-phone');
            Route::post('/verify-phone', [DoctorAuthController::class, 'verifyPhone'])->name('verify-phone.submit');
            Route::get('/verify-email', [DoctorAuthController::class, 'showVerifyEmail'])->name('verify-email');
            Route::post('/verify-email', [DoctorAuthController::class, 'verifyEmail'])->name('verify-email.submit');
            Route::post('/verify-email/resend', [DoctorAuthController::class, 'resendVerificationOtp'])->name('verify-email.resend');
            Route::post('/logout', [DoctorAuthController::class, 'logout'])->name('logout');

            Route::middleware('doctor.phone.verified')->group(function () {
            Route::get('/pending', [DoctorVerificationController::class, 'pending'])->name('pending');
            Route::get('/rejected', [DoctorVerificationController::class, 'rejected'])->name('rejected');
            Route::get('/suspended', [DoctorVerificationController::class, 'suspended'])->name('suspended');
            Route::post('/resubmit-documents', [DoctorVerificationController::class, 'resubmit'])->name('resubmit');

            Route::middleware('doctor.approved')->group(function () {
                Route::get('/dashboard', [DoctorDashboardWebController::class, 'dashboard'])->name('dashboard');
                Route::get('/dashboard/calendar', [DoctorDashboardWebController::class, 'calendar'])->name('calendar');
                Route::get('/dashboard/settings', [DoctorDashboardWebController::class, 'settings'])->name('settings');
                Route::get('/dashboard/patients', [DoctorDashboardWebController::class, 'patients'])->name('patients.index');
                Route::get('/dashboard/patients/{id}', [DoctorDashboardWebController::class, 'patientShow'])->name('patients.show');
                Route::get('/dashboard/patients/{id}/records', [DoctorDashboardWebController::class, 'patientRecords'])->name('patients.records');
                Route::get('/dashboard/prescriptions', [DoctorDashboardWebController::class, 'prescriptions'])->name('prescriptions.index');
                Route::get('/dashboard/prescriptions/create', [DoctorDashboardWebController::class, 'prescriptionCreate'])->name('prescriptions.create');
                Route::get('/dashboard/prescriptions/{id}', [DoctorDashboardWebController::class, 'prescriptionShow'])->name('prescriptions.show');
                Route::get('/dashboard/prescriptions/{id}/edit', [DoctorDashboardWebController::class, 'prescriptionEdit'])->name('prescriptions.edit');
                Route::get('/dashboard/records', [DoctorDashboardWebController::class, 'records'])->name('records.index');
                Route::get('/dashboard/records/create', [DoctorDashboardWebController::class, 'recordCreate'])->name('records.create');
                Route::get('/dashboard/records/{id}', [DoctorDashboardWebController::class, 'recordShow'])->name('records.show');
                Route::get('/dashboard/records/{id}/edit', [DoctorDashboardWebController::class, 'recordEdit'])->name('records.edit');
                Route::get('/dashboard/subscription/plans', [DoctorDashboardWebController::class, 'subscriptionPlans'])->name('subscription.plans');
                Route::get('/dashboard/requests', [DoctorDashboardWebController::class, 'requests'])->name('requests');

                Route::prefix('api')->group(function () {
                    Route::get('/metrics', [DoctorDashboardController::class, 'metrics']);
                    Route::get('/today-activity', [DoctorDashboardController::class, 'todayActivity']);
                    Route::get('/upcoming-tasks', [DoctorDashboardController::class, 'upcomingTasks']);

                    Route::get('/patients', [DoctorDashboardController::class, 'patients']);
                    Route::get('/patients/{id}', [DoctorDashboardController::class, 'patientDetails']);
                    Route::get('/patients/{id}/prescriptions', [DoctorDashboardController::class, 'patientPrescriptions']);
                    Route::post('/ghost-patients', [DoctorDashboardController::class, 'createGhostPatient']);

                    Route::get('/prescriptions', [DoctorDashboardController::class, 'prescriptions']);
                    Route::post('/prescriptions', [DoctorDashboardController::class, 'storePrescription']);
                    Route::get('/prescriptions/{id}', [DoctorDashboardController::class, 'showPrescription']);
                    Route::put('/prescriptions/{id}', [DoctorDashboardController::class, 'updatePrescription']);
                    Route::delete('/prescriptions/{id}', [DoctorDashboardController::class, 'destroyPrescription']);

                    Route::get('/records', [DoctorDashboardController::class, 'records']);
                    Route::post('/records', [DoctorDashboardController::class, 'storeRecord']);
                    Route::get('/records/{id}', [DoctorDashboardController::class, 'showRecord']);
                    Route::put('/records/{id}', [DoctorDashboardController::class, 'updateRecord']);
                    Route::delete('/records/{id}', [DoctorDashboardController::class, 'destroyRecord']);

                    Route::get('/profile', [DoctorDashboardController::class, 'profile']);
                    Route::put('/profile', [DoctorDashboardController::class, 'updateProfile']);
                    Route::put('/professional', [DoctorDashboardController::class, 'updateProfessional']);

                    Route::get('/schedules', [DoctorDashboardController::class, 'schedules']);
                    Route::delete('/schedules/{scheduleId}', [DoctorDashboardController::class, 'deleteSchedule']);

                    Route::get('/calendar', [DoctorDashboardController::class, 'calendar']);
                    Route::get('/appointments', [DoctorDashboardController::class, 'appointments']);
                    Route::get('/appointments/{appointmentId}', [DoctorDashboardController::class, 'appointmentDetails']);
                    Route::post('/appointments/{appointmentId}/confirm', [DoctorDashboardController::class, 'confirmAppointment']);
                    Route::post('/appointments/{appointmentId}/reject', [DoctorDashboardController::class, 'rejectAppointment']);
                    Route::post('/appointments/{appointmentId}/complete', [DoctorDashboardController::class, 'completeAppointment']);
                    Route::get('/notifications/unread', [DoctorDashboardController::class, 'unreadNotifications']);
                    Route::post('/notifications/{notificationId}/read', [DoctorDashboardController::class, 'markNotificationRead']);
                    Route::post('/notifications/read-all', [DoctorDashboardController::class, 'markAllNotificationsRead']);

                    Route::get('/subscription', [DoctorDashboardController::class, 'subscription']);
                    Route::get('/subscription/plans', [DoctorSubscriptionController::class, 'plans']);
                    Route::get('/payment-settings', [DoctorSubscriptionController::class, 'paymentSettings']);
                    Route::post('/subscription/subscribe', [DoctorSubscriptionController::class, 'subscribe']);
                    Route::post('/change-password', [DoctorDashboardController::class, 'changePassword']);

                    Route::get('/branches', [DoctorBranchController::class, 'index']);
                    Route::post('/branches', [DoctorBranchController::class, 'store']);
                    Route::put('/branches/{branchId}', [DoctorBranchController::class, 'update']);
                    Route::delete('/branches/{branchId}', [DoctorBranchController::class, 'destroy']);
                });
            });
            });
        });
    });
});
