# 🔧 دليل الاستخدام من الـ Frontend

## 📌 الـ Endpoints الأساسية

### API Base URL
```
http://localhost:8000/api/v1/auth/
```

---

## 🔐 Authentication Endpoints

### 1️⃣ **التسجيل (Register)**

**Endpoint**: `POST /api/v1/auth/register`

**Request**:
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    name: 'أحمد محمد',
    phone: '07912345678',
    email: 'ahmed@example.com',
    password: 'SecurePassword123',
    password_confirmation: 'SecurePassword123',
    role: 'patient'  // or 'doctor'
  })
});

const data = await response.json();
```

**Success Response** (201):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "phone": "07912345678",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abcdef123456..."
  },
  "message": "تم التسجيل بنجاح"
}
```

**Validation Error** (422):
```json
{
  "success": false,
  "message": "خطأ في البيانات المدخلة",
  "errors": {
    "phone": ["رقم الهاتف مسجل بالفعل"],
    "password": ["كلمة المرور يجب أن تكون 8 أحرف على الأقل"]
  }
}
```

---

### 2️⃣ **تسجيل الدخول (Login)**

**Endpoint**: `POST /api/v1/auth/login`

**Request**:
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    phone: '07912345678',
    password: 'SecurePassword123'
  })
});

const data = await response.json();
if (data.success) {
  localStorage.setItem('token', data.data.token);
  console.log('تسجيل الدخول نجح!');
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "phone": "07912345678",
      "email": "ahmed@example.com",
      "role": "patient"
    },
    "token": "1|abcdef123456..."
  },
  "message": "تم الدخول بنجاح"
}
```

**Error Response** (401):
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "بيانات الدخول غير صحيحة"
  }
}
```

---

### 3️⃣ **إرسال رمز OTP (Send OTP)**

**Endpoint**: `POST /api/v1/auth/send-otp`

**Request**:
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/send-otp', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    phone: '07912345678',
    type: 'login'  // or 'register', 'reset_password'
  })
});

const data = await response.json();
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "phone": "07912345678",
    "type": "login"
  },
  "message": "تم إرسال الكود بنجاح"
}
```

---

### 4️⃣ **التحقق من OTP (Verify OTP)**

**Endpoint**: `POST /api/v1/auth/verify-otp`

**Request**:
```javascript
const response = await fetch('http://localhost:8000/api/v1/auth/verify-otp', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    phone: '07912345678',
    code: '123456',
    type: 'login'
  })
});

const data = await response.json();
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "phone": "07912345678",
    "type": "login",
    "verified": true
  },
  "message": "تم التحقق بنجاح"
}
```

---

## 🔒 Protected Endpoints

### بيانات المستخدم الحالي (Get Current User)

**Endpoint**: `GET /api/v1/auth/me`

**Request**:
```javascript
const token = localStorage.getItem('token');

const response = await fetch('http://localhost:8000/api/v1/auth/me', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
console.log(data.data.user);
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "أحمد محمد",
    "phone": "07912345678",
    "email": "ahmed@example.com",
    "role": "patient",
    "status": "active"
  }
}
```

**Error Response** (401):
```json
{
  "success": false,
  "error": {
    "code": "Unauthenticated",
    "message": "يجب تسجيل الدخول أولاً"
  }
}
```

---

### تحديث البيانات الشخصية (Update Profile)

**Endpoint**: `PUT /api/v1/auth/profile`

**Request**:
```javascript
const token = localStorage.getItem('token');

const response = await fetch('http://localhost:8000/api/v1/auth/profile', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'أحمد علي',
    email: 'newemail@example.com'
  })
});

const data = await response.json();
```

**Success Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "أحمد علي",
    "phone": "07912345678",
    "email": "newemail@example.com",
    "role": "patient"
  },
  "message": "تم تحديث البيانات الشخصية بنجاح"
}
```

---

### تسجيل الخروج (Logout)

**Endpoint**: `POST /api/v1/auth/logout`

**Request**:
```javascript
const token = localStorage.getItem('token');

const response = await fetch('http://localhost:8000/api/v1/auth/logout', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const data = await response.json();
if (data.success) {
  localStorage.removeItem('token');
  window.location.href = '/login';
}
```

**Success Response** (200):
```json
{
  "success": true,
  "data": null,
  "message": "تم تسجيل الخروج بنجاح"
}
```

---

## 📊 Dashboard Endpoints

### Admin Dashboard

**الـ Base URL**: `/admin/dashboard/`

```
GET    /admin/dashboard/metrics         - الإحصائيات العامة
GET    /admin/dashboard/doctors         - إحصائيات الأطباء
GET    /admin/dashboard/patients        - إحصائيات المرضى
GET    /admin/dashboard/appointments    - إحصائيات المواعيد
GET    /admin/dashboard/revenue         - الإيرادات
GET    /admin/dashboard/analytics       - التحليلات
POST   /admin/dashboard/doctors/{id}/approve   - الموافقة على طبيب
POST   /admin/dashboard/doctors/{id}/reject    - رفض طبيب
```

**Example Request**:
```javascript
const token = localStorage.getItem('token');

const response = await fetch('http://localhost:8000/admin/dashboard/metrics', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const data = await response.json();
console.log(data.data); // metrics data
```

---

### Doctor Dashboard

**الـ Base URL**: `/doctor/dashboard/`

```
GET    /doctor/dashboard/metrics         - الإحصائيات
GET    /doctor/dashboard/patients        - قائمة المرضى
GET    /doctor/dashboard/patients/{id}   - بيانات المريض
GET    /doctor/dashboard/today-activity  - نشاط اليوم
GET    /doctor/dashboard/upcoming-tasks  - المهام القادمة
GET    /doctor/dashboard/prescriptions   - الوصفات
```

---

## 🛠️ Utility Functions

### Example: Fetch Helper

```javascript
// src/api/client.js

const API_BASE_URL = 'http://localhost:8000/api/v1';

export async function apiCall(endpoint, options = {}) {
  const token = localStorage.getItem('token');
  
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.message || 'API Error');
  }

  return data;
}

// Usage:
try {
  const result = await apiCall('/auth/login', {
    method: 'POST',
    body: JSON.stringify({
      phone: '07912345678',
      password: 'password123'
    })
  });
  console.log(result.data.token);
} catch (error) {
  console.error('Login failed:', error.message);
}
```

---

## ⚠️ معالجة الأخطاء

```javascript
async function handleApiError(response) {
  const data = await response.json();
  
  if (data.errors) {
    // Validation errors
    Object.entries(data.errors).forEach(([field, messages]) => {
      console.error(`${field}: ${messages.join(', ')}`);
    });
  }
  
  if (response.status === 401) {
    // Unauthorized - remove token and redirect to login
    localStorage.removeItem('token');
    window.location.href = '/login';
  }
  
  if (response.status === 403) {
    // Forbidden - show permission error
    console.error('ليس لديك صلاحية للوصول لهذا المورد');
  }
}
```

---

**آخر تحديث**: 13 يونيو 2026
