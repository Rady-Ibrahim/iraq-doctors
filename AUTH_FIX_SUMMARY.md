# حل مشكلة API Auth - الرد برسالة HTML بدلاً من JSON

## المشكلة ❌
عند استدعاء `/api/v1/auth/register` أو `/api/v1/auth/login`:
- كان يرجع صفحة HTML بدلاً من JSON
- خاصة عند فشل validation (مثل phone مش صحيح)

## السبب 🔍
Laravel's `FormRequest` class بشكل افتراضي:
- عند فشل validation، ترجع **redirect response** (صفحة HTML)
- لا تفهم أنها في API context

## الحل ✅
تم إضافة:

### 1. Base FormRequest Class للـ API
**الملف**: `app/Http/Requests/ApiFormRequest.php`
- يوفر `failedValidation()` method
- يرجع JSON response عند فشل validation بدلاً من redirect

### 2. تحديث جميع API FormRequests
تم تحديث جميع Request classes في الـ modules:
- **Auth Module**: 9 requests
- **Doctor Module**: 2 requests  
- **Appointment Module**: 1 request
- **Review Module**: 1 request
- **MedicalRecord Module**: 2 requests
- **StaticPage Module**: 2 requests

### مثال Response الآن:
```json
// عند فشل validation
{
  "success": false,
  "message": "خطأ في البيانات المدخلة",
  "errors": {
    "phone": ["رقم الهاتف غير صحيح"],
    "password": ["كلمة المرور يجب أن تكون 8 أحرف على الأقل"]
  }
}
```

## الاستخدام من الكلاينت

تأكد من:
1. إرسال `Content-Type: application/json` header
2. استخدام `/api/v1/auth/register` (not `/auth/register`)

```javascript
// صحيح ✅
fetch('/api/v1/auth/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'أحمد',
    phone: '07912345678',
    email: 'ahmed@example.com',
    password: 'password123',
    password_confirmation: 'password123',
    role: 'patient'
  })
})
```

## Dashboard Routes (قادمة)
سيتم إنشاء:
- Dashboard للـ Admin (`/dashboard/admin/*`)
- Dashboard للـ Doctor (`/dashboard/doctor/*`)
- كلاهما محمي بـ `auth:sanctum` middleware

---
**آخر تحديث**: 13 يونيو 2026
