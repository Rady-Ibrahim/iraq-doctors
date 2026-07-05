<?php

namespace Modules\Auth\Services\Api;

use Modules\Laboratory\Services\Api\LaboratoryOrderService;
use Modules\Pharmacy\Services\Api\PharmacyOrderService;

class PatientOrdersService
{
    public function __construct(
        private LaboratoryOrderService $laboratoryOrderService,
        private PharmacyOrderService $pharmacyOrderService
    ) {}

    public function getUnifiedOrders(int $patientId, bool $historyOnly = false): array
    {
        $labOrders = $this->laboratoryOrderService->getPatientOrders($patientId, null, $historyOnly);
        $pharmacyOrders = $this->pharmacyOrderService->getPatientOrders($patientId, null, $historyOnly);

        $items = collect();

        foreach ($labOrders as $order) {
            $items->push(array_merge(
                ['order_type' => 'laboratory'],
                $this->laboratoryOrderService->formatOrderForPatient($order)
            ));
        }

        foreach ($pharmacyOrders as $order) {
            $items->push(array_merge(
                ['order_type' => 'pharmacy'],
                $this->pharmacyOrderService->formatOrderForPatient($order)
            ));
        }

        return $items
            ->sortByDesc(fn ($row) => $row['created_at'] ?? '')
            ->values()
            ->all();
    }
}
