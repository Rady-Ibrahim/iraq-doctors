# Firebase Auth — اختبار Postman (تطبيق المريض)

## Endpoint

```
POST {{base_url}}/api/v1/auth/firebase
Content-Type: application/json
Accept: application/json
```

---

## وضع الاختبار المحلي (بدون Firebase حقيقي)

في `.env` (محلي فقط):

```env
APP_ENV=local
FIREBASE_AUTH_TEST_MODE=true
FIREBASE_AUTH_TEST_KEY=postman-local-secret-change-me
```

### Header مطلوب

```
X-Auth-Test-Key: postman-local-secret-change-me
```

---

## 1) تسجيل مريض جديد

```json
POST /api/v1/auth/firebase

{
  "phone": "07901234567",
  "name": "أحمد تجريبي"
}
```

**Response 200:**

```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "أحمد تجريبي", "phone": "+9647901234567", "role": "patient" },
    "token": "1|...",
    "is_new_user": true
  }
}
```

الرقم يُخزَّن دائماً بصيغة **E.164**: `+9647901234567`

---

## 2) تسجيل دخول مريض موجود

نفس الطلب **بدون** `name`:

```json
{
  "phone": "07901234567"
}
```

أو:

```json
{
  "phone": "+9647901234567"
}
```

`is_new_user: false`

---

## 3) بعد Login — OneSignal (كما هو)

```
POST /api/v1/auth/devices/register
Authorization: Bearer {{token}}

{
  "player_id": "onesignal-subscription-id",
  "platform": "android"
}
```

---

## 4) me

```
GET /api/v1/auth/me
Authorization: Bearer {{token}}
```

---

## 5) أخطاء متوقعة

| Code | المعنى |
|------|--------|
| `NAME_REQUIRED` | مستخدم جديد بدون اسم |
| `WRONG_APP` | الرقم مسجل كدكتور/أدمن |
| `BLOCKED` | حساب محظور |
| `INVALID_PHONE` | رقم غير صحيح |
| `INVALID_FIREBASE_TOKEN` | token Firebase غير صالح (وضع الإنتاج) |

---

## وضع الإنتاج (Firebase حقيقي)

```env
FIREBASE_AUTH_TEST_MODE=false
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
```

```json
{
  "firebase_token": "eyJhbGciOiJSUzI1NiIs..."
}
```

بدون `X-Auth-Test-Key`.

---

## 6) رقم مصري (اختبار Firebase SMS)

```env
PHONE_DEFAULT_COUNTRY=20
```

أو أرسل الرقم بصيغة E.164 مباشرة:

```json
{
  "phone": "01020578795",
  "name": "مريض تجريبي"
}
```

يُخزَّن: `+201020578795`

```json
{
  "phone": "+201020578795"
}
```

---

## تفعيل هاتف الدكتور (ويب)

بعد التسجيل/الدخول يُوجَّه الدكتور إلى `/doctor/verify-phone`.

1. المتصفح يستدعي Firebase Web SDK → `signInWithPhoneNumber` → **SMS على الهاتف**
2. المستخدم يدخل الكود → يُرسل `firebase_token` للسيرفر
3. السيرفر يتحقق عبر Kreait ويضبط `phone_verified_at`

إعدادات `.env` المطلوبة:

```env
FIREBASE_CREDENTIALS=storage/app/firebase/firebase-credentials.json
# project_id و auth_domain يُقرآن تلقائياً من الملف أعلاه
FIREBASE_WEB_API_KEY=...   # من Firebase Console → Web API Key (غير موجود في service account)
```

في Firebase Console:
- تفعيل **Phone** في Authentication
- إضافة **Authorized domains** (localhost / نطاقك)
- للتطوير: أرقام اختبار في Phone numbers for testing (كود ثابت بدون SMS حقيقي)
- للإنتاج: خطة Blaze لإرسال SMS فعلي

---

## تحويل الأرقام القديمة إلى E.164

```bash
php artisan phones:normalize --dry-run
php artisan phones:normalize
```
