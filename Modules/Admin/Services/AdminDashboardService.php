<?php

namespace Modules\Admin\Services;

use Modules\Doctor\Models\Doctor;
use Modules\Auth\Models\User;
use Modules\Appointment\Models\Appointment;
use Modules\Subscription\Models\DoctorSubscription;
use Modules\Subscription\Models\Subscription;
use Modules\Review\Models\Review;
use Carbon\Carbon;

class AdminDashboardService
{
    public function getSystemMetrics()
    {
        $totalDoctors = Doctor::count();
        $activeDoctors = Doctor::where('status', 'approved')->count();
        $pendingDoctors = Doctor::where('status', 'pending')->count();
        $suspendedDoctors = Doctor::where('status', 'suspended')->count();

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

        $avgRating = Review::avg('rating');
        $totalReviews = Review::count();

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
            ],
            'reviews' => [
                'average' => round($avgRating, 2),
                'total' => $totalReviews,
            ],
            'subscriptions' => [
                'active' => $activeSubscriptions,
                'expired' => $expiredSubscriptions,
            ],
        ];
    }

    public function getDoctorsStats($filters = [])
    {
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

        $doctors = $query->orderBy('created_at', 'desc')->paginate(20);

        return $doctors;
    }

    public function getPatientsStats($filters = [])
    {
        $query = User::where('role', 'patient');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_ghost'])) {
            $query->where('is_ghost', $filters['is_ghost']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate(20);

        return $patients;
    }

    public function getAppointmentsStats($filters = [])
    {
        $query = Appointment::with(['doctor.user', 'patient']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('appointment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('appointment_date', '<=', $filters['date_to']);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->paginate(20);

        return $appointments;
    }

    public function getRevenueStats($filters = [])
    {
        $query = DoctorSubscription::with(['doctor.user', 'subscription']);

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $revenues = $query->orderBy('created_at', 'desc')->paginate(20);

        return $revenues;
    }

    public function getAnalyticsData($period = '30days')
    {
        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            '1year' => now()->subYear(),
            default => now()->subDays(30),
        };

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

        return [
            'users' => $dailyUsers,
            'appointments' => $dailyAppointments,
            'revenue' => $dailyRevenue,
        ];
    }

    public function approveDoctor($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);
        $doctor->status = 'approved';
        $doctor->save();
        return $doctor;
    }

    public function rejectDoctor($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);
        $doctor->status = 'rejected';
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
}
