# 🏥 أطباء العراق — حالة المشروع

**آخر تحديث:** 1 يونيو 2026  
**الحالة:** قيد التطوير

---

## ✅ الموديولات المكتملة

### 1. Auth Module ✅
**الوظيفة:** نظام المصادقة وإدارة المستخدمين

**الملفات:**
- Models: `User`, `Otp`
- Migrations: `users`, `otps`
- Service: `AuthService`
- Controller: `AuthController`
- Requests: `RegisterRequest`, `LoginRequest`, `SendOtpRequest`, `VerifyOtpRequest`

**API Endpoints:**
- `POST /api/v1/auth/register` — تسجيل جديد
- `POST /api/v1/auth/login` — دخول
- `POST /api/v1/auth/send-otp` — إرسال OTP
- `POST /api/v1/auth/verify-otp` — التحقق من OTP
- `POST /api/v1/auth/logout` — خروج
- `GET /api/v1/auth/me` — بيانات المستخدم

---

### 2. Doctor Module ✅
**الوظيفة:** إدارة الأطباء والتخصصات والبحث

**الملفات:**
- Models: `Doctor`, `Speciality`, `DoctorSchedule`
- Migrations: `doctors`, `specialities`, `doctor_schedules`
- Service: `DoctorService`
- Controller: `DoctorController`
- Requests: `SearchDoctorsRequest`

**API Endpoints:**
- `GET /api/v1/doctors` — البحث عن الأطباء مع فلاتر
- `GET /api/v1/doctors/specialities` — قائمة التخصصات
- `GET /api/v1/doctors/{id}` — بروفايل الطبيب
- `GET /api/v1/doctors/{id}/schedule` — جدول الطبيب

**الفلاتر المدعومة:**
- التخصص
- اسم الطبيب
- التقييم
- سعر الكشف
- الموقع الجغرافي (latitude, longitude, radius)
- الترتيب (rating, fee_asc, fee_desc, experience)

---

### 3. Appointment Module ✅
**الوظيفة:** حجز وإدارة المواعيد

**الملفات:**
- Models: `Appointment`
- Migrations: `appointments`
- Service: `AppointmentService`
- Controller: `AppointmentController`
- Requests: `BookAppointmentRequest`

**API Endpoints:**
- `POST /api/v1/appointments` — حجز موعد جديد
- `GET /api/v1/appointments/my` — مواعيدي
- `GET /api/v1/appointments/{id}` — تفاصيل الموعد
- `POST /api/v1/appointments/{id}/cancel` — إلغاء الموعد
- `POST /api/v1/appointments/{id}/confirm` — تأكيد الموعد
- `POST /api/v1/appointments/{id}/complete` — إكمال الموعد

**حالات الموعد:**
- `pending` — في الانتظار
- `confirmed` — مؤكد
- `completed` — مكتمل
- `cancelled` — ملغي
- `no_show` — لم يحضر

---

### 4. Review Module ✅
**الوظيفة:** نظام التقييمات والمراجعات

**الملفات:**
- Models: `Review`
- Migrations: `reviews`
- Service: `ReviewService`
- Controller: `ReviewController`
- Requests: `CreateReviewRequest`

**API Endpoints:**
- `POST /api/v1/reviews` — إضافة تقييم
- `GET /api/v1/reviews/my` — تقييماتي
- `GET /api/v1/reviews/doctor/{id}` — تقييمات الطبيب

**قواعد التقييم:**
- يمكن التقييم فقط بعد اكتمال الموعد
- كل موعد يمكن تقييمه مرة واحدة فقط
- التقييم يحدث متوسط تقييم الطبيب تلقائياً

---

## 🚧 الموديولات قيد التطوير

### 1. Doctor Branches Module ✅ (مكتمل)
**الوظيفة:** نظام الفروع والمواقع والخرائط

**الملفات:**
- Models: `DoctorBranch`
- Migrations: `doctor_branches`, `add_branch_id_to_doctor_schedules`
- Service: `DoctorBranchService`
- Controller: `DoctorBranchController`
- Requests: `CreateBranchRequest`
- تحديثات: `Doctor` Model, `DoctorSchedule` Model

**API Endpoints:**
- `GET /api/v1/doctors/{doctorId}/branches` — قائمة فروع الطبيب
- `POST /api/v1/doctors/{doctorId}/branches` — إضافة فرع جديد
- `GET /api/v1/doctors/branches/{branchId}` — تفاصيل الفرع
- `PUT /api/v1/doctors/branches/{branchId}` — تعديل الفرع
- `DELETE /api/v1/doctors/branches/{branchId}` — حذف الفرع
- `GET /api/v1/doctors/branches/nearby` — البحث عن فروع قريبة

**الميزات:**
- دعم الفروع المتعددة للطبيب الواحد
- تحديد الفرع الرئيسي (Primary Branch)
- البحث الجغرافي بناءً على الإحداثيات
- فلترة حسب المحافظة
- ربط الفروع بجداول المواعيد

---

### 2. Medical Records Module ✅ (مكتمل)
**الوظيفة:** السجل الطبي والروشتات والتقارير

**الملفات:**
- Models: `MedicalRecord`
- Migrations: `medical_records`
- Service: `MedicalRecordService`
- Controller: `MedicalRecordController`
- Requests: `CreateMedicalRecordRequest`, `UploadAttachmentRequest`
- تحديثات: `Appointment` Model

**API Endpoints:**
- `POST /api/v1/medical-records` — إضافة سجل طبي (للطبيب فقط)
- `GET /api/v1/medical-records/appointment/{appointmentId}` — عرض سجل موعد معين
- `GET /api/v1/medical-records/patient/history` — السجل الطبي للمريض
- `POST /api/v1/medical-records/{recordId}/attachments` — رفع ملف مرفق

**الميزات:**
- أنواع السجلات: prescription (روشتة), report (تقرير), diagnosis (تشخيص)
- دعم رفع الملفات (صور، PDFs)
- السجل الطبي مرتبط بالموعد
- لا يمكن تعديل السجل بعد الحفظ
- صلاحيات: الطبيب يرى مواعيده، المريض يرى سجله فقط

---

### 3. Profile & Password Management ✅ (مكتمل)
**الوظيفة:** تحديث البيانات الشخصية وكلمة المرور وإعادة تعيينها

**الملفات:**
- Requests: `UpdateProfileRequest`, `UpdatePasswordRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`
- تحديثات: `AuthService`, `AuthController`, `Auth Routes`

**API Endpoints:**
- `PUT /api/v1/auth/profile` — تحديث البيانات الشخصية
- `PUT /api/v1/auth/password` — تغيير كلمة المرور
- `POST /api/v1/auth/forgot-password` — إرسال OTP لإعادة التعيين
- `POST /api/v1/auth/reset-password` — إعادة تعيين كلمة المرور

**الميزات:**
- Unique Validation مع استثناء المستخدم الحالي
- OTP Security مع type = 'password_reset'
- تحديث البيانات الشخصية (للمريض والطبيب)
- تغيير كلمة المرور مع التحقق من الحالية
- إعادة تعيين كلمة المرور عبر OTP

---

### 4. Advanced Search Filters ✅ (مكتمل)
**الوظيفة:** تحسين وتطوير فلاتر البحث عن الأطباء

**الملفات:**
- Migration: `add_consultation_type_and_indexes_to_doctors_table`
- تحديثات: `Doctor` Model, `SearchDoctorsRequest`, `DoctorService`

**الفلاتر الجديدة:**
- **Availability:** today, tomorrow, this_week (مع التحقق من المواعيد المحجوزة)
- **Consultation Type:** clinic, home, online
- **Distance Range:** 5, 10, 20, 50 كم
- **Experience Level:** junior, intermediate, senior
- **Rating Range:** min_rating, max_rating
- **Price Range:** min_fee, max_fee
- **Governorate:** فلترة حسب المحافظة

**التحسينات:**
- إضافة Database Indexes على consultation_fee, rating, experience_years, consultation_type, governorate
- Optimization لـ Haversine Formula (فلترة governorate أولاً لتقليل استهلاك الـ CPU)
- Availability Check مع التحقق من day_of_week و المواعيد المحجوزة
- دعم الترتيب حسب المسافة (distance sort)

---

### 5. Static Pages Module ✅ (مكتمل)
**الوظيفة:** إدارة الصفحات الثابتة (الشروط، السياسات، إلخ)

**الملفات:**
- Models: `StaticPage`
- Migrations: `static_pages`
- Service: `StaticPageService`
- Controller: `StaticPageController`
- Requests: `CreateStaticPageRequest`, `UpdateStaticPageRequest`
- Seeder: `StaticPageSeeder`

**API Endpoints:**
- `GET /api/v1/pages` — قائمة الصفحات المفعلة (Public)
- `GET /api/v1/pages/{slug}` — عرض صفحة معينة (Public)
- `POST /api/v1/admin/pages` — إنشاء صفحة جديدة (Admin)
- `GET /api/v1/admin/pages` — قائمة جميع الصفحات (Admin)
- `PUT /api/v1/admin/pages/{id}` — تحديث صفحة (Admin)
- `DELETE /api/v1/admin/pages/{id}` — حذف صفحة (Admin)

**الميزات:**
- دعم اللغات (العربية والإنجليزية)
- Slug-based Routing
- Ordering للصفحات
- Active/Inactive للصفحات
- Admin Control للإدارة
- Seeder للصفحات الافتراضية

---

### 6. الفجوات المكتشفة من الفيجما ✅ (مكتمل)
**الوظيفة:** إصلاح الفجوات المكتشفة من شاشات الفيجما

#### **1. Doctor Dashboard Counter Metrics**
**الملفات:**
- تحديثات: `AuthService`, `AuthController`

**الميزات:**
- إضافة `doctor_stats` في `GET /api/v1/auth/me` للدكتور
- الإحصائيات تشمل:
  - `total_patients_count` — عدد المرضى الفريدين
  - `sent_prescriptions_count` — عدد الروشتات المرسلة
  - `today_appointments_count` — عدد المواعيد اليوم

#### **2. Ghost Patients (Doctor-Created Patients)**
**الملفات:**
- Migration: `add_ghost_patient_fields_to_users_table`
- Request: `CreateGhostPatientRequest`
- تحديثات: `User` Model, `AuthService`, `AuthController`, `Auth Routes`

**الميزات:**
- إضافة حقول `is_ghost` و `created_by_doctor_id` في جدول `users`
- Endpoint: `POST /api/v1/auth/ghost-patient` (Doctor only)
- إنشاء مريض بدون باسورد وOTP للمرضى الوهميين (حجز أرضي)

#### **3. Vital Signs في Medical Records**
**الملفات:**
- Migration: `add_vital_signs_to_medical_records_table`
- تحديثات: `MedicalRecord` Model, `CreateMedicalRecordRequest`, `MedicalRecordService`

**الميزات:**
- إضافة حقول: `weight`, `height`, `blood_pressure`, `allergies`
- دعم تخزين العلامات الحيوية في السجلات الطبية

---

## 📋 الموديولات المخططة

---

## 🏗️ البنية التقنية

**Backend:** Laravel 11  
**Database:** MySQL  
**Authentication:** Laravel Sanctum  
**Architecture:** Modular Service Strategy  
**API Version:** v1

---

## 📊 Database Schema

### الجداول الحالية:
- `users` — المستخدمين
- `otps` — أكواد التحقق
- `specialities` — التخصصات
- `doctors` — الأطباء
- `doctor_schedules` — جداول الأطباء
- `appointments` — المواعيد
- `reviews` — التقييمات

---

## 🔧 التكوينات

**Providers المسجلة:**
- `Modules\Auth\Providers\AuthServiceProvider`
- `Modules\Doctor\Providers\DoctorServiceProvider`
- `Modules\Appointment\Providers\AppointmentServiceProvider`
- `Modules\Review\Providers\ReviewServiceProvider`

**Routes:**
- `routes/api.php` — Main API router
- كل موديول له ملف routes منفصل

---

## 📝 ملاحظات

- جميع الـ Routes تستخدم Sanctum للمصادقة
- الـ Validation باللغة العربية
- Error handling موحد
- UUID للمعرفات الأساسية
- النظام يدعم اللغة العربية والإنجليزية

---

**التالي:** Doctor Branches Module
