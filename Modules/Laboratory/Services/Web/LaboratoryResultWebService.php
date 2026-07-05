<?php

namespace Modules\Laboratory\Services\Web;

use App\Notifications\LaboratoryOrderResultReady;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\LaboratoryOrder;
use Modules\Laboratory\Models\LaboratoryOrderResult;
use Modules\MedicalRecord\Models\MedicalRecord;

class LaboratoryResultWebService
{
    public function __construct(private LaboratoryOrderWebService $orderService) {}

    public function listResults(int $laboratoryId, int $orderId): array
    {
        $order = $this->orderService->findOrderForLaboratory($laboratoryId, $orderId);

        return $order->results()
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => $this->formatResult($r))
            ->values()
            ->all();
    }

    public function uploadResult(
        int $laboratoryId,
        int $orderId,
        UploadedFile $file,
        int $uploadedBy,
        ?string $notes = null
    ): LaboratoryOrderResult {
        return DB::transaction(function () use ($laboratoryId, $orderId, $file, $uploadedBy, $notes) {
            $order = $this->orderService->findOrderForLaboratory($laboratoryId, $orderId);

            if (! $this->canUploadResults($order)) {
                throw new \InvalidArgumentException('لا يمكن رفع النتائج في الحالة الحالية للطلب');
            }

            $path = $file->store('laboratory-results/' . $order->id, 'public');

            $result = LaboratoryOrderResult::create([
                'laboratory_order_id' => $order->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'notes' => $notes,
                'uploaded_by' => $uploadedBy,
            ]);

            $medicalRecord = $this->syncMedicalRecord($order, $uploadedBy);
            $result->update(['medical_record_id' => $medicalRecord->id]);

            if ($order->status === LaboratoryOrderStatus::Processing) {
                $this->orderService->transitionTo(
                    $laboratoryId,
                    $orderId,
                    LaboratoryOrderStatus::Ready
                );
            }

            $order->patient?->notify(new LaboratoryOrderResultReady($order->fresh('laboratory')));

            return $result->fresh('uploader');
        });
    }

    public function deleteResult(int $laboratoryId, int $orderId, int $resultId): void
    {
        DB::transaction(function () use ($laboratoryId, $orderId, $resultId) {
            $order = $this->orderService->findOrderForLaboratory($laboratoryId, $orderId);

            if ($order->isTerminal()) {
                throw new \InvalidArgumentException('لا يمكن حذف نتائج طلب منتهٍ');
            }

            $result = LaboratoryOrderResult::where('laboratory_order_id', $order->id)
                ->findOrFail($resultId);

            $result->delete();
            $this->refreshMedicalRecordAttachments($order);
        });
    }

    public function canUploadResults(LaboratoryOrder $order): bool
    {
        return in_array($order->status, [
            LaboratoryOrderStatus::Processing,
            LaboratoryOrderStatus::Ready,
        ], true);
    }

    protected function syncMedicalRecord(LaboratoryOrder $order, int $createdBy): MedicalRecord
    {
        $order->loadMissing(['laboratory', 'items', 'results']);

        $record = MedicalRecord::firstOrCreate(
            ['laboratory_order_id' => $order->id],
            [
                'patient_id' => $order->patient_id,
                'laboratory_id' => $order->laboratory_id,
                'record_type' => 'lab_result',
                'diagnosis' => 'نتائج تحاليل — ' . $order->order_number,
                'notes' => json_encode([
                    'order_number' => $order->order_number,
                    'laboratory_name' => $order->laboratory?->name,
                    'tests' => $order->items->pluck('test_name')->all(),
                ], JSON_UNESCAPED_UNICODE),
                'attachments' => [],
                'created_by' => $createdBy,
            ]
        );

        $this->refreshMedicalRecordAttachments($order, $record);

        return $record->fresh();
    }

    protected function refreshMedicalRecordAttachments(LaboratoryOrder $order, ?MedicalRecord $record = null): void
    {
        $record ??= MedicalRecord::where('laboratory_order_id', $order->id)->first();

        if (! $record) {
            return;
        }

        $attachments = LaboratoryOrderResult::where('laboratory_order_id', $order->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r) => [
                'file_name' => $r->file_name,
                'file_path' => storage_public_url($r->file_path),
                'file_type' => $r->mime_type,
                'file_size' => $r->file_size,
                'uploaded_at' => $r->created_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        $record->update(['attachments' => $attachments]);
    }

    public function formatResult(LaboratoryOrderResult $result): array
    {
        return [
            'id' => $result->id,
            'file_name' => $result->file_name,
            'file_url' => storage_public_url($result->file_path),
            'mime_type' => $result->mime_type,
            'file_size' => $result->file_size,
            'notes' => $result->notes,
            'uploaded_by' => $result->uploader?->name,
            'created_at' => $result->created_at?->format('Y-m-d H:i'),
        ];
    }
}
