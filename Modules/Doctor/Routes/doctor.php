<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\Doctor\DoctorDashboardController;
use Modules\Doctor\Http\Controllers\Doctor\DoctorStaffController;
use Modules\Doctor\Http\Controllers\Web\DoctorAuthController;
use Modules\Doctor\Http\Controllers\Web\DoctorBranchController;
use Modules\Doctor\Http\Controllers\Web\DoctorCsrfController;
use Modules\Doctor\Http\Controllers\Web\DoctorDashboardWebController;
use Modules\Doctor\Http\Controllers\Web\DoctorSubscriptionController;
use Modules\Doctor\Http\Controllers\Web\DoctorVerificationController;

Route::middleware(['session.scope:doctor', 'web'])->group(function () {
    Route::redirect('/doctor', '/doctor/login');

    Route::prefix('doctor')->name('doctor.')->group(function () {
        Route::get('/api/csrf-token', [DoctorCsrfController::class, 'token']);

        Route::middleware('guest:web')->group(function () {
            Route::get('/login', [DoctorAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [DoctorAuthController::class, 'login']);
            Route::get('/register', [DoctorAuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [DoctorAuthController::class, 'register']);
        });

        Route::middleware(['auth:web', 'doctor'])->group(function () {
            Route::get('/verify-phone', [DoctorAuthController::class, 'showVerifyPhone'])->name('verify-phone');
            Route::post('/verify-phone/send', [DoctorAuthController::class, 'sendPhoneOtp'])->name('verify-phone.send');
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

                Route::middleware(['doctor.approved', 'doctor.context'])->group(function () {
                    Route::get('/dashboard', [DoctorDashboardWebController::class, 'dashboard'])->name('dashboard');

                    Route::middleware('doctor.permission:calendar.view')->group(function () {
                        Route::get('/dashboard/calendar', [DoctorDashboardWebController::class, 'calendar'])->name('calendar');
                    });

                    Route::middleware('doctor.permission:settings.view')->group(function () {
                        Route::get('/dashboard/settings', [DoctorDashboardWebController::class, 'settings'])->name('settings');
                    });


                    Route::middleware('doctor.owner')->group(function () {
                        Route::get('/dashboard/staff', [DoctorDashboardWebController::class, 'staff'])->name('staff.index');
                        Route::get('/dashboard/subscription/plans', [DoctorDashboardWebController::class, 'subscriptionPlans'])->name('subscription.plans');
                    });

                    Route::middleware('doctor.permission:patients.view')->group(function () {
                        Route::get('/dashboard/patients', [DoctorDashboardWebController::class, 'patients'])->name('patients.index');
                        Route::get('/dashboard/patients/{id}', [DoctorDashboardWebController::class, 'patientShow'])->name('patients.show');
                        Route::get('/dashboard/patients/{id}/records', [DoctorDashboardWebController::class, 'patientRecords'])->name('patients.records');
                    });

                    Route::middleware('doctor.permission:prescriptions.view')->group(function () {
                        Route::get('/dashboard/prescriptions', [DoctorDashboardWebController::class, 'prescriptions'])->name('prescriptions.index');
                        Route::get('/dashboard/prescriptions/create', [DoctorDashboardWebController::class, 'prescriptionCreate'])->name('prescriptions.create');
                        Route::get('/dashboard/prescriptions/{id}/pdf', [DoctorDashboardWebController::class, 'prescriptionPdf'])->name('prescriptions.pdf');
                        Route::get('/dashboard/prescriptions/{id}/print', [DoctorDashboardWebController::class, 'prescriptionPrint'])->name('prescriptions.print');
                        Route::get('/dashboard/prescriptions/{id}', [DoctorDashboardWebController::class, 'prescriptionShow'])->name('prescriptions.show');
                        Route::get('/dashboard/prescriptions/{id}/edit', [DoctorDashboardWebController::class, 'prescriptionEdit'])->name('prescriptions.edit');
                    });

                    Route::middleware('doctor.permission:records.view')->group(function () {
                        Route::get('/dashboard/records', [DoctorDashboardWebController::class, 'records'])->name('records.index');
                        Route::get('/dashboard/records/create', [DoctorDashboardWebController::class, 'recordCreate'])->name('records.create');
                        Route::get('/dashboard/records/{id}/pdf', [DoctorDashboardWebController::class, 'recordPdf'])->name('records.pdf');
                        Route::get('/dashboard/records/{id}/print', [DoctorDashboardWebController::class, 'recordPrint'])->name('records.print');
                        Route::get('/dashboard/records/{id}', [DoctorDashboardWebController::class, 'recordShow'])->name('records.show');
                        Route::get('/dashboard/records/{id}/edit', [DoctorDashboardWebController::class, 'recordEdit'])->name('records.edit');
                    });

                    Route::middleware('doctor.permission:appointments.view')->group(function () {
                        Route::get('/dashboard/requests', [DoctorDashboardWebController::class, 'requests'])->name('requests');
                    });

                    Route::prefix('api')->group(function () {
                        Route::get('/me', [DoctorStaffController::class, 'me']);

                        Route::get('/metrics', [DoctorDashboardController::class, 'metrics']);
                        Route::get('/today-activity', [DoctorDashboardController::class, 'todayActivity']);
                        Route::get('/upcoming-tasks', [DoctorDashboardController::class, 'upcomingTasks']);

                        Route::middleware('doctor.permission:patients.view')->group(function () {
                            Route::get('/patients', [DoctorDashboardController::class, 'patients']);
                            Route::get('/patients/{id}', [DoctorDashboardController::class, 'patientDetails']);
                            Route::get('/patients/{id}/prescriptions', [DoctorDashboardController::class, 'patientPrescriptions']);
                        });

                        Route::post('/ghost-patients', [DoctorDashboardController::class, 'createGhostPatient'])
                            ->middleware('doctor.permission:patients.manage');

                        Route::middleware('doctor.permission:prescriptions.view')->group(function () {
                            Route::get('/prescriptions', [DoctorDashboardController::class, 'prescriptions']);
                            Route::get('/prescriptions/{id}', [DoctorDashboardController::class, 'showPrescription']);
                        });

                        Route::middleware('doctor.permission:prescriptions.manage')->group(function () {
                            Route::get('/referral-options', [DoctorDashboardController::class, 'referralOptions']);
                            Route::post('/prescriptions', [DoctorDashboardController::class, 'storePrescription']);
                            Route::put('/prescriptions/{id}', [DoctorDashboardController::class, 'updatePrescription']);
                            Route::delete('/prescriptions/{id}', [DoctorDashboardController::class, 'destroyPrescription']);
                        });

                        Route::middleware('doctor.permission:records.view')->group(function () {
                            Route::get('/records', [DoctorDashboardController::class, 'records']);
                            Route::get('/records/{id}', [DoctorDashboardController::class, 'showRecord']);
                        });

                        Route::middleware('doctor.permission:records.manage')->group(function () {
                            Route::post('/records', [DoctorDashboardController::class, 'storeRecord']);
                            Route::put('/records/{id}', [DoctorDashboardController::class, 'updateRecord']);
                            Route::delete('/records/{id}', [DoctorDashboardController::class, 'destroyRecord']);
                        });

                        Route::get('/profile', [DoctorDashboardController::class, 'profile']);
                        Route::put('/profile', [DoctorDashboardController::class, 'updateProfile'])
                            ->middleware('doctor.permission:settings.view');
                        Route::post('/profile/avatar', [DoctorDashboardController::class, 'updateProfileWithAvatar'])
                            ->middleware('doctor.permission:settings.view');
                        Route::put('/professional', [DoctorDashboardController::class, 'updateProfessional'])
                            ->middleware('doctor.owner');
                        Route::post('/change-password', [DoctorDashboardController::class, 'changePassword'])
                            ->middleware('doctor.permission:settings.view');

                        Route::middleware('doctor.permission:schedule.manage')->group(function () {
                            Route::get('/schedules', [DoctorDashboardController::class, 'schedules']);
                            Route::post('/schedules', [DoctorDashboardController::class, 'storeSchedule']);
                            Route::put('/schedules/{scheduleId}', [DoctorDashboardController::class, 'updateSchedule']);
                            Route::delete('/schedules/{scheduleId}', [DoctorDashboardController::class, 'deleteSchedule']);
                        });

                        Route::get('/calendar', [DoctorDashboardController::class, 'calendar'])
                            ->middleware('doctor.permission:calendar.view');

                        Route::middleware('doctor.permission:appointments.view')->group(function () {
                            Route::get('/appointments', [DoctorDashboardController::class, 'appointments']);
                            Route::get('/appointments/{appointmentId}', [DoctorDashboardController::class, 'appointmentDetails']);
                        });

                        Route::middleware('doctor.permission:appointments.manage')->group(function () {
                            Route::post('/appointments/{appointmentId}/confirm', [DoctorDashboardController::class, 'confirmAppointment']);
                            Route::post('/appointments/{appointmentId}/reject', [DoctorDashboardController::class, 'rejectAppointment']);
                            Route::post('/appointments/{appointmentId}/complete', [DoctorDashboardController::class, 'completeAppointment']);
                        });

                        Route::get('/notifications/unread', [DoctorDashboardController::class, 'unreadNotifications']);
                        Route::post('/notifications/{notificationId}/read', [DoctorDashboardController::class, 'markNotificationRead']);
                        Route::post('/notifications/read-all', [DoctorDashboardController::class, 'markAllNotificationsRead']);


                        Route::middleware('doctor.owner')->group(function () {
                            Route::get('/subscription', [DoctorDashboardController::class, 'subscription']);
                            Route::get('/subscription/plans', [DoctorSubscriptionController::class, 'plans']);
                            Route::get('/payment-settings', [DoctorSubscriptionController::class, 'paymentSettings']);
                            Route::post('/subscription/subscribe', [DoctorSubscriptionController::class, 'subscribe']);

                            Route::get('/branches', [DoctorBranchController::class, 'index']);
                            Route::post('/branches', [DoctorBranchController::class, 'store']);
                            Route::put('/branches/{branchId}', [DoctorBranchController::class, 'update']);
                            Route::delete('/branches/{branchId}', [DoctorBranchController::class, 'destroy']);

                            Route::get('/staff/permissions', [DoctorStaffController::class, 'permissionsCatalog']);
                            Route::get('/staff', [DoctorStaffController::class, 'index']);
                            Route::post('/staff', [DoctorStaffController::class, 'store']);
                            Route::put('/staff/{memberId}', [DoctorStaffController::class, 'update']);
                            Route::patch('/staff/{memberId}/status', [DoctorStaffController::class, 'updateStatus']);
                            Route::delete('/staff/{memberId}', [DoctorStaffController::class, 'destroy']);
                        });
                    });
                });
            });
        });
    });
});
