<?php

namespace Modules\Laboratory\Enums;

enum LaboratoryOrderStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case Scheduled = 'scheduled';
    case Collected = 'collected';
    case Processing = 'processing';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Reviewing => 'قيد المراجعة',
            self::Quoted => 'تم عرض السعر',
            self::Accepted => 'مقبول',
            self::Scheduled => 'مجدول',
            self::Collected => 'تم سحب العينة',
            self::Processing => 'قيد التحليل',
            self::Ready => 'النتيجة جاهزة',
            self::Delivered => 'تم التسليم',
            self::Cancelled => 'ملغي',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return self[] */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Reviewing, self::Cancelled],
            self::Reviewing => [self::Quoted, self::Cancelled],
            self::Quoted => [self::Cancelled],
            self::Accepted => [self::Scheduled, self::Cancelled],
            self::Scheduled => [self::Collected, self::Cancelled],
            self::Collected => [self::Processing, self::Cancelled],
            self::Processing => [self::Ready, self::Cancelled],
            self::Ready => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    public static function activeStatuses(): array
    {
        return array_map(
            fn (self $s) => $s->value,
            array_filter(self::cases(), fn (self $s) => ! in_array($s, [self::Delivered, self::Cancelled], true))
        );
    }

    public static function historyStatuses(): array
    {
        return [self::Delivered->value];
    }
}
