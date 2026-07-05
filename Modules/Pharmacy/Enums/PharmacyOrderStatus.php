<?php

namespace Modules\Pharmacy\Enums;

enum PharmacyOrderStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Reviewing => 'قيد المراجعة',
            self::Quoted => 'تم عرض السعر',
            self::Accepted => 'مقبول',
            self::Preparing => 'قيد التجهيز',
            self::OutForDelivery => 'في الطريق للتوصيل',
            self::Completed => 'مكتمل',
            self::Cancelled => 'ملغي',
        };
    }

    public function canTransitionTo(self $target, ?string $fulfillmentType = null): bool
    {
        return in_array($target, $this->allowedTransitions($fulfillmentType), true);
    }

    /** @return self[] */
    public function allowedTransitions(?string $fulfillmentType = null): array
    {
        return match ($this) {
            self::New => [self::Reviewing, self::Cancelled],
            self::Reviewing => [self::Quoted, self::Cancelled],
            self::Quoted => [self::Cancelled],
            self::Accepted => [self::Preparing, self::Cancelled],
            self::Preparing => $fulfillmentType === 'delivery'
                ? [self::OutForDelivery, self::Cancelled]
                : [self::Completed, self::Cancelled],
            self::OutForDelivery => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public static function activeStatuses(): array
    {
        return array_map(
            fn (self $s) => $s->value,
            array_filter(self::cases(), fn (self $s) => ! in_array($s, [self::Completed, self::Cancelled], true))
        );
    }

    public static function historyStatuses(): array
    {
        return [self::Completed->value];
    }
}
