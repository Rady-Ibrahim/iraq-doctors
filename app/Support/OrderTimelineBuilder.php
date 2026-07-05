<?php

namespace App\Support;

use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;

class OrderTimelineBuilder
{
    /** @return array<int, array{status: string, label: string, state: string, at: ?string}> */
    public static function pharmacy(PharmacyOrderStatus $current, bool $isDelivery, array $timestamps = []): array
    {
        $flow = [
            ['status' => PharmacyOrderStatus::New->value, 'label' => PharmacyOrderStatus::New->label(), 'key' => 'created_at'],
            ['status' => PharmacyOrderStatus::Reviewing->value, 'label' => PharmacyOrderStatus::Reviewing->label(), 'key' => null],
            ['status' => PharmacyOrderStatus::Quoted->value, 'label' => PharmacyOrderStatus::Quoted->label(), 'key' => 'quoted_at'],
            ['status' => PharmacyOrderStatus::Accepted->value, 'label' => PharmacyOrderStatus::Accepted->label(), 'key' => null],
            ['status' => PharmacyOrderStatus::Preparing->value, 'label' => PharmacyOrderStatus::Preparing->label(), 'key' => null],
        ];

        if ($isDelivery) {
            $flow[] = ['status' => PharmacyOrderStatus::OutForDelivery->value, 'label' => PharmacyOrderStatus::OutForDelivery->label(), 'key' => 'out_for_delivery_at'];
        }

        $flow[] = ['status' => PharmacyOrderStatus::Completed->value, 'label' => PharmacyOrderStatus::Completed->label(), 'key' => 'completed_at'];

        return self::build($flow, $current->value, $timestamps);
    }

    /** @return array<int, array{status: string, label: string, state: string, at: ?string}> */
    public static function laboratory(LaboratoryOrderStatus $current, array $timestamps = []): array
    {
        $flow = [
            ['status' => LaboratoryOrderStatus::New->value, 'label' => LaboratoryOrderStatus::New->label(), 'key' => 'created_at'],
            ['status' => LaboratoryOrderStatus::Reviewing->value, 'label' => LaboratoryOrderStatus::Reviewing->label(), 'key' => null],
            ['status' => LaboratoryOrderStatus::Quoted->value, 'label' => LaboratoryOrderStatus::Quoted->label(), 'key' => 'quoted_at'],
            ['status' => LaboratoryOrderStatus::Accepted->value, 'label' => LaboratoryOrderStatus::Accepted->label(), 'key' => null],
            ['status' => LaboratoryOrderStatus::Scheduled->value, 'label' => LaboratoryOrderStatus::Scheduled->label(), 'key' => 'scheduled_at'],
            ['status' => LaboratoryOrderStatus::Collected->value, 'label' => LaboratoryOrderStatus::Collected->label(), 'key' => null],
            ['status' => LaboratoryOrderStatus::Processing->value, 'label' => LaboratoryOrderStatus::Processing->label(), 'key' => null],
            ['status' => LaboratoryOrderStatus::Ready->value, 'label' => LaboratoryOrderStatus::Ready->label(), 'key' => null],
            ['status' => LaboratoryOrderStatus::Delivered->value, 'label' => LaboratoryOrderStatus::Delivered->label(), 'key' => 'completed_at'],
        ];

        return self::build($flow, $current->value, $timestamps);
    }

    /**
     * @param  array<int, array{status: string, label: string, key: ?string}>  $flow
     * @param  array<string, ?string>  $timestamps
     * @return array<int, array{status: string, label: string, state: string, at: ?string}>
     */
    protected static function build(array $flow, string $currentStatus, array $timestamps): array
    {
        if ($currentStatus === 'cancelled') {
            return array_map(fn ($step) => [
                'status' => $step['status'],
                'label' => $step['label'],
                'state' => 'cancelled',
                'at' => $step['key'] ? ($timestamps[$step['key']] ?? null) : null,
            ], $flow);
        }

        $statusOrder = array_column($flow, 'status');
        $currentIndex = array_search($currentStatus, $statusOrder, true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        return array_map(function ($step, $index) use ($currentIndex, $timestamps) {
            $state = 'pending';
            if ($index < $currentIndex) {
                $state = 'completed';
            } elseif ($index === $currentIndex) {
                $state = 'current';
            }

            return [
                'status' => $step['status'],
                'label' => $step['label'],
                'state' => $state,
                'at' => $step['key'] ? ($timestamps[$step['key']] ?? null) : null,
            ];
        }, $flow, array_keys($flow));
    }
}
