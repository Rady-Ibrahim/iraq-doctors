<?php

namespace Modules\Doctor\Services;

use Modules\Doctor\Models\Doctor;
use Modules\Appointment\Models\Appointment;
use Modules\Auth\Models\User;
use Modules\MedicalRecord\Models\MedicalRecord;
use Modules\Review\Models\Review;
use Carbon\Carbon;

class DoctorDashboardService
{
    public function getMetrics($doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);

        // Total Patients (unique patients who had appointments)
        $totalPatients = Appointment::where('doctor_id', $doctorId)
            ->distinct('patient_id')
            ->count('patient_id');

        // Previous month patients for growth calculation
        $previousMonthPatients = Appointment::where('doctor_id', $doctorId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->distinct('patient_id')
            ->count('patient_id');

        $patientsGrowth = $previousMonthPatients > 0 
            ? (($totalPatients - $previousMonthPatients) / $previousMonthPatients) * 100 
            : 0;

        // Today's Appointments
        $todayAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->count();

        // Issued Prescriptions (Medical Records with type prescription)
        $issuedPrescriptions = MedicalRecord::whereHas('appointment', function ($query) use ($doctorId) {
            $query->where('doctor_id', $doctorId);
        })->where('type', 'prescription')->count();

        // Review Growth
        $totalReviews = Review::where('doctor_id', $doctorId)->count();
        $previousMonthReviews = Review::where('doctor_id', $doctorId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $reviewGrowth = $previousMonthReviews > 0 
            ? (($totalReviews - $previousMonthReviews) / $previousMonthReviews) * 100 
            : 0;

        // Average Rating
        $avgRating = $doctor->rating;

        return [
            'total_patients' => [
                'count' => $totalPatients,
                'growth' => round($patientsGrowth, 1),
            ],
            'today_appointments' => [
                'count' => $todayAppointments,
            ],
            'issued_prescriptions' => [
                'count' => $issuedPrescriptions,
            ],
            'review_growth' => [
                'percentage' => round($reviewGrowth, 1),
                'average_rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ],
        ];
    }

    public function getPatientsList($doctorId, $filters = [])
    {
        $query = User::where('role', 'patient')
            ->whereHas('appointments', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            })
            ->with(['appointments' => function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                  ->orderBy('appointment_date', 'desc')
                  ->limit(1);
            }]);

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate(20);

        return $patients;
    }

    public function getPatientDetails($doctorId, $patientId)
    {
        $patient = User::where('id', $patientId)
            ->where('role', 'patient')
            ->firstOrFail();

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->with('medicalRecord')
            ->orderBy('appointment_date', 'desc')
            ->get();

        $medicalRecords = MedicalRecord::whereHas('appointment', function ($q) use ($doctorId, $patientId) {
            $q->where('doctor_id', $doctorId)
              ->where('patient_id', $patientId);
        })->with('appointment')
          ->orderBy('created_at', 'desc')
          ->get();

        $reviews = Review::where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->get();

        return [
            'patient' => $patient,
            'appointments' => $appointments,
            'medical_records' => $medicalRecords,
            'reviews' => $reviews,
        ];
    }

    public function getTodayActivity($doctorId)
    {
        $todayAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->with(['patient', 'medicalRecord'])
            ->orderBy('appointment_time')
            ->get();

        return $todayAppointments->map(function ($appointment) {
            $latestDiagnosis = null;
            if ($appointment->medicalRecord) {
                $latestDiagnosis = $appointment->medicalRecord->diagnosis;
            }

            return [
                'id' => $appointment->id,
                'patient_name' => $appointment->patient->name,
                'patient_id' => $appointment->patient->id,
                'appointment_time' => $appointment->appointment_time,
                'status' => $appointment->status,
                'diagnosis' => $latestDiagnosis,
                'visit_time' => $appointment->created_at,
            ];
        });
    }

    public function getUpcomingTasks($doctorId)
    {
        // Get upcoming appointments (next 7 days)
        $upcomingAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>', today())
            ->whereDate('appointment_date', '<=', now()->addDays(7))
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        // Get admin tasks (could be expanded later)
        $adminTasks = collect([
            [
                'id' => 'task_1',
                'title' => 'تحديث سجلات التأمين',
                'type' => 'admin',
                'due_date' => now()->addDays(3),
                'status' => 'pending',
            ],
            [
                'id' => 'task_2',
                'title' => 'اجتماع القسم الطبي',
                'type' => 'meeting',
                'due_date' => now()->addDays(5),
                'status' => 'scheduled',
            ],
        ]);

        return [
            'appointments' => $upcomingAppointments,
            'tasks' => $adminTasks,
        ];
    }

    public function getPrescriptions($doctorId, $filters = [])
    {
        $query = MedicalRecord::where('type', 'prescription')
            ->whereHas('appointment', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId);
            })
            ->with(['appointment.patient']);

        if (isset($filters['patient_id'])) {
            $query->whereHas('appointment', function ($q) use ($filters) {
                $q->where('patient_id', $filters['patient_id']);
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $prescriptions = $query->orderBy('created_at', 'desc')->paginate(20);

        return $prescriptions;
    }

    public function getRecords($doctorId, $filters = [])
    {
        $query = MedicalRecord::whereHas('appointment', function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        })
        ->with(['appointment.patient']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['patient_id'])) {
            $query->whereHas('appointment', function ($q) use ($filters) {
                $q->where('patient_id', $filters['patient_id']);
            });
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(20);

        return $records;
    }

    public function getPatientPrescriptions($doctorId, $patientId)
    {
        $prescriptions = MedicalRecord::where('type', 'prescription')
            ->whereHas('appointment', function ($q) use ($doctorId, $patientId) {
                $q->where('doctor_id', $doctorId)
                  ->where('patient_id', $patientId);
            })
            ->with('appointment')
            ->orderBy('created_at', 'desc')
            ->get();

        return $prescriptions;
    }
}
