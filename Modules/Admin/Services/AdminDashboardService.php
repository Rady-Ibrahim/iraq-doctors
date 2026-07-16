<?php

namespace Modules\Admin\Services;

use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorBranch;
use Modules\Doctor\Models\Speciality;
use Modules\Auth\Models\User;
use Modules\Appointment\Models\Appointment;
use Modules\Subscription\Models\DoctorSubscription;
use Modules\Subscription\Models\Subscription;
use Modules\Laboratory\Models\LaboratorySubscription;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacySubscription;
use Modules\Review\Models\Review;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratoryOrder;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Pharmacy\Models\PharmacyOrder;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AdminDashboardService
{
    public function getSystemMetrics()
    {
        $totalDoctors = Doctor::count();
        $activeDoctors = Doctor::where('status', 'approved')->count();
        $pendingDoctors = Doctor::where('status', 'pending')->count();
        $suspendedDoctors = Doctor::where('status', 'suspended')->count();
        $pendingLaboratories = Laboratory::where('status', 'pending')->count();
        $pendingPharmacies = Pharmacy::where('status', 'pending')->count();

        $totalPatients = User::where('role', 'patient')->where('status', 'active')->count();
        $ghostPatients = User::where('role', 'patient')->where('is_ghost', true)->count();
        $realPatients = $totalPatients - $ghostPatients;

        $totalAppointments = Appointment::count();
        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
        $completedAppointments = Appointment::where('status', 'completed')->count();
        $pendingAppointments = Appointment::where('status', 'pending')->count();
        $cancelledAppointments = Appointment::where('status', 'cancelled')->count();

        $totalRevenue = DoctorSubscription::sum('amount_paid');
        $monthlyRevenue = DoctorSubscription::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');

        $lastMonthRevenue = DoctorSubscription::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount_paid');
        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        $avgRating = Review::approved()->avg('rating') ?? 0;
        $totalReviews = Review::approved()->count();
        $pendingReviews = Review::pending()->count();

        $activeSubscriptions = DoctorSubscription::where('status', 'active')->count();
        $expiredSubscriptions = DoctorSubscription::where('status', 'expired')->count();

        return [
            'doctors' => [
                'total' => $totalDoctors,
                'active' => $activeDoctors,
                'pending' => $pendingDoctors,
                'suspended' => $suspendedDoctors,
            ],
            'patients' => [
                'total' => $totalPatients,
                'real' => $realPatients,
                'ghost' => $ghostPatients,
            ],
            'appointments' => [
                'total' => $totalAppointments,
                'today' => $todayAppointments,
                'completed' => $completedAppointments,
                'pending' => $pendingAppointments,
                'cancelled' => $cancelledAppointments,
            ],
            'revenue' => [
                'total' => $totalRevenue,
                'monthly' => $monthlyRevenue,
                'growth' => $revenueGrowth,
            ],
            'reviews' => [
                'average' => round($avgRating, 2),
                'total' => $totalReviews,
                'pending' => $pendingReviews,
            ],
            'subscriptions' => [
                'active' => $activeSubscriptions,
                'expired' => $expiredSubscriptions,
            ],
            'laboratories' => [
                'total' => Laboratory::count(),
                'pending' => $pendingLaboratories,
            ],
            'pharmacies' => [
                'total' => Pharmacy::count(),
                'pending' => $pendingPharmacies,
            ],
            'laboratory_orders' => [
                'total' => LaboratoryOrder::count(),
                'active' => LaboratoryOrder::whereNotIn('status', [
                    LaboratoryOrderStatus::Delivered->value,
                    LaboratoryOrderStatus::Cancelled->value,
                ])->count(),
                'delivered' => LaboratoryOrder::where('status', LaboratoryOrderStatus::Delivered)->count(),
            ],
            'pharmacy_orders' => [
                'total' => PharmacyOrder::count(),
                'active' => PharmacyOrder::whereNotIn('status', [
                    PharmacyOrderStatus::Completed->value,
                    PharmacyOrderStatus::Cancelled->value,
                ])->count(),
                'completed' => PharmacyOrder::where('status', PharmacyOrderStatus::Completed)->count(),
            ],
        ];
    }

    public function getDoctorsStats($filters = [])
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = Doctor::with(['user', 'speciality', 'subscription']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['speciality_id'])) {
            $query->where('speciality_id', $filters['speciality_id']);
        }

        if (isset($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }

        $doctors = $query->orderBy('created_at', 'desc')->paginate($limit);

        return $doctors;
    }

    public function getDoctorDetails(int $doctorId): array
    {
        $doctor = Doctor::with(['user', 'speciality', 'branches', 'schedules'])->findOrFail($doctorId);

        $totalAppointments = Appointment::where('doctor_id', $doctorId)->count();
        $completedAppointments = Appointment::where('doctor_id', $doctorId)->where('status', 'completed')->count();
        $totalPatients = Appointment::where('doctor_id', $doctorId)->distinct('patient_id')->count('patient_id');
        $totalReviews = Review::where('doctor_id', $doctorId)->approved()->count();

        $recentReviews = Review::where('doctor_id', $doctorId)
            ->approved()
            ->with('patient')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'patient_name' => $review->patient?->name,
                'created_at' => $review->created_at,
            ])
            ->values()
            ->all();

        return [
            'id' => $doctor->id,
            'name' => $doctor->user?->name,
            'phone' => $doctor->user?->phone,
            'email' => $doctor->user?->email,
            'speciality' => $doctor->speciality?->name_ar,
            'speciality_id' => $doctor->speciality_id,
            'status' => $doctor->status,
            'experience_years' => $doctor->experience_years,
            'consultation_fee' => $doctor->consultation_fee,
            'rating' => $doctor->rating,
            'bio' => $doctor->bio_ar ?: $doctor->bio_en,
            'address' => $doctor->address,
            'reject_reason' => $doctor->reject_reason,
            'license_document' => storage_public_url($doctor->license_document),
            'clinic_image' => storage_public_url($doctor->clinic_image),
            'created_at' => $doctor->created_at,
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'total_patients' => $totalPatients,
            'total_reviews' => $totalReviews,
            'branches' => $doctor->branches->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->branch_name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'governorate' => $branch->governorate,
                'is_primary' => $branch->is_primary,
            ])->values()->all(),
            'schedules' => $doctor->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ])->values()->all(),
            'recent_reviews' => $recentReviews,
        ];
    }

    public function deleteDoctor(int $doctorId): void
    {
        $doctor = Doctor::findOrFail($doctorId);
        $doctor->user?->delete();
        $doctor->delete();
    }

    public function getPatientsStats($filters = [])
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = User::where('role', 'patient')->withCount('appointments as total_appointments');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_ghost'])) {
            if ($filters['is_ghost'] === 'ghost') {
                $query->where('is_ghost', true);
            } elseif ($filters['is_ghost'] === 'regular') {
                $query->where('is_ghost', false);
            } else {
                $query->where('is_ghost', filter_var($filters['is_ghost'], FILTER_VALIDATE_BOOLEAN));
            }
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate($limit);

        return $patients;
    }

    public function getAppointmentsStats($filters = [])
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = Appointment::with(['doctor.user', 'doctor.speciality', 'patient']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('appointment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('appointment_date', '<=', $filters['date_to']);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->paginate($limit);
        $appointments->getCollection()->transform(fn ($appointment) => $this->formatAppointment($appointment));

        return $appointments;
    }

    public function getRevenueDashboardData(array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $subscriptionQuery = DoctorSubscription::with(['doctor.user', 'subscription'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalRevenue = (clone $subscriptionQuery)->sum('amount_paid');
        $transactionCount = (clone $subscriptionQuery)->count();
        $completedAppointments = Appointment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $recentTransactions = (clone $subscriptionQuery)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'date' => $item->created_at?->format('Y-m-d'),
                'type' => 'subscription',
                'description' => 'اشتراك: ' . ($item->doctor?->user?->name ?? 'طبيب'),
                'amount' => $item->amount_paid,
            ])
            ->values()
            ->all();

        $topPerformers = Doctor::with('user')
            ->withCount(['appointments as appointments_count' => fn ($q) => $q->whereBetween('appointments.created_at', [$startDate, $endDate])])
            ->orderByDesc('appointments_count')
            ->limit(5)
            ->get()
            ->map(fn ($doctor) => [
                'name' => $doctor->user?->name,
                'appointments' => $doctor->appointments_count,
                'revenue' => DoctorSubscription::where('doctor_id', $doctor->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount_paid'),
            ])
            ->values()
            ->all();

        $subscriptionRevenue = $totalRevenue;
        $appointmentRevenue = Appointment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('price');

        $totalCombined = $subscriptionRevenue + $appointmentRevenue;

        return [
            'total_revenue' => $totalCombined,
            'completed_appointments' => $completedAppointments,
            'subscription_revenue' => $subscriptionRevenue,
            'average_revenue' => $transactionCount > 0 ? round($totalRevenue / $transactionCount, 2) : 0,
            'revenue_by_category' => [
                ['name' => 'اشتراكات الأطباء', 'amount' => $subscriptionRevenue, 'percentage' => $totalCombined > 0 ? round(($subscriptionRevenue / $totalCombined) * 100) : 0],
                ['name' => 'مواعيد مكتملة', 'amount' => $appointmentRevenue, 'percentage' => $totalCombined > 0 ? round(($appointmentRevenue / $totalCombined) * 100) : 0],
            ],
            'top_performers' => $topPerformers,
            'recent_transactions' => $recentTransactions,
        ];
    }

    public function getSubscriptionsDashboardData(array $filters = []): array
    {
        $limit = (int) ($filters['limit'] ?? 20);
        $status = $filters['status'] ?? null;

        $plans = Subscription::orderBy('sort_order')->get()->map(fn ($plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'type' => $plan->type ?? 'doctor',
            'price' => $plan->price,
            'duration_days' => $plan->duration_days,
            'status' => $plan->status,
            'subscribers_count' => match ($plan->type ?? 'doctor') {
                'laboratory' => LaboratorySubscription::where('subscription_id', $plan->id)->where('status', 'active')->count(),
                'pharmacy' => PharmacySubscription::where('subscription_id', $plan->id)->where('status', 'active')->count(),
                default => DoctorSubscription::where('subscription_id', $plan->id)->where('status', 'active')->count(),
            },
        ])->values()->all();

        $doctorQuery = DoctorSubscription::with(['doctor.user', 'subscription'])->orderByDesc('created_at');
        if ($status) {
            $doctorQuery->where('status', $status);
        }
        $doctorSubs = $doctorQuery->get()->map(fn ($sub) => [
            'id' => $sub->id,
            'subscriber_type' => 'doctor',
            'subscriber_name' => $sub->doctor?->user?->name,
            'doctor_name' => $sub->doctor?->user?->name,
            'plan_name' => $sub->subscription?->name,
            'amount_paid' => $sub->amount_paid,
            'submitted_amount' => $sub->submitted_amount,
            'payment_method' => $sub->payment_method,
            'payment_receipt' => storage_public_url($sub->payment_receipt),
            'payment_reject_reason' => $sub->payment_reject_reason,
            'status' => $sub->status,
            'start_date' => $sub->start_date?->format('Y-m-d'),
            'end_date' => $sub->end_date?->format('Y-m-d'),
            'created_at' => $sub->created_at?->format('Y-m-d'),
        ]);

        $labQuery = LaboratorySubscription::with(['laboratory.user', 'subscription'])->orderByDesc('created_at');
        if ($status) {
            $labQuery->where('status', $status);
        }
        $labSubs = $labQuery->get()->map(fn ($sub) => [
            'id' => $sub->id,
            'subscriber_type' => 'laboratory',
            'subscriber_name' => $sub->laboratory?->name,
            'doctor_name' => $sub->laboratory?->name,
            'plan_name' => $sub->subscription?->name,
            'amount_paid' => $sub->amount_paid,
            'submitted_amount' => $sub->submitted_amount,
            'payment_method' => $sub->payment_method,
            'payment_receipt' => storage_public_url($sub->payment_receipt),
            'payment_reject_reason' => $sub->payment_reject_reason,
            'status' => $sub->status,
            'start_date' => $sub->start_date?->format('Y-m-d'),
            'end_date' => $sub->end_date?->format('Y-m-d'),
            'created_at' => $sub->created_at?->format('Y-m-d'),
        ]);

        $pharmacyQuery = PharmacySubscription::with(['pharmacy.user', 'subscription'])->orderByDesc('created_at');
        if ($status) {
            $pharmacyQuery->where('status', $status);
        }
        $pharmacySubs = $pharmacyQuery->get()->map(fn ($sub) => [
            'id' => $sub->id,
            'subscriber_type' => 'pharmacy',
            'subscriber_name' => $sub->pharmacy?->name,
            'doctor_name' => $sub->pharmacy?->name,
            'plan_name' => $sub->subscription?->name,
            'amount_paid' => $sub->amount_paid,
            'submitted_amount' => $sub->submitted_amount,
            'payment_method' => $sub->payment_method,
            'payment_receipt' => storage_public_url($sub->payment_receipt),
            'payment_reject_reason' => $sub->payment_reject_reason,
            'status' => $sub->status,
            'start_date' => $sub->start_date?->format('Y-m-d'),
            'end_date' => $sub->end_date?->format('Y-m-d'),
            'created_at' => $sub->created_at?->format('Y-m-d'),
        ]);

        $merged = $doctorSubs->concat($labSubs)->concat($pharmacySubs)->sortByDesc('created_at')->values();
        $total = $merged->count();
        $page = (int) ($filters['page'] ?? 1);
        $subscriptions = $merged->slice(($page - 1) * $limit, $limit)->values()->all();

        return [
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $limit)),
            ],
            'summary' => [
                'active' => DoctorSubscription::where('status', 'active')->count()
                    + LaboratorySubscription::where('status', 'active')->count()
                    + PharmacySubscription::where('status', 'active')->count(),
                'expired' => DoctorSubscription::where('status', 'expired')->count()
                    + LaboratorySubscription::where('status', 'expired')->count()
                    + PharmacySubscription::where('status', 'expired')->count(),
                'pending_payment' => DoctorSubscription::where('status', 'pending_payment')->count()
                    + LaboratorySubscription::where('status', 'pending_payment')->count()
                    + PharmacySubscription::where('status', 'pending_payment')->count(),
                'total_revenue' => DoctorSubscription::sum('amount_paid')
                    + LaboratorySubscription::sum('amount_paid')
                    + PharmacySubscription::sum('amount_paid'),
            ],
        ];
    }

    public function getAnalyticsData($period = '30days', $type = null)
    {
        $startDate = $this->resolvePeriodStart($period);

        $dailyUsers = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyAppointments = Appointment::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyRevenue = DoctorSubscription::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount_paid) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalUsers = User::count();
        $activeDoctors = Doctor::where('status', 'approved')->count();
        $dailyAppointmentsCount = Appointment::whereDate('appointment_date', today())->count();
        $totalAppointments = Appointment::count();
        $conversionRate = $totalUsers > 0 ? round(($totalAppointments / $totalUsers) * 100, 1) : 0;

        $specialities = Speciality::withCount('doctors')->orderByDesc('doctors_count')->limit(5)->get();
        $totalDoctorsForSpecialities = max($specialities->sum('doctors_count'), 1);

        $topSpecialities = $specialities->map(fn ($speciality) => [
            'name' => $speciality->name_ar,
            'count' => $speciality->doctors_count,
            'percentage' => round(($speciality->doctors_count / $totalDoctorsForSpecialities) * 100),
        ])->values()->all();

        $maleCount = User::where('role', 'patient')->where('gender', 'male')->count();
        $femaleCount = User::where('role', 'patient')->where('gender', 'female')->count();
        $patientTotal = max($maleCount + $femaleCount, 1);

        $peakHoursRaw = Appointment::where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(appointment_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $peakTotal = max($peakHoursRaw->sum('count'), 1);
        $peakHours = $peakHoursRaw->map(function ($row) use ($peakTotal) {
            $hour = (int) $row->hour;
            $nextHour = ($hour + 1) % 24;

            return [
                'time_range' => sprintf('%02d:00 - %02d:00', $hour, $nextHour),
                'count' => $row->count,
                'percentage' => round(($row->count / $peakTotal) * 100),
            ];
        })->values()->all();

        $geoRaw = DoctorBranch::selectRaw('governorate, COUNT(*) as count')
            ->whereNotNull('governorate')
            ->groupBy('governorate')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        $geoTotal = max($geoRaw->sum('count'), 1);
        $geographicDistribution = $geoRaw->map(fn ($row) => [
            'name' => $row->governorate,
            'count' => $row->count,
            'percentage' => round(($row->count / $geoTotal) * 100),
        ])->values()->all();

        return [
            'total_users' => $totalUsers,
            'active_doctors' => $activeDoctors,
            'daily_appointments' => $dailyAppointmentsCount,
            'conversion_rate' => $conversionRate,
            'top_specialities' => $topSpecialities,
            'demographics' => [
                'male' => $maleCount,
                'female' => $femaleCount,
                'age_18_25' => 0,
                'age_26_35' => 0,
                'age_36_45' => 0,
                'age_46_plus' => 0,
            ],
            'peak_hours' => $peakHours,
            'geographic_distribution' => $geographicDistribution,
            'users' => $dailyUsers,
            'appointments' => $dailyAppointments,
            'revenue' => $dailyRevenue,
            'type' => $type,
        ];
    }

    public function approveDoctor($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);
        $doctor->status = 'approved';
        $doctor->save();
        return $doctor;
    }

    public function rejectDoctor($doctorId, ?string $reason = null)
    {
        $doctor = Doctor::findOrFail($doctorId);
        $doctor->status = 'rejected';
        $doctor->reject_reason = $reason;
        $doctor->save();
        return $doctor;
    }

    public function suspendDoctor($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);
        $doctor->status = 'suspended';
        $doctor->save();
        return $doctor;
    }

    public function activateDoctor($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);
        $doctor->status = 'approved';
        $doctor->save();
        return $doctor;
    }

    public function getLaboratoriesStats($filters = [])
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = Laboratory::with(['user', 'governorate']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->orderByDesc('created_at')->paginate($limit);
    }

    public function getLaboratoryDetails(int $laboratoryId): array
    {
        $laboratory = Laboratory::with(['user', 'governorate'])->findOrFail($laboratoryId);

        return [
            'id' => $laboratory->id,
            'name' => $laboratory->name,
            'owner_name' => $laboratory->user?->name,
            'phone' => $laboratory->user?->phone,
            'email' => $laboratory->user?->email,
            'governorate' => $laboratory->governorate?->name_ar,
            'district' => $laboratory->district,
            'address' => $laboratory->address,
            'latitude' => $laboratory->latitude,
            'longitude' => $laboratory->longitude,
            'description_ar' => $laboratory->description_ar,
            'status' => $laboratory->status,
            'reject_reason' => $laboratory->reject_reason,
            'logo' => storage_public_url($laboratory->logo),
            'commercial_register_document' => storage_public_url($laboratory->commercial_register_document),
            'license_document' => storage_public_url($laboratory->license_document),
            'owner_id_document' => storage_public_url($laboratory->owner_id_document),
            'accreditation_document' => storage_public_url($laboratory->accreditation_document),
            'created_at' => $laboratory->created_at,
        ];
    }

    public function approveLaboratory($laboratoryId)
    {
        $laboratory = Laboratory::findOrFail($laboratoryId);
        $laboratory->status = 'approved';
        $laboratory->reject_reason = null;
        $laboratory->save();

        return $laboratory;
    }

    public function rejectLaboratory($laboratoryId, ?string $reason = null)
    {
        $laboratory = Laboratory::findOrFail($laboratoryId);
        $laboratory->status = 'rejected';
        $laboratory->reject_reason = $reason;
        $laboratory->save();

        return $laboratory;
    }

    public function suspendLaboratory($laboratoryId)
    {
        $laboratory = Laboratory::findOrFail($laboratoryId);
        $laboratory->status = 'suspended';
        $laboratory->save();

        return $laboratory;
    }

    public function getPharmaciesStats($filters = [])
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = Pharmacy::with(['user', 'governorate']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->orderByDesc('created_at')->paginate($limit);
    }

    public function getPharmacyDetails(int $pharmacyId): array
    {
        $pharmacy = Pharmacy::with(['user', 'governorate'])->findOrFail($pharmacyId);

        return [
            'id' => $pharmacy->id,
            'name' => $pharmacy->name,
            'owner_name' => $pharmacy->user?->name,
            'phone' => $pharmacy->user?->phone,
            'email' => $pharmacy->user?->email,
            'governorate' => $pharmacy->governorate?->name_ar,
            'district' => $pharmacy->district,
            'address' => $pharmacy->address,
            'latitude' => $pharmacy->latitude,
            'longitude' => $pharmacy->longitude,
            'description_ar' => $pharmacy->description_ar,
            'status' => $pharmacy->status,
            'reject_reason' => $pharmacy->reject_reason,
            'logo' => storage_public_url($pharmacy->logo),
            'commercial_register_document' => storage_public_url($pharmacy->commercial_register_document),
            'license_document' => storage_public_url($pharmacy->license_document),
            'owner_id_document' => storage_public_url($pharmacy->owner_id_document),
            'created_at' => $pharmacy->created_at,
        ];
    }

    public function approvePharmacy($pharmacyId)
    {
        $pharmacy = Pharmacy::findOrFail($pharmacyId);
        $pharmacy->status = 'approved';
        $pharmacy->reject_reason = null;
        $pharmacy->save();

        return $pharmacy;
    }

    public function rejectPharmacy($pharmacyId, ?string $reason = null)
    {
        $pharmacy = Pharmacy::findOrFail($pharmacyId);
        $pharmacy->status = 'rejected';
        $pharmacy->reject_reason = $reason;
        $pharmacy->save();

        return $pharmacy;
    }

    public function suspendPharmacy($pharmacyId)
    {
        $pharmacy = Pharmacy::findOrFail($pharmacyId);
        $pharmacy->status = 'suspended';
        $pharmacy->save();

        return $pharmacy;
    }

    protected function formatAppointment(Appointment $appointment): array
    {
        $time = $appointment->appointment_time;

        if ($time instanceof Carbon) {
            $formattedTime = $time->format('H:i');
        } elseif ($time) {
            $formattedTime = Carbon::parse($time)->format('H:i');
        } else {
            $formattedTime = '-';
        }

        return [
            'id' => $appointment->id,
            'patient_name' => $appointment->patient?->name,
            'patient_phone' => $appointment->patient?->phone,
            'doctor_name' => $appointment->doctor?->user?->name,
            'speciality' => $appointment->doctor?->speciality?->name_ar,
            'appointment_date' => $appointment->appointment_date?->format('Y-m-d'),
            'date' => $appointment->appointment_date?->format('Y-m-d'),
            'appointment_time' => $formattedTime,
            'price' => $appointment->price,
            'status' => $appointment->status,
        ];
    }

    protected function resolvePeriodStart(string $period): Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            'week', '7days' => now()->subDays(7),
            'month', '30days' => now()->subDays(30),
            'year', '90days' => now()->subDays(90),
            '1year' => now()->subYear(),
            default => now()->subDays(30),
        };
    }

    protected function resolveDateRange(array $filters): array
    {
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            return [
                Carbon::parse($filters['date_from'] ?? now()->subMonth())->startOfDay(),
                Carbon::parse($filters['date_to'] ?? now())->endOfDay(),
            ];
        }

        $start = match ($filters['period'] ?? 'month') {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek()->startOfDay(),
            'month' => now()->subMonth()->startOfDay(),
            'year' => now()->subYear()->startOfDay(),
            default => now()->subMonth()->startOfDay(),
        };

        return [$start, now()->endOfDay()];
    }

    public function getReviews(array $filters = [])
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = Review::with(['patient', 'doctor.user', 'doctor.speciality', 'pharmacy', 'laboratory', 'reviewer']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('doctor.user', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('pharmacy', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('laboratory', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($limit);
    }

    public function formatReview(Review $review): array
    {
        $providerType = 'doctor';
        $providerName = $review->doctor?->user?->name;
        $providerSubtitle = $review->doctor?->speciality?->name_ar;

        if ($review->pharmacy_id) {
            $providerType = 'pharmacy';
            $providerName = $review->pharmacy?->name;
            $providerSubtitle = 'صيدلية';
        } elseif ($review->laboratory_id) {
            $providerType = 'laboratory';
            $providerName = $review->laboratory?->name;
            $providerSubtitle = 'مختبر تحاليل';
        }

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status,
            'reject_reason' => $review->reject_reason,
            'patient_name' => $review->patient?->name,
            'patient_phone' => $review->patient?->phone,
            'provider_type' => $providerType,
            'provider_name' => $providerName,
            'provider_subtitle' => $providerSubtitle,
            'doctor_id' => $review->doctor_id,
            'doctor_name' => $review->doctor?->user?->name,
            'pharmacy_id' => $review->pharmacy_id,
            'pharmacy_name' => $review->pharmacy?->name,
            'laboratory_id' => $review->laboratory_id,
            'laboratory_name' => $review->laboratory?->name,
            'speciality' => $review->doctor?->speciality?->name_ar,
            'reviewed_by' => $review->reviewer?->name,
            'reviewed_at' => $review->reviewed_at?->format('Y-m-d H:i'),
            'created_at' => $review->created_at?->format('Y-m-d H:i'),
        ];
    }
}
