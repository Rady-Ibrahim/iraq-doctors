# فلو المستخدمين — أطباء العراق

مرجع لمسارات الداشبورد (ويب) و API. **آخر تحديث:** يونيو 2026 — جاهز للإنتاج.

---

## ملخص الحالة

| المجال | الحالة |
|--------|--------|
| تسجيل دكتور + OTP + موافقة أدمن | ✅ |
| اشتراكات + إيصال + تأكيد أدمن | ✅ |
| طلبات مواعيد (قبول/رفض/إكمال) | ✅ |
| سجل طبي مربوط بالموعد | ✅ |
| إضافة مسؤول من لوحة الأدمن | ✅ |
| تذكير انتهاء اشتراك (3 أيام) — إيميل | ✅ `subscriptions:process` يومياً |
| جلسات منفصلة أدمن/دكتور | ✅ cookies منفصلة |
| رفع صور حتى 10 ميجا | ✅ `UPLOAD_MAX_IMAGE_KB` |
| إشعار push عند حجز جديد | ✅ In-app notifications + bell |
| إحصائيات يوم العيادة | ✅ في Dashboard الدكتور |

---

## 1. فلو الدكتور

### 1.1 التسجيل والتفعيل

```
/doctor/register
  → بيانات شخصية + تخصص + محافظة + منطقة + عنوان + موقع (خريطة)
  → صورة شخصية + ترخيص + صورة عيادة
  → إنشاء فرع أساسي "العيادة الرئيسية"
  ↓
/doctor/verify-email  → OTP على الإيميل
  ↓
/doctor/pending  → انتظار موافقة الأدمن
  ↓
موافقة → /doctor/dashboard
رفض   → /doctor/rejected → إعادة رفع مستندات
```

### 1.2 الاشتراك

```
/doctor/dashboard/subscription/plans
  → عرض الباقات + أرقام الدفع (من إعدادات الأدمن)
  → اختيار باقة → رفع إيصال + المبلغ = سعر الباقة
  ↓
pending_payment
  ↓
أدمن يؤكد في /admin/dashboard/subscriptions
  ↓
active → إيميل تذكير قبل 3 أيام من الانتهاء (cron)
```

### 1.3 طلبات المرضى

```
مريض يحجز (API) → pending
  ↓
/doctor/dashboard/requests
  → معلق: [قبول] / [رفض]
  → مؤكد: [إكمال]
  → مكتمل: [إضافة سجل طبي]
```

### 1.4 صفحات الداشبورد

| الصفحة | المسار |
|--------|--------|
| الرئيسية | `/doctor/dashboard` |
| طلبات المواعيد | `/doctor/dashboard/requests` |
| الاشتراكات | `/doctor/dashboard/subscription/plans` |
| المرضى | `/doctor/dashboard/patients` |
| الوصفات | `/doctor/dashboard/prescriptions` |
| السجلات الطبية | `/doctor/dashboard/records` |
| التقويم | `/doctor/dashboard/calendar` |
| الإعدادات | `/doctor/dashboard/settings` |

---

## 2. فلو الأدمن

### 2.1 الدخول والمستخدمون

```
/admin/login → /admin/dashboard
/admin/users → قائمة المستخدمين + [إضافة مسؤول]
```

**إضافة مسؤول:** زر "إضافة مسؤول" → اسم، هاتف، إيميل، كلمة مرور → `POST /admin/api/users/admins`

### 2.2 الأطباء

```
/admin/dashboard/doctors → قائمة
/admin/dashboard/doctors/{id} → موافقة / رفض / تعليق / تفعيل
```

### 2.3 الاشتراكات والدفع

```
/admin/dashboard/subscriptions
  → إعدادات الدفع (فودافون كاش + بنك)
  → خطط الاشتراك (CRUD: إضافة / تعديل / حذف)
  → جدول اشتراكات → تأكيد / رفض pending_payment
```

### 2.4 باقي الصفحات

| الصفحة | المسار |
|--------|--------|
| الرئيسية | `/admin/dashboard` |
| المرضى | `/admin/dashboard/patients` |
| المواعيد | `/admin/dashboard/appointments` |
| الإيرادات | `/admin/dashboard/revenue` |
| التحليلات | `/admin/dashboard/analytics` |
| التخصصات | `/admin/dashboard/specialities` |
| المحافظات | `/admin/dashboard/governorates` |
| المستخدمون | `/admin/users` |

---

## 3. فلو المريض (API)

```
POST /api/v1/auth/send-otp → verify-otp → register
POST /api/v1/auth/login (يتطلب email_verified_at)
GET  /api/v1/doctors → حجز
POST /api/v1/appointments → pending
[دكتور يقبل/يرفض/يكمل من الداشبورد]
GET  /api/v1/medical-records/patient/history
POST /api/v1/auth/avatar (حتى 10 ميجا)
```

---

## 4. حالات النظام

### الطبيب `doctors.status`
`pending` → `approved` | `rejected` | `suspended`

### الموعد `appointments.status`
`pending` → `confirmed` → `completed` | `cancelled`

### الاشتراك `doctor_subscriptions.status`
`pending_payment` → `active` → `expired` | `cancelled`

---

## 5. الجلسات (أدمن + دكتور معاً)

تم فصل cookies:
- الأدمن: `ADMIN_SESSION_COOKIE` (افتراضي: `iraq_doctors_admin_session`)
- الدكتور: `DOCTOR_SESSION_COOKIE` (افتراضي: `iraq_doctors_doctor_session`)

يمكن فتح `/admin/dashboard` و `/doctor/dashboard` في تابين مختلفين **بدون 419**.

---

## 6. النشر على الإنتاج

### أوامر الإعداد

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=SubscriptionPlanSeeder  # إن لزم
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Cron (مهم)

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

يشغّل يومياً الساعة 8 صباحاً:
- `subscriptions:process` — إنهاء الاشتراكات المنتهية + إيميل تذكير قبل 3 أيام

### إعدادات السيرفر

| الإعداد | القيمة المقترحة |
|---------|-----------------|
| `APP_DEBUG` | `false` |
| `APP_ENV` | `production` |
| PHP `upload_max_filesize` | `12M` أو أكثر |
| PHP `post_max_size` | `12M` أو أكثر |
| `MAIL_*` | SMTP حقيقي |
| `FILESYSTEM_DISK` | `public` |

### متغيرات `.env` المهمة

```env
UPLOAD_MAX_IMAGE_KB=10240
UPLOAD_MAX_DOCUMENT_KB=10240
ADMIN_SESSION_COOKIE=iraq_doctors_admin_session
DOCTOR_SESSION_COOKIE=iraq_doctors_doctor_session
```

### حسابات تجريبية

راجع `database/seeders/DEMO_ACCOUNTS.md` بعد `php artisan db:seed --class=DemoDataSeeder`.

---

## 7. ما تبقى (اختياري — مرحلة لاحقة)

| الميزة | الأولوية |
|--------|----------|
| إشعار push/WebSocket حقيقي (بدل polling) | متوسطة |
| تمييز `source` في السجلات (app vs clinic) | منخفضة |
| إدارة باقات متعددة اللغات/ميزات تفصيلية | منخفضة |

---

*للتفاصيل التقنية راجع الكود في `Modules/Doctor`, `Modules/Admin`, `Modules/Subscription`.*
