<?php

namespace Modules\Doctor\Support;

class DoctorStaffPermissions
{
    public const APPOINTMENTS_VIEW = 'appointments.view';

    public const APPOINTMENTS_MANAGE = 'appointments.manage';

    public const PATIENTS_VIEW = 'patients.view';

    public const PATIENTS_MANAGE = 'patients.manage';

    public const PRESCRIPTIONS_VIEW = 'prescriptions.view';

    public const PRESCRIPTIONS_MANAGE = 'prescriptions.manage';

    public const RECORDS_VIEW = 'records.view';

    public const RECORDS_MANAGE = 'records.manage';

    public const CALENDAR_VIEW = 'calendar.view';

    public const SCHEDULE_MANAGE = 'schedule.manage';

    public const SETTINGS_VIEW = 'settings.view';

    public const ALL = [
        self::APPOINTMENTS_VIEW => 'عرض طلبات المواعيد',
        self::APPOINTMENTS_MANAGE => 'إدارة المواعيد (قبول / رفض / إكمال)',
        self::PATIENTS_VIEW => 'عرض المرضى',
        self::PATIENTS_MANAGE => 'إضافة مرضى خارجيين',
        self::PRESCRIPTIONS_VIEW => 'عرض الوصفات',
        self::PRESCRIPTIONS_MANAGE => 'إنشاء وتعديل الوصفات',
        self::RECORDS_VIEW => 'عرض السجلات الطبية',
        self::RECORDS_MANAGE => 'إنشاء وتعديل السجلات',
        self::CALENDAR_VIEW => 'عرض التقويم',
        self::SCHEDULE_MANAGE => 'إدارة الجدول الزمني',
        self::SETTINGS_VIEW => 'الإعدادات الشخصية وكلمة المرور',
    ];

    public const DEFAULT = [
        self::APPOINTMENTS_VIEW,
        self::APPOINTMENTS_MANAGE,
        self::PATIENTS_VIEW,
        self::CALENDAR_VIEW,
    ];

    public static function labels(): array
    {
        return self::ALL;
    }

    public static function isValid(string $permission): bool
    {
        return array_key_exists($permission, self::ALL);
    }

    public static function sanitize(array $permissions): array
    {
        return array_values(array_unique(array_filter(
            $permissions,
            fn (string $permission) => self::isValid($permission)
        )));
    }
}
