<?php

namespace Modules\Doctor\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Appointment\Models\Appointment;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorSchedule;
use Modules\MedicalRecord\Models\MedicalRecord;
use Modules\Review\Models\Review;

class DoctorDashboardService
{
    public function getMetrics(int $doctorId): array
    {
        $doctor = Doctor::findOrFail($doctorId);

        $totalPatients = Appointment::where('doctor_id', $doctorId)
            ->distinct('patient_id')
            ->count('patient_id');

        $newThisMonth = Appointment::where('doctor_id', $doctorId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct('patient_id')
            ->count('patient_id');

        $todayAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->count();

        $upcomingAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        $totalPrescriptions = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', 'prescription')
            ->count();

        $thisMonthPrescriptions = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', 'prescription')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalReviews = Review::where('doctor_id', $doctorId)->count();

        return [
            'patients' => [
                'total' => $totalPatients,
                'new_this_month' => $newThisMonth,
            ],
            'appointments' => [
                'today' => $todayAppointments,
                'upcoming' => $upcomingAppointments,
            ],
            'prescriptions' => [
                'total' => $totalPrescriptions,
                'this_month' => $thisMonthPrescriptions,
            ],
            'reviews' => [
                'average_rating' => round((float) $doctor->rating, 1),
                'total' => $totalReviews,
            ],
        ];
    }

    public function getPatientsList(int $doctorId, array $filters = [])
    {
        $limit = (int) ($filters['limit'] ?? 20);

        $query = User::where('role', 'patient')
            ->whereHas('appointments', fn ($q) => $q->where('doctor_id', $doctorId))
            ->withCount(['appointments as total_appointments' => fn ($q) => $q->where('doctor_id', $doctorId)])
            ->with(['appointments' => fn ($q) => $q->where('doctor_id', $doctorId)->orderByDesc('appointment_date')->limit(1)]);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
            });
        }

        $sortBy = $filters['sort_by'] ?? 'latest';
        match ($sortBy) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('created_at'),
        };

        $paginator = $query->paginate($limit);

        $paginator->getCollection()->transform(function (User $patient) {
            $lastAppointment = $patient->appointments->first();

            return [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'total_appointments' => $patient->total_appointments,
                'last_appointment_date' => $lastAppointment?->appointment_date?->format('Y-m-d'),
                'last_visit' => $lastAppointment?->appointment_date?->format('Y-m-d'),
            ];
        });

        return $paginator;
    }

    public function getPatientDetails(int $doctorId, int $patientId): array
    {
        $patient = User::where('id', $patientId)->where('role', 'patient')->firstOrFail();

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->with('medicalRecord')
            ->orderByDesc('appointment_date')
            ->get();

        $medicalRecords = MedicalRecord::where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
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

    public function getTodayActivity(int $doctorId): array
    {
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today())
            ->with('patient')
            ->orderBy('appointment_time')
            ->get()
            ->map(fn ($a) => $this->formatAppointment($a));

        return ['appointments' => $appointments];
    }

    public function getUpcomingTasks(int $doctorId): array
    {
        $upcomingAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>=', today())
            ->whereDate('appointment_date', '<=', now()->addDays(7))
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $tasks = $upcomingAppointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => 'موعد: ' . ($appointment->patient->name ?? 'مريض'),
                'type' => 'appointment',
                'date' => $appointment->appointment_date?->format('Y-m-d'),
                'time' => $this->formatTime($appointment->appointment_time),
                'status' => $appointment->status,
            ];
        })->values()->all();

        return ['tasks' => $tasks];
    }

    public function getPrescriptions(int $doctorId, array $filters = [])
    {
        $query = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', 'prescription')
            ->with(['patient']);

        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        if (!empty($filters['search'])) {
            $query->whereHas('patient', fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'));
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $paginator = $query->orderByDesc('created_at')->paginate((int) ($filters['limit'] ?? 20));
        $paginator->getCollection()->transform(fn ($r) => $this->formatPrescription($r));

        return $paginator;
    }

    public function getRecords(int $doctorId, array $filters = [])
    {
        $query = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', '!=', 'prescription')
            ->with(['patient']);

        if (!empty($filters['type'])) {
            $query->where('record_type', $this->mapRecordTypeToDb($filters['type']));
        }
        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        if (!empty($filters['search'])) {
            $query->whereHas('patient', fn ($q) => $q->where('name', 'like', '%' . $filters['search'] . '%'));
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        $paginator = $query->orderByDesc('created_at')->paginate((int) ($filters['limit'] ?? 20));
        $paginator->getCollection()->transform(fn ($r) => $this->formatRecord($r));

        return $paginator;
    }

    public function getPatientPrescriptions(int $doctorId, int $patientId)
    {
        return MedicalRecord::where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->where('record_type', 'prescription')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getProfile(int $userId): array
    {
        $user = User::findOrFail($userId);
        $doctor = Doctor::where('user_id', $userId)->with('speciality')->firstOrFail();

        return [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'address' => $user->address ?? $doctor->address,
            'bio_ar' => $doctor->bio_ar,
            'bio_en' => $doctor->bio_en,
            'experience_years' => $doctor->experience_years,
            'consultation_fee' => $doctor->consultation_fee,
            'speciality' => $doctor->speciality?->name_ar,
            'status' => $doctor->status,
        ];
    }

    public function updateProfile(int $userId, array $data): User
    {
        $user = User::findOrFail($userId);
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
            'email' => $data['email'] ?? $user->email,
            'address' => $data['address'] ?? $user->address,
        ]);

        return $user->fresh();
    }

    public function updateProfessional(int $userId, array $data): Doctor
    {
        $doctor = Doctor::where('user_id', $userId)->firstOrFail();
        $doctor->update([
            'bio_ar' => $data['bio_ar'] ?? $doctor->bio_ar,
            'bio_en' => $data['bio_en'] ?? $doctor->bio_en,
            'experience_years' => $data['experience_years'] ?? $doctor->experience_years,
            'consultation_fee' => $data['consultation_fee'] ?? $doctor->consultation_fee,
        ]);

        return $doctor->fresh();
    }

    public function getSchedules(int $doctorId): array
    {
        return DoctorSchedule::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'day_of_week' => ucfirst($s->day_of_week),
                'start_time' => $this->formatTime($s->start_time),
                'end_time' => $this->formatTime($s->end_time),
            ])
            ->all();
    }

    public function deleteSchedule(int $doctorId, int $scheduleId): bool
    {
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)->findOrFail($scheduleId);
        return (bool) $schedule->delete();
    }

    public function getCalendar(int $doctorId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereBetween('appointment_date', [$start, $end])
            ->orderBy('appointment_time')
            ->get();

        $grouped = [];
        foreach ($appointments as $appointment) {
            $date = $appointment->appointment_date->format('Y-m-d');
            $grouped[$date][] = [
                'id' => $appointment->id,
                'time' => $this->formatTime($appointment->appointment_time),
                'status' => $appointment->status,
            ];
        }

        return $grouped;
    }

    public function getAppointments(int $doctorId, array $filters = []): array
    {
        $query = Appointment::where('doctor_id', $doctorId)->with('patient');

        if (!empty($filters['date'])) {
            $query->whereDate('appointment_date', $filters['date']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query->orderBy('appointment_date')->orderBy('appointment_time');

        if (!empty($filters['limit'])) {
            $query->limit((int) $filters['limit']);
        }

        return $query->get()->map(fn ($a) => $this->formatAppointment($a))->all();
    }

    public function getAppointmentDetails(int $doctorId, int $appointmentId): array
    {
        $appointment = Appointment::where('doctor_id', $doctorId)
            ->with('patient')
            ->findOrFail($appointmentId);

        return $this->formatAppointment($appointment);
    }

    public function getSubscription(int $doctorId): ?array
    {
        $doctor = Doctor::findOrFail($doctorId);
        $active = $doctor->activeSubscription();

        if (!$active) {
            return null;
        }

        return [
            'plan_name' => $active->subscription?->name ?? 'خطة اشتراك',
            'price' => $active->amount_paid,
            'status' => $active->status,
            'start_date' => $active->start_date?->format('Y-m-d'),
            'end_date' => $active->end_date?->format('Y-m-d'),
        ];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = User::findOrFail($userId);

        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->update(['password' => $newPassword]);

        return true;
    }

    public function createGhostPatient(int $doctorUserId, array $data): User
    {
        return DB::transaction(function () use ($doctorUserId, $data) {
            return User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => Str::random(16),
                'role' => 'patient',
                'status' => 'active',
                'is_ghost' => true,
                'created_by_doctor_id' => $doctorUserId,
                'gender' => $data['gender'] ?? null,
            ]);
        });
    }

    public function createPrescription(int $doctorId, int $userId, array $data): MedicalRecord
    {
        return DB::transaction(function () use ($doctorId, $userId, $data) {
            $appointment = $this->ensureWalkInAppointment($doctorId, (int) $data['patient_id']);

            return MedicalRecord::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctorId,
                'patient_id' => $data['patient_id'],
                'record_type' => 'prescription',
                'diagnosis' => $data['diagnosis'] ?? null,
                'prescription' => $data['medicines'] ?? [],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);
        });
    }

    public function getPrescription(int $doctorId, int $recordId): array
    {
        $record = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', 'prescription')
            ->with('patient')
            ->findOrFail($recordId);

        return $this->formatPrescription($record, true);
    }

    public function updatePrescription(int $doctorId, int $recordId, array $data): MedicalRecord
    {
        $record = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', 'prescription')
            ->findOrFail($recordId);

        $record->update([
            'diagnosis' => $data['diagnosis'] ?? $record->diagnosis,
            'prescription' => $data['medicines'] ?? $record->prescription,
            'notes' => $data['notes'] ?? $record->notes,
        ]);

        return $record->fresh(['patient']);
    }

    public function deletePrescription(int $doctorId, int $recordId): bool
    {
        $record = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', 'prescription')
            ->findOrFail($recordId);

        return (bool) $record->delete();
    }

    public function createRecord(int $doctorId, int $userId, array $data, array $files = []): MedicalRecord
    {
        return DB::transaction(function () use ($doctorId, $userId, $data, $files) {
            $appointment = $this->ensureWalkInAppointment($doctorId, (int) $data['patient_id']);
            $attachments = $this->uploadAttachments($files);

            return MedicalRecord::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctorId,
                'patient_id' => $data['patient_id'],
                'record_type' => $this->mapRecordTypeToDb($data['type'] ?? 'diagnosis'),
                'diagnosis' => $data['title'] ?? null,
                'notes' => $this->buildRecordNotes($data),
                'attachments' => $attachments,
                'created_by' => $userId,
            ]);
        });
    }

    public function getRecord(int $doctorId, int $recordId): array
    {
        $record = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', '!=', 'prescription')
            ->with('patient')
            ->findOrFail($recordId);

        return $this->formatRecord($record, true);
    }

    public function updateRecord(int $doctorId, int $recordId, array $data, array $files = []): MedicalRecord
    {
        $record = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', '!=', 'prescription')
            ->findOrFail($recordId);

        $attachments = $record->attachments ?? [];
        if (!empty($files)) {
            $attachments = array_merge($attachments, $this->uploadAttachments($files));
        }

        $record->update([
            'record_type' => isset($data['type']) ? $this->mapRecordTypeToDb($data['type']) : $record->record_type,
            'diagnosis' => $data['title'] ?? $record->diagnosis,
            'notes' => isset($data['description']) || isset($data['notes'])
                ? $this->buildRecordNotes($data)
                : $record->notes,
            'attachments' => $attachments,
        ]);

        return $record->fresh(['patient']);
    }

    public function deleteRecord(int $doctorId, int $recordId): bool
    {
        $record = MedicalRecord::where('doctor_id', $doctorId)
            ->where('record_type', '!=', 'prescription')
            ->findOrFail($recordId);

        return (bool) $record->delete();
    }

    protected function ensureWalkInAppointment(int $doctorId, int $patientId): Appointment
    {
        $dayOfWeek = strtolower(now()->format('l'));

        $schedule = DoctorSchedule::firstOrCreate(
            ['doctor_id' => $doctorId, 'day_of_week' => $dayOfWeek],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_active' => true]
        );

        return Appointment::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'doctor_schedule_id' => $schedule->id,
            'appointment_date' => today(),
            'appointment_time' => now()->format('H:i:s'),
            'status' => 'completed',
            'notes' => 'زيارة من لوحة تحكم الطبيب',
        ]);
    }

    protected function formatPrescription(MedicalRecord $record, bool $detailed = false): array
    {
        $medicines = $record->prescription ?? [];

        $data = [
            'id' => $record->id,
            'patient_name' => $record->patient?->name,
            'patient_phone' => $record->patient?->phone,
            'medicines_count' => is_array($medicines) ? count($medicines) : 0,
            'notes' => $record->notes,
            'created_at' => $record->created_at,
        ];

        if ($detailed) {
            $data['diagnosis'] = $record->diagnosis;
            $data['medicines'] = $medicines;
        }

        return $data;
    }

    protected function formatRecord(MedicalRecord $record, bool $detailed = false): array
    {
        $meta = $this->parseRecordNotes($record->notes);
        $attachments = $record->attachments ?? [];

        $data = [
            'id' => $record->id,
            'patient_name' => $record->patient?->name,
            'patient_phone' => $record->patient?->phone,
            'type' => $meta['display_type'] ?? $record->record_type,
            'attachments_count' => count($attachments),
            'created_at' => $record->created_at,
        ];

        if ($detailed) {
            $data['title'] = $record->diagnosis;
            $data['description'] = $meta['description'] ?? '';
            $data['notes'] = $meta['text'] ?? '';
            $data['attachments'] = collect($attachments)->map(fn ($a, $i) => [
                'id' => $i,
                'file_name' => $a['file_name'] ?? 'ملف',
                'file_size' => $a['file_size'] ?? 0,
            ])->values()->all();
            $data['updated_at'] = $record->updated_at;
        }

        return $data;
    }

    protected function mapRecordTypeToDb(string $type): string
    {
        return match ($type) {
            'prescription' => 'prescription',
            'diagnosis' => 'diagnosis',
            'treatment', 'lab_test', 'imaging' => 'report',
            default => 'diagnosis',
        };
    }

    protected function buildRecordNotes(array $data): string
    {
        return json_encode([
            'description' => $data['description'] ?? '',
            'text' => $data['notes'] ?? '',
            'display_type' => $data['type'] ?? 'diagnosis',
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function parseRecordNotes(?string $notes): array
    {
        if (!$notes) {
            return [];
        }

        $decoded = json_decode($notes, true);

        return is_array($decoded) ? $decoded : ['text' => $notes];
    }

    protected function uploadAttachments(array $files): array
    {
        $service = app(\Modules\MedicalRecord\Services\Api\MedicalRecordService::class);
        $attachments = [];

        foreach ($files as $file) {
            if ($file) {
                $attachments[] = $service->uploadFile($file);
            }
        }

        return $attachments;
    }

    protected function formatAppointment(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'patient_name' => $appointment->patient?->name,
            'patient_id' => $appointment->patient_id,
            'date' => $appointment->appointment_date?->format('Y-m-d'),
            'time' => $this->formatTime($appointment->appointment_time),
            'status' => $appointment->status,
            'notes' => $appointment->notes,
        ];
    }

    protected function formatTime($time): string
    {
        if (!$time) {
            return '-';
        }

        if ($time instanceof Carbon) {
            return $time->format('H:i');
        }

        return Carbon::parse($time)->format('H:i');
    }
}
