# اختبار API — Auth Module

## 🧪 Postman Collection

### 1. Register (تسجيل جديد)
```
POST http://localhost:8000/api/v1/auth/register
Content-Type: application/json

{
  "name": "أحمد محمد",
  "phone": "9647501234567",
  "email": "ahmed@example.com",
  "password": "SecurePass123",
  "password_confirmation": "SecurePass123",
  "role": "patient"
}
```

**Expected Response (201):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "أحمد محمد",
      "phone": "9647501234567",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abc123def456..."
  },
  "message": "تم التسجيل بنجاح"
}
```

---

### 2. Login (الدخول)
```
POST http://localhost:8000/api/v1/auth/login
Content-Type: application/json

{
  "phone": "9647501234567",
  "password": "SecurePass123"
}
```

**Expected Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "أحمد محمد",
      "phone": "9647501234567",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abc123def456..."
  },
  "message": "تم الدخول بنجاح"
}
```

---

### 3. Send OTP (إرسال كود التحقق)
```
POST http://localhost:8000/api/v1/auth/send-otp
Content-Type: application/json

{
  "phone": "9647501234567",
  "type": "login"
}
```

**Expected Response (200):**
```json
{
  "success": true,
  "data": {
    "phone": "9647501234567",
    "type": "login"
  },
  "message": "تم إرسال الكود بنجاح"
}
```

---

### 4. Verify OTP (التحقق من الكود)
```
POST http://localhost:8000/api/v1/auth/verify-otp
Content-Type: application/json

{
  "phone": "9647501234567",
  "code": "123456",
  "type": "login"
}
```

**Expected Response (200):**
```json
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

---

### 5. Register with OTP (التسجيل عبر OTP)
```
POST http://localhost:8000/api/v1/auth/register-with-otp
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
```

**Expected Response (201):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "أحمد محمد",
      "phone": "9647501234567",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abc123def456..."
  },
  "message": "تم التسجيل بنجاح"
}
```

---

### 6. Get Current User (بيانات المستخدم الحالي)
```
GET http://localhost:8000/api/v1/auth/me
Authorization: Bearer 1|abc123def456...
```

**Expected Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "أحمد محمد",
    "phone": "9647501234567",
    "email": "ahmed@example.com",
    "role": "patient",
    "status": "active"
  }
}
```

---

### 7. Logout (الخروج)
```
POST http://localhost:8000/api/v1/auth/logout
Authorization: Bearer 1|abc123def456...
```

**Expected Response (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

---

## 🔴 Error Responses

### Invalid Credentials
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "بيانات الدخول غير صحيحة"
  }
}
```

### Invalid OTP
```json
{
  "success": false,
  "error": {
    "code": "OTP_INVALID",
    "message": "الكود غير صحيح أو منتهي الصلاحية"
  }
}
```

### Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "phone": [
      "رقم الهاتف غير صحيح"
    ],
    "password": [
      "كلمة المرور يجب أن تكون 8 أحرف على الأقل"
    ]
  }
}
```

---

## 📱 Flutter Integration

### Example Request
```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

Future<void> login(String phone, String password) async {
  final response = await http.post(
    Uri.parse('http://localhost:8000/api/v1/auth/login'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'phone': phone,
      'password': password,
    }),
  );

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    String token = data['data']['token'];
    // Save token to secure storage
  } else {
    print('Login failed');
  }
}
```

---

## 🛠️ Debugging Tips

1. **Check Migrations:** `php artisan migrate:status`
2. **Clear Cache:** `php artisan cache:clear`
3. **Check Routes:** `php artisan route:list`
4. **Database Logs:** Check `storage/logs/laravel.log`

---

**آخر تحديث:** 31 مايو 2026
