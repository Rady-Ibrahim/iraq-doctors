<?php

namespace Modules\MedicalRecord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Appointment\Models\Appointment;
use Modules\Doctor\Models\Doctor;
use Modules\Auth\Models\User;

class MedicalRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medical_records';

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'record_type',
        'diagnosis',
        'prescription',
        'notes',
        'attachments',
        'created_by',
        'weight',
        'height',
        'blood_pressure',
        'allergies',
        'laboratory_id',
        'laboratory_order_id',
        'pharmacy_id',
        'pharmacy_order_id',
    ];

    protected $casts = [
        'prescription' => 'array',
        'attachments' => 'array',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function laboratory()
    {
        return $this->belongsTo(\Modules\Laboratory\Models\Laboratory::class);
    }

    public function laboratoryOrder()
    {
        return $this->belongsTo(\Modules\Laboratory\Models\LaboratoryOrder::class);
    }

    public function pharmacy()
    {
        return $this->belongsTo(\Modules\Pharmacy\Models\Pharmacy::class);
    }

    public function pharmacyOrder()
    {
        return $this->belongsTo(\Modules\Pharmacy\Models\PharmacyOrder::class);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
