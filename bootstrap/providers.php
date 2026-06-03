<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\Doctor\Providers\DoctorServiceProvider::class,
    Modules\Appointment\Providers\AppointmentServiceProvider::class,
    Modules\Review\Providers\ReviewServiceProvider::class,
    Modules\MedicalRecord\Providers\MedicalRecordServiceProvider::class,
    Modules\StaticPage\Providers\StaticPageServiceProvider::class,
    Modules\Subscription\Providers\SubscriptionServiceProvider::class,
    Modules\Admin\Providers\AdminServiceProvider::class,
];
