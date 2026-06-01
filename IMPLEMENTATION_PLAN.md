# خطة تنفيذ مشروع أطباء العراق — Backend

## 🎯 نظرة عامة
- **Framework:** Laravel 11
- **Database:** MySQL
- **Architecture:** Modular Service Strategy
- **API Version:** v1
- **Authentication:** Laravel Sanctum

---

## 📋 خطة التنفيذ

### Phase 1: Auth Module ✅ (مكتمل)

#### الملفات المنشأة:

**1. Models:**
- `Modules/Auth/Models/User.php` — نموذج المستخدم
- `Modules/Auth/Models/Otp.php` — نموذج OTP

**2. Migrations:**
- `Modules/Auth/database/migrations/2024_01_01_000001_create_users_table.php`
- `Modules/Auth/database/migrations/2024_01_01_000002_create_otps_table.php`

**3. Services:**
- `Modules/Auth/Services/Api/AuthService.php` — خدمة المصادقة

**4. Requests (Validation):**
- `Modules/Auth/Http/Requests/Api/RegisterRequest.php`
- `Modules/Auth/Http/Requests/Api/LoginRequest.php`
- `Modules/Auth/Http/Requests/Api/SendOtpRequest.php`
- `Modules/Auth/Http/Requests/Api/VerifyOtpRequest.php`

**5. Controllers:**
- `Modules/Auth/Http/Controllers/Api/AuthController.php`

**6. Routes:**
- `Modules/Auth/Routes/api.php` — API routes
- `routes/api.php` — Main API router

**7. Configuration:**
- `Modules/Auth/Providers/AuthServiceProvider.php`
- `config/auth.php` — تحديث Sanctum guard
- `bootstrap/providers.php` — تسجيل Provider

---

## 🔌 API Endpoints — Auth Module

### Base URL: `http://localhost/api/v1`

### 1. التسجيل (Register)
```
POST /auth/register
Content-Type: application/json

{
  "name": "أحمد محمد",
  "phone": "9647501234567",
  "email": "ahmed@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "role": "patient"
}

Response (201):
{
  "success": true,
  "data": {
    "user": {
      "id": "uuid",
      "name": "أحمد محمد",
      "phone": "9647501234567",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abc123..."
  },
  "message": "تم التسجيل بنجاح"
}
```

### 2. الدخول (Login)
```
POST /auth/login
Content-Type: application/json

{
  "phone": "9647501234567",
  "password": "SecurePass123"
}

Response (200):
{
  "success": true,
  "data": {
    "user": {
      "id": "uuid",
      "name": "أحمد محمد",
      "phone": "9647501234567",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abc123..."
  },
  "message": "تم الدخول بنجاح"
}
```

### 3. إرسال OTP
```
POST /auth/send-otp
Content-Type: application/json

{
  "phone": "9647501234567",
  "type": "login"  // login | register | reset_password
}

Response (200):
{
  "success": true,
  "data": {
    "phone": "9647501234567",
    "type": "login"
  },
  "message": "تم إرسال الكود بنجاح"
}
```

### 4. التحقق من OTP
```
POST /auth/verify-otp
Content-Type: application/json

{
  "phone": "9647501234567",
  "code": "123456",
  "type": "login"
}

Response (200):
{
  "success": true,
  "data": {
    "phone": "9647501234567",
    "type": "login",
    "verified": true
  },
  "message": "تم التحقق بنجاح"
}
```

### 5. التسجيل عبر OTP
```
POST /auth/register-with-otp
Content-Type: application/json

{
  "phone": "9647501234567",
  "code": "123456",
  "name": "أحمد محمد",
  "email": "ahmed@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "role": "patient"
}

Response (201):
{
  "success": true,
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  },
  "message": "تم التسجيل بنجاح"
}
```

### 6. الخروج (Logout)
```
POST /auth/logout
Authorization: Bearer <token>

Response (200):
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

### 7. بيانات المستخدم الحالي (Me)
```
GET /auth/me
Authorization: Bearer <token>

Response (200):
{
  "success": true,
  "data": {
    "id": "uuid",
    "name": "أحمد محمد",
    "phone": "9647501234567",
    "email": "ahmed@example.com",
    "role": "patient",
    "status": "active"
  }
}
```

---

## 🚀 خطوات التشغيل

### 1. تثبيت المتطلبات
```bash
composer install
```

### 2. إعداد قاعدة البيانات
```bash
# نسخ .env.example إلى .env وتحديث بيانات قاعدة البيانات
cp .env.example .env

# توليد APP_KEY
php artisan key:generate

# تشغيل Migrations
php artisan migrate
```

### 3. تشغيل الخادم
```bash
php artisan serve
```

### 4. اختبار الـ API
استخدم Postman أو Insomnia لاختبار الـ Endpoints

---

## 📊 Database Schema

### Users Table
```sql
CREATE TABLE users (
  id UUID PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(20) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('patient', 'doctor', 'admin') DEFAULT 'patient',
  status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
  email_verified_at TIMESTAMP NULL,
  phone_verified_at TIMESTAMP NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### OTPs Table
```sql
CREATE TABLE otps (
  id UUID PRIMARY KEY,
  phone VARCHAR(20) NOT NULL,
  code VARCHAR(6) NOT NULL,
  type ENUM('register', 'login', 'reset_password') DEFAULT 'login',
  attempts INT DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## ✅ Validation Rules

### Register
- `name`: مطلوب، نص، أقصى 255 حرف
- `phone`: مطلوب، فريد، 10-15 رقم
- `email`: اختياري، بريد إلكتروني فريد
- `password`: مطلوب، 8 أحرف على الأقل، يجب تأكيده
- `role`: مطلوب، patient أو doctor

### Login
- `phone`: مطلوب، 10-15 رقم
- `password`: مطلوب، 8 أحرف على الأقل

### Send OTP
- `phone`: مطلوب، 10-15 رقم
- `type`: مطلوب، register | login | reset_password

### Verify OTP
- `phone`: مطلوب، 10-15 رقم
- `code`: مطلوب، 6 أرقام
- `type`: مطلوب، register | login | reset_password

---

## 🔐 Error Codes

| Code | Status | Description |
|------|--------|-------------|
| AUTH_INVALID_CREDENTIALS | 401 | بيانات الدخول غير صحيحة |
| OTP_INVALID | 401 | الكود غير صحيح أو منتهي الصلاحية |
| OTP_SEND_FAILED | 500 | فشل إرسال الكود |
| REGISTRATION_FAILED | 500 | فشل التسجيل |

---

## 📝 الخطوات التالية

### Phase 2: Doctor Module
- Doctor Model
- Doctor Service
- Doctor Search & Filters
- Doctor Schedule Management

### Phase 3: Appointment Module
- Appointment Model
- Appointment Service
- Booking Flow
- State Machine

### Phase 4: Payment Module
- Payment Service
- Payment Gateway Integration
- Webhook Handling

---

## 📚 الملفات المهمة

```
Modules/Auth/
├── Models/
│   ├── User.php
│   └── Otp.php
├── Services/Api/
│   └── AuthService.php
├── Http/
│   ├── Controllers/Api/
│   │   └── AuthController.php
│   ├── Requests/Api/
│   │   ├── RegisterRequest.php
│   │   ├── LoginRequest.php
│   │   ├── SendOtpRequest.php
│   │   └── VerifyOtpRequest.php
│   └── Middleware/
├── Routes/
│   └── api.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   └── 2024_01_01_000002_create_otps_table.php
│   └── seeders/
├── Providers/
│   └── AuthServiceProvider.php
└── config/
```

---

**آخر تحديث:** 31 مايو 2026
**الحالة:** Auth Module مكتمل وجاهز للاختبار
