# 📋 صفحات الداشبورد المطلوبة - Iraq Doctors Platform

**الحالة:** قيد التطوير  
**آخر تحديث:** 3 يونيو 2026

---

## ✅ الصفحات المكتملة

### Admin Dashboard
- ✅ `/admin/login` - صفحة تسجيل الدخول
- ✅ `/admin/dashboard` - الصفحة الرئيسية (مع إحصائيات أساسية)
- ✅ `layout.blade.php` - Layout رئيسي مع sidebar

### Doctor Dashboard
- ✅ `/doctor/login` - صفحة تسجيل الدخول
- ✅ `/doctor/dashboard` - الصفحة الرئيسية (مع إحصائيات أساسية)
- ✅ `layout.blade.php` - Layout رئيسي مع sidebar

---

## ❌ الصفحات الناقصة

### Admin Dashboard Pages

#### 1. `/admin/dashboard/doctors` - إدارة الأطباء
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] جدول بقائمة الأطباء
- [ ] فلاتر (الحالة، التخصص، البحث)
- [ ] أزرار الإجراءات (عرض، تعديل، موافقة، رفض، حذف)
- [ ] صفحة تفاصيل الطبيب
- [ ] صفحة تعديل بيانات الطبيب
- [ ] عرض الفروع والجداول
- [ ] عرض المواعيد والتقييمات

**API Endpoints المطلوبة:**
```
GET /admin/dashboard/doctors - قائمة الأطباء
GET /admin/dashboard/doctors/{id} - تفاصيل الطبيب
PUT /admin/dashboard/doctors/{id} - تعديل الطبيب
DELETE /admin/dashboard/doctors/{id} - حذف الطبيب
POST /admin/dashboard/doctors/{id}/approve - الموافقة
POST /admin/dashboard/doctors/{id}/reject - الرفض
GET /admin/dashboard/doctors/{id}/branches - فروع الطبيب
GET /admin/dashboard/doctors/{id}/appointments - مواعيد الطبيب
GET /admin/dashboard/doctors/{id}/reviews - تقييمات الطبيب
```

---

#### 2. `/admin/dashboard/patients` - إدارة المرضى
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] جدول بقائمة المرضى
- [ ] فلاتر (النوع: عادي/ghost، البحث)
- [ ] أزرار الإجراءات (عرض، حظر، إلغاء الحظر، حذف)
- [ ] صفحة تفاصيل المريض
- [ ] عرض السجل الطبي
- [ ] عرض المواعيد

**API Endpoints المطلوبة:**
```
GET /admin/dashboard/patients - قائمة المرضى
GET /admin/dashboard/patients/{id} - تفاصيل المريض
PUT /admin/dashboard/patients/{id} - تعديل المريض
DELETE /admin/dashboard/patients/{id} - حذف المريض
POST /admin/dashboard/patients/{id}/block - حظر المريض
POST /admin/dashboard/patients/{id}/unblock - إلغاء الحظر
GET /admin/dashboard/patients/{id}/appointments - مواعيد المريض
GET /admin/dashboard/patients/{id}/medical-records - السجل الطبي
```

---

#### 3. `/admin/dashboard/appointments` - إدارة المواعيد
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] جدول بقائمة المواعيد
- [ ] فلاتر (الحالة، التاريخ، الطبيب، المريض)
- [ ] أزرار الإجراءات (عرض، تأكيد، إكمال، إلغاء)
- [ ] صفحة تفاصيل الموعد
- [ ] عرض السجل الطبي المرتبط

**API Endpoints المطلوبة:**
```
GET /admin/dashboard/appointments - قائمة المواعيد
GET /admin/dashboard/appointments/{id} - تفاصيل الموعد
PUT /admin/dashboard/appointments/{id} - تعديل الموعد
POST /admin/dashboard/appointments/{id}/confirm - تأكيد الموعد
POST /admin/dashboard/appointments/{id}/complete - إكمال الموعد
POST /admin/dashboard/appointments/{id}/cancel - إلغاء الموعد
```

---

#### 4. `/admin/dashboard/revenue` - الإيرادات والتقارير
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] رسم بياني للإيرادات الشهرية
- [ ] رسم بياني للإيرادات حسب الطبيب
- [ ] جدول بتفاصيل الإيرادات
- [ ] فلاتر (التاريخ، الطبيب)
- [ ] تصدير التقارير (PDF/Excel)

**API Endpoints المطلوبة:**
```
GET /admin/dashboard/revenue - إحصائيات الإيرادات
GET /admin/dashboard/revenue/monthly - الإيرادات الشهرية
GET /admin/dashboard/revenue/by-doctor - الإيرادات حسب الطبيب
GET /admin/dashboard/revenue/export - تصدير التقرير
```

---

#### 5. `/admin/dashboard/analytics` - التحليلات المتقدمة
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] رسوم بيانية متعددة (نمو المستخدمين، المواعيد، التقييمات)
- [ ] إحصائيات التخصصات
- [ ] إحصائيات الفروع
- [ ] إحصائيات الاشتراكات
- [ ] فلاتر التاريخ

**API Endpoints المطلوبة:**
```
GET /admin/dashboard/analytics - التحليلات الشاملة
GET /admin/dashboard/analytics/users - تحليلات المستخدمين
GET /admin/dashboard/analytics/appointments - تحليلات المواعيد
GET /admin/dashboard/analytics/specialities - تحليلات التخصصات
GET /admin/dashboard/analytics/subscriptions - تحليلات الاشتراكات
```

---

#### 6. `/admin/users` - إدارة المستخدمين
**الحالة:** ⚠️ جزئياً (API موجود، الصفحة ناقصة)

**المتطلبات:**
- [ ] جدول بقائمة جميع المستخدمين
- [ ] فلاتر (الدور، الحالة، البحث)
- [ ] أزرار الإجراءات (عرض، تعديل، حظر، حذف)
- [ ] صفحة تفاصيل المستخدم
- [ ] صفحة تعديل بيانات المستخدم

**API Endpoints المطلوبة:**
```
GET /admin/users - قائمة المستخدمين
GET /admin/users/{id} - تفاصيل المستخدم
PUT /admin/users/{id} - تعديل المستخدم
DELETE /admin/users/{id} - حذف المستخدم
POST /admin/users/{id}/block - حظر المستخدم
POST /admin/users/{id}/unblock - إلغاء الحظر
```

---

#### 7. `/admin/subscriptions` - إدارة الاشتراكات
**الحالة:** ⚠️ جزئياً (API موجود، الصفحة ناقصة)

**المتطلبات:**
- [ ] جدول بقائمة الباقات
- [ ] أزرار الإجراءات (عرض، تعديل، حذف)
- [ ] صفحة إنشاء باقة جديدة
- [ ] صفحة تعديل الباقة
- [ ] عرض الأطباء المشتركين في كل باقة
- [ ] إحصائيات الاشتراكات

**API Endpoints المطلوبة:**
```
GET /admin/subscriptions - قائمة الباقات
GET /admin/subscriptions/{id} - تفاصيل الباقة
POST /admin/subscriptions - إنشاء باقة جديدة
PUT /admin/subscriptions/{id} - تعديل الباقة
DELETE /admin/subscriptions/{id} - حذف الباقة
GET /admin/subscriptions/{id}/doctors - الأطباء المشتركين
GET /admin/subscriptions/stats - إحصائيات الاشتراكات
```

---

### Doctor Dashboard Pages

#### 1. `/doctor/dashboard/patients` - إدارة المرضى
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] جدول بقائمة مرضى الطبيب
- [ ] فلاتر (البحث، آخر زيارة)
- [ ] أزرار الإجراءات (عرض، السجل الطبي، المواعيد)
- [ ] صفحة تفاصيل المريض
- [ ] عرض السجل الطبي الكامل
- [ ] عرض جميع المواعيد مع المريض

**API Endpoints المطلوبة:**
```
GET /doctor/dashboard/patients - قائمة المرضى
GET /doctor/dashboard/patients/{id} - تفاصيل المريض
GET /doctor/dashboard/patients/{id}/medical-records - السجل الطبي
GET /doctor/dashboard/patients/{id}/appointments - المواعيد
```

---

#### 2. `/doctor/dashboard/prescriptions` - الوصفات الطبية
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] جدول بقائمة الوصفات
- [ ] فلاتر (التاريخ، المريض)
- [ ] أزرار الإجراءات (عرض، تعديل، حذف)
- [ ] صفحة إنشاء وصفة جديدة
- [ ] صفحة تعديل الوصفة
- [ ] طباعة الوصفة

**API Endpoints المطلوبة:**
```
GET /doctor/dashboard/prescriptions - قائمة الوصفات
GET /doctor/dashboard/prescriptions/{id} - تفاصيل الوصفة
POST /doctor/dashboard/prescriptions - إنشاء وصفة
PUT /doctor/dashboard/prescriptions/{id} - تعديل الوصفة
DELETE /doctor/dashboard/prescriptions/{id} - حذف الوصفة
GET /doctor/dashboard/prescriptions/{id}/print - طباعة الوصفة
```

---

#### 3. `/doctor/dashboard/records` - السجلات الطبية
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] جدول بقائمة السجلات
- [ ] فلاتر (النوع، التاريخ، المريض)
- [ ] أزرار الإجراءات (عرض، حذف)
- [ ] صفحة إنشاء سجل جديد
- [ ] صفحة عرض السجل الكامل
- [ ] رفع الملفات المرفقة

**API Endpoints المطلوبة:**
```
GET /doctor/dashboard/records - قائمة السجلات
GET /doctor/dashboard/records/{id} - تفاصيل السجل
POST /doctor/dashboard/records - إنشاء سجل
DELETE /doctor/dashboard/records/{id} - حذف السجل
POST /doctor/dashboard/records/{id}/attachments - رفع ملف
```

---

#### 4. `/doctor/dashboard/calendar` - التقويم والمواعيد
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] تقويم شهري/أسبوعي/يومي
- [ ] عرض المواعيد على التقويم
- [ ] أزرار الإجراءات (تأكيد، إكمال، إلغاء)
- [ ] إضافة مواعيد يدوية
- [ ] إدارة الجداول (الأوقات المتاحة)

**API Endpoints المطلوبة:**
```
GET /doctor/dashboard/calendar - المواعيد
GET /doctor/dashboard/calendar/{date} - مواعيد يوم معين
POST /doctor/dashboard/appointments/{id}/confirm - تأكيد الموعد
POST /doctor/dashboard/appointments/{id}/complete - إكمال الموعد
POST /doctor/dashboard/appointments/{id}/cancel - إلغاء الموعد
```

---

#### 5. `/doctor/dashboard/settings` - الإعدادات
**الحالة:** ❌ ناقصة

**المتطلبات:**
- [ ] تحديث البيانات الشخصية
- [ ] تحديث كلمة المرور
- [ ] إدارة الفروع
- [ ] إدارة الجداول
- [ ] إدارة الاشتراك
- [ ] الإشعارات والتنبيهات

**API Endpoints المطلوبة:**
```
GET /doctor/dashboard/settings - الإعدادات
PUT /doctor/dashboard/settings/profile - تحديث البيانات
PUT /doctor/dashboard/settings/password - تحديث كلمة المرور
GET /doctor/dashboard/settings/branches - الفروع
POST /doctor/dashboard/settings/branches - إضافة فرع
PUT /doctor/dashboard/settings/branches/{id} - تعديل فرع
DELETE /doctor/dashboard/settings/branches/{id} - حذف فرع
GET /doctor/dashboard/settings/subscription - الاشتراك الحالي
```

---

## 📊 ملخص الحالة

| الصفحة | الحالة | الأولوية |
|--------|--------|---------|
| Admin Login | ✅ | - |
| Admin Dashboard | ✅ | - |
| Admin Doctors | ❌ | High |
| Admin Patients | ❌ | High |
| Admin Appointments | ❌ | High |
| Admin Revenue | ❌ | Medium |
| Admin Analytics | ❌ | Medium |
| Admin Users | ⚠️ | High |
| Admin Subscriptions | ⚠️ | High |
| Doctor Login | ✅ | - |
| Doctor Dashboard | ✅ | - |
| Doctor Patients | ❌ | High |
| Doctor Prescriptions | ❌ | High |
| Doctor Records | ❌ | High |
| Doctor Calendar | ❌ | Medium |
| Doctor Settings | ❌ | Medium |

---

## 🚀 خطة التطوير

### Phase 1 (Critical) - الصفحات الأساسية
1. Admin Doctors (list + details)
2. Admin Patients (list + details)
3. Admin Appointments (list + details)
4. Doctor Patients (list + details)
5. Doctor Prescriptions (list + create)
6. Doctor Records (list + create)

### Phase 2 (High) - الصفحات المتقدمة
1. Admin Revenue (charts + reports)
2. Admin Analytics (detailed analytics)
3. Admin Users (complete management)
4. Admin Subscriptions (complete management)
5. Doctor Calendar (calendar view)
6. Doctor Settings (profile + subscription)

### Phase 3 (Medium) - الميزات الإضافية
1. Export/Print functionality
2. Advanced filters
3. Bulk actions
4. Notifications
5. Activity logs

---

## 📝 ملاحظات

- جميع الصفحات يجب أن تستخدم نفس Layout
- جميع الصفحات يجب أن تكون responsive
- جميع الصفحات يجب أن تحتوي على error handling
- جميع الصفحات يجب أن تحتوي على loading states
- جميع الصفحات يجب أن تحتوي على confirmation dialogs للحذف

---

**الأولوية:** يجب إكمال Phase 1 قبل الإطلاق للإنتاج
