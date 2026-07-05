<?php

namespace Modules\Laboratory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;

class LaboratoryOrderResult extends Model
{
    protected $table = 'laboratory_order_results';

    protected $fillable = [
        'laboratory_order_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'notes',
        'uploaded_by',
        'medical_record_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaboratoryOrder::class, 'laboratory_order_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(\Modules\MedicalRecord\Models\MedicalRecord::class);
    }
}
