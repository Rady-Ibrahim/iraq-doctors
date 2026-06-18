# 🏥 أطباء العراق — Final Execution Plan
> **Architecture:** Modular Monolith → Microservices Migration Path  
> **Strategy:** Service-Oriented with Phased Delivery  
> **Version:** 1.0 — Production Roadmap

---

## 📌 نظرة عامة على الاستراتيجية

المشروع يتبع **Modular Service Strategy** وهي:
- كل Module مستقل بـ Service خاصة به
- سهل التحويل لـ Microservices مستقبلاً
- Shared Kernel للـ Auth والـ Database
- نبدأ بـ Monorepo ثم نفصل عند الحاجة

```
أطباء العراق
├── apps/
│   ├── mobile/          
│   ├── doctor-web/     
│   └── admin-web/       
├── services/
│   ├── auth-service/
│   ├── doctor-service/
│   ├── appointment-service/
│   ├── payment-service/
│   ├── chat-service/
│   ├── review-service/
│   ├── subscription-service/
│   └── notification-service/
└── shared/
    ├── database/
    ├── types/
    └── utils/
```

---

## 🎯 الأولويات — Priority Matrix

| الأولوية | المودول | السبب | Sprint |
|---------|---------|-------|--------|
| 🔴 P0 | Auth Service | كل شيء يعتمد عليه | S1 |
| 🔴 P0 | Doctor Service | Core Product | S1 |
| 🔴 P0 | Appointment Service | Core Revenue | S1-S2 |
| 🟠 P1 | Payment Service | تحصيل الإيرادات | S2 |
| 🟠 P1 | Notification Service | User Retention | S2 |
| 🟡 P2 | Review Service | Trust Building | S3 |
| 🟡 P2 | Chat Service | Engagement | S3 |
| 🟢 P3 | Subscription Service | Growth Revenue | S4 |
| 🟢 P3 | Admin Dashboard | Operations | S4 |
| 🔵 P4 | Analytics & Reports | Optimization | S5 |

---

## 🗺️ خريطة التنفيذ — Execution Roadmap

### Phase 1 — Foundation (Weeks 1–6)
**الهدف:** MVP قابل للاختبار مع Real Users

#### Sprint 1 (Week 1–2): Core Infrastructure
```
✅ Project Setup & Monorepo Config
✅ Database Schema (PostgreSQL)
✅ Auth Service
   ├── POST /auth/register (Patient + Doctor)
   ├── POST /auth/login (JWT + Refresh Token)
   ├── POST /auth/otp/send
   ├── POST /auth/otp/verify
   └── POST /auth/forgot-password
✅ Doctor Service (Basic)
   ├── GET  /doctors (list + filters)
   ├── GET  /doctors/:id (profile)
   ├── POST /doctors (register + upload docs)
   └── Admin approval flow
✅ CI/CD Pipeline Setup
```

#### Sprint 2 (Week 3–4): Appointments Core
```
✅ Appointment Service
   ├── POST /appointments (book)
   ├── GET  /appointments (patient/doctor view)
   ├── PUT  /appointments/:id/confirm
   ├── PUT  /appointments/:id/cancel
   ├── PUT  /appointments/:id/complete
   └── GET  /appointments/:id/available-slots
✅ Doctor Schedule Management
   ├── POST /doctors/:id/schedule
   ├── PUT  /doctors/:id/schedule
   └── GET  /doctors/:id/availability
✅ State Machine للـ Appointment
   Pending → Confirmed → Completed
                     ↘ Cancelled
                     ↘ No-Show
```

#### Sprint 3 (Week 5–6): Payment + Notifications
```
✅ Payment Service
   ├── POST /payments/initiate
   ├── POST /payments/webhook (gateway callback)
   ├── GET  /payments/:id/status
   └── POST /payments/:id/refund
✅ Notification Service
   ├── Push Notifications (FCM)
   ├── SMS Integration
   └── In-App Notifications
✅ Mobile App Screens (Patient)
   ├── Home Screen
   ├── Search & Filters
   ├── Doctor Profile
   ├── Booking Flow
   └── My Appointments
```

---

### Phase 2 — Engagement (Weeks 7–10)
**الهدف:** زيادة الـ Retention وبناء الثقة

#### Sprint 4 (Week 7–8): Reviews + Chat
```
✅ Review Service
   ├── POST /reviews (Completed appointments ONLY)
   ├── GET  /doctors/:id/reviews
   └── Backend Guard:
       IF appointment.status != 'completed' → 403
       IF review_exists → 409 Conflict
       IF patient != appointment.patient → 403
✅ Chat Service (WebSocket)
   ├── Text Messages
   ├── Image Upload
   ├── Document Sharing
   ├── Voice Notes
   └── Read Receipts (Sent/Delivered/Seen)
✅ Mobile App Screens
   ├── Chat List
   ├── Chat Room
   └── Review & Rating Screen
```

#### Sprint 5 (Week 9–10): Doctor Dashboard V1
```
✅ Doctor Web Dashboard
   ├── Overview & KPIs
   ├── Calendar (Daily/Weekly/Monthly)
   ├── Appointments Management
   ├── Patient Details View
   ├── Schedule Management
   └── Earnings Overview
```

---

### Phase 3 — Monetization (Weeks 11–14)
**الهدف:** تشغيل نظام الاشتراكات وتحقيق الإيرادات

#### Sprint 6 (Week 11–12): Subscription System
```
✅ Subscription Service
   ├── Plans Management (Basic/Professional/Premium)
   ├── Doctor Subscription Workflow
   │   POST /subscriptions/subscribe
   │   GET  /subscriptions/current
   │   POST /subscriptions/renew
   │   POST /subscriptions/cancel
   ├── Feature Gates (حجوزات مدفوعة، أناليتكس)
   └── Auto-renewal + Expiry Notifications

Subscription Plans:
┌─────────────┬──────────┬──────────────┬─────────────┐
│ Feature     │ Basic    │ Professional │ Premium     │
├─────────────┼──────────┼──────────────┼─────────────┤
│ Appointments│ 50/month │ Unlimited    │ Unlimited   │
│ Visibility  │ Normal   │ Higher       │ Featured    │
│ Analytics   │ ❌       │ ✅           │ ✅ Advanced │
│ Banner      │ ❌       │ ❌           │ ✅          │
│ Support     │ Standard │ Priority     │ Dedicated   │
└─────────────┴──────────┴──────────────┴─────────────┘
```

#### Sprint 7 (Week 13–14): Admin Dashboard V1
```
✅ Admin Web Dashboard
   ├── KPIs Overview
   │   ├── Total Users / Doctors / Appointments
   │   ├── Revenue (Subscriptions + Commissions)
   │   └── Avg Rating Platform-wide
   ├── Doctor Management (Approve/Reject/Suspend/Verify)
   ├── Patient Management (View/Block/Delete)
   ├── Appointment Management (View/Cancel/Refund)
   ├── Subscription Plan Management
   ├── Review Moderation (View/Delete Abuse)
   └── Articles & Promotions Management
```

---

### Phase 4 — Growth (Weeks 15–18)
**الهدف:** Optimization وتحضير الـ Scale

#### Sprint 8 (Week 15–16): Analytics + Polish
```
✅ Analytics Service
   ├── Daily Active Users
   ├── Appointment Conversion Rate
   ├── Revenue Charts
   ├── Doctor Performance Reports
   └── Patient Retention Metrics
✅ Mobile Polish
   ├── Performance Optimization
   ├── Offline Mode Basic
   ├── Push Notification Deep Links
   └── App Store Submission Prep
```

#### Sprint 9 (Week 17–18): QA + Launch
```
✅ Load Testing (Artillery/k6)
✅ Security Audit (OWASP Top 10)
✅ UAT with Real Doctors & Patients
✅ Soft Launch (Beta Group 500 Users)
✅ Monitoring Setup (Sentry + Datadog)
✅ Production Deployment
```

---

## 🏗️ Service Architecture Details

### Auth Service
```
Responsibilities:
  - JWT Token Management (Access + Refresh)
  - OTP via SMS
  - Role-Based Access Control (RBAC)
  - Doctor Document Upload & Verification Queue

Stack:
  - Node.js / NestJS
  - Redis (Token Blacklist + OTP Cache)
  - S3-compatible (Document Storage)

Endpoints:
  POST /auth/register
  POST /auth/login
  POST /auth/refresh
  POST /auth/logout
  POST /auth/otp/send
  POST /auth/otp/verify
  POST /auth/forgot-password
  POST /auth/reset-password
  POST /auth/doctor/upload-docs
```

### Doctor Service
```
Responsibilities:
  - Doctor Profile CRUD
  - Search & Filtering
  - Schedule Management
  - Availability Calculation
  - Subscription Feature Gates

Stack:
  - Node.js / NestJS
  - PostgreSQL + PostGIS (Geolocation)
  - Elasticsearch (Search & Filters)

Key Rules:
  - Doctor visible only if status = 'approved'
  - Availability respects schedule + existing appointments
  - Rating auto-recalculated on each new review

Endpoints:
  GET  /doctors                    (Search + Filters)
  GET  /doctors/:id                (Profile)
  PUT  /doctors/:id                (Update - Doctor Only)
  GET  /doctors/:id/availability   (Available Slots)
  POST /doctors/:id/schedule       (Set Working Hours)
  GET  /doctors/:id/reviews
  GET  /doctors/:id/stats          (Dashboard)
```

### Appointment Service
```
Responsibilities:
  - Booking Flow
  - Status State Machine
  - Slot Locking (Race Condition Prevention)
  - Cancellation & Refund Trigger

Stack:
  - Node.js / NestJS
  - PostgreSQL
  - Redis (Slot Locking with TTL)
  - Bull Queue (Status Transitions)

State Machine:
  PENDING ──→ CONFIRMED ──→ COMPLETED
     │              │
     └──→ CANCELLED ←──────────────┘
                │
              NO_SHOW (Auto after appointment time + 30min)

Endpoints:
  POST /appointments               (Book)
  GET  /appointments               (List - Patient/Doctor filtered)
  GET  /appointments/:id
  PUT  /appointments/:id/confirm   (Doctor Only)
  PUT  /appointments/:id/cancel    (Patient or Doctor)
  PUT  /appointments/:id/complete  (Doctor Only)
  PUT  /appointments/:id/no-show   (System Auto or Doctor)
```

### Payment Service
```
Responsibilities:
  - Payment Initiation & Verification
  - Webhook Handling (Gateway Callbacks)
  - Refund Processing
  - Earnings Calculation for Doctors

Stack:
  - Node.js / NestJS
  - PostgreSQL
  - Idempotency Keys (Prevent Duplicate Payments)

Supported Gateways (Phase 1):
  - Zain Cash
  - Asia Hawala
  - Card (Future)

Endpoints:
  POST /payments/initiate
  POST /payments/webhook
  GET  /payments/:id
  POST /payments/:id/refund
  GET  /doctors/:id/earnings
```

### Review Service
```
Responsibilities:
  - Review Submission (LOCKED behind Completed status)
  - Rating Aggregation
  - Abuse Detection & Reporting

Stack:
  - Node.js / NestJS
  - PostgreSQL

CRITICAL Business Rule:
  POST /reviews
  Guard Checks (in order):
    1. appointment.status === 'completed' → else 403
    2. req.user.id === appointment.patient_id → else 403
    3. !review_exists(appointment_id) → else 409
  
  After successful review:
    → Trigger: recalculate doctor.rating
    → Trigger: send thank-you notification to patient

Endpoints:
  POST /reviews
  GET  /doctors/:id/reviews
  GET  /reviews/:id
  POST /reviews/:id/report         (Flag as abusive)
  DELETE /reviews/:id              (Admin Only)
```

### Chat Service
```
Responsibilities:
  - Real-time Messaging (WebSocket)
  - Media Upload (Images, Docs, Voice)
  - Message Status Tracking

Stack:
  - Node.js / NestJS + Socket.IO
  - MongoDB (Messages)
  - S3-compatible (Media Files)
  - Redis (Online Status + Typing Indicators)

Events:
  Client → Server:
    message:send
    message:seen
    typing:start
    typing:stop
  
  Server → Client:
    message:received
    message:status_update
    user:online
    user:offline

REST Endpoints:
  GET  /chats                      (List Conversations)
  GET  /chats/:id/messages         (Message History)
  POST /chats/:id/media            (Upload Media)
```

### Notification Service
```
Responsibilities:
  - Push Notifications (FCM)
  - SMS Notifications
  - In-App Notification Center

Stack:
  - Node.js / NestJS
  - Firebase Admin SDK
  - Bull Queue (Retry + Scheduling)

Triggers:
  → New appointment booked
  → Appointment confirmed
  → Appointment cancelled
  → New message received
  → Appointment reminder (24h + 1h before)
  → Review request (after completion)
  → Subscription expiring soon
```

### Subscription Service
```
Responsibilities:
  - Plan Management
  - Doctor Subscription Lifecycle
  - Feature Flag Resolution
  - Auto-renewal Logic

Stack:
  - Node.js / NestJS
  - PostgreSQL
  - Bull Queue (Expiry Checks)

Feature Gate Example:
  canBook(doctor_id):
    if plan == 'basic' && monthly_bookings >= 50 → false
    if plan == 'professional' || 'premium' → true
  
  getVisibilityScore(doctor_id):
    basic → 1
    professional → 2
    premium → 3 (+ featured badge)
```

---

## 🗄️ Database Schema

```sql
-- Core Tables

CREATE TABLE users (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name          VARCHAR(255) NOT NULL,
  phone         VARCHAR(20) UNIQUE NOT NULL,
  email         VARCHAR(255) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('patient', 'doctor', 'admin') NOT NULL,
  status        ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
  created_at    TIMESTAMP DEFAULT NOW()
);

CREATE TABLE doctors (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id           UUID REFERENCES users(id),
  speciality_id     UUID REFERENCES specialities(id),
  bio               TEXT,
  experience_years  INT,
  consultation_fee  DECIMAL(10,2),
  rating            DECIMAL(3,2) DEFAULT 0,
  rating_count      INT DEFAULT 0,
  subscription_id   UUID REFERENCES doctor_subscriptions(id),
  status            ENUM('pending', 'approved', 'rejected', 'suspended'),
  location          GEOGRAPHY(POINT, 4326),
  created_at        TIMESTAMP DEFAULT NOW()
);

CREATE TABLE appointments (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  doctor_id        UUID REFERENCES doctors(id),
  patient_id       UUID REFERENCES patients(id),
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status           ENUM('pending','confirmed','completed','cancelled','no_show'),
  price            DECIMAL(10,2),
  payment_status   ENUM('pending','paid','refunded'),
  notes            TEXT,
  created_at       TIMESTAMP DEFAULT NOW()
);

CREATE TABLE reviews (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  appointment_id UUID UNIQUE REFERENCES appointments(id), -- UNIQUE prevents double review
  doctor_id      UUID REFERENCES doctors(id),
  patient_id     UUID REFERENCES patients(id),
  rating         SMALLINT CHECK (rating BETWEEN 1 AND 5),
  review         TEXT,
  is_flagged     BOOLEAN DEFAULT FALSE,
  created_at     TIMESTAMP DEFAULT NOW()
);

CREATE TABLE subscriptions (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name             VARCHAR(100) NOT NULL,
  price            DECIMAL(10,2),
  duration_days    INT,
  max_appointments INT, -- NULL = unlimited
  is_featured      BOOLEAN DEFAULT FALSE,
  has_analytics    BOOLEAN DEFAULT FALSE,
  has_banner       BOOLEAN DEFAULT FALSE,
  status           ENUM('active', 'inactive') DEFAULT 'active'
);

CREATE TABLE doctor_subscriptions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  doctor_id       UUID REFERENCES doctors(id),
  subscription_id UUID REFERENCES subscriptions(id),
  start_date      DATE NOT NULL,
  end_date        DATE NOT NULL,
  status          ENUM('active', 'expired', 'cancelled'),
  created_at      TIMESTAMP DEFAULT NOW()
);

CREATE TABLE payments (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  appointment_id UUID REFERENCES appointments(id),
  patient_id     UUID REFERENCES patients(id),
  amount         DECIMAL(10,2),
  payment_method VARCHAR(50),
  status         ENUM('pending','completed','failed','refunded'),
  transaction_id VARCHAR(255) UNIQUE,
  gateway_ref    VARCHAR(255),
  created_at     TIMESTAMP DEFAULT NOW()
);

-- Indexes for Performance
CREATE INDEX idx_doctors_speciality ON doctors(speciality_id);
CREATE INDEX idx_doctors_status ON doctors(status);
CREATE INDEX idx_appointments_doctor ON appointments(doctor_id);
CREATE INDEX idx_appointments_patient ON appointments(patient_id);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_reviews_doctor ON reviews(doctor_id);
CREATE INDEX idx_reviews_appointment ON reviews(appointment_id);
```

---

## 🔐 Permission Matrix

| Action | Patient | Doctor | Admin |
|--------|---------|--------|-------|
| Register | ✅ | ✅ | - |
| Search Doctors | ✅ | ✅ | ✅ |
| Book Appointment | ✅ | ❌ | ❌ |
| Confirm Appointment | ❌ | ✅ | ✅ |
| Complete Appointment | ❌ | ✅ | ✅ |
| Cancel Appointment | ✅ | ✅ | ✅ |
| Submit Review | ✅ (Completed only) | ❌ | ❌ |
| View Own Reviews | ✅ | ✅ | ✅ |
| Delete Review | ❌ | ❌ | ✅ |
| Manage Schedule | ❌ | ✅ | ✅ |
| Subscribe to Plan | ❌ | ✅ | ❌ |
| Manage Plans | ❌ | ❌ | ✅ |
| Approve Doctor | ❌ | ❌ | ✅ |
| Block User | ❌ | ❌ | ✅ |
| View All Data | ❌ | ❌ | ✅ |

---

## 📱 Mobile App — Screen Flow

```
App Launch
  ├── Onboarding (first time)
  └── Auth Check
       ├── Not Logged In → Login / Register
       └── Logged In → Home

Home
  ├── Search Bar → Search Results
  │                   └── Doctor Profile
  │                         ├── Book Appointment → Booking Flow
  │                         ├── Chat → Chat Room
  │                         └── Call
  ├── Categories → Filtered Doctors
  ├── Featured Doctors → Doctor Profile
  └── Articles → Article Detail

Booking Flow:
  Select Branch → Select Date → Select Time → Confirm → Payment → Success

Bottom Navigation:
  Home | Search | Appointments | Chat | Profile

My Appointments:
  Upcoming | Completed | Cancelled
    └── Completed → [Rate Now] button → Rating Screen

Profile:
  Edit Profile | Medical History | Settings | Logout
```

---

## 🌐 API Standards

### Request Headers
```
Authorization: Bearer <access_token>
Content-Type: application/json
Accept-Language: ar | en
X-Platform: ios | android | web
X-Version: 1.0.0
```

### Response Format (Standard)
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 150
  }
}
```

### Error Format
```json
{
  "success": false,
  "error": {
    "code": "REVIEW_NOT_ALLOWED",
    "message": "يمكنك التقييم فقط بعد اكتمال الموعد",
    "statusCode": 403
  }
}
```

### Error Codes Reference
| Code | Status | Description |
|------|--------|-------------|
| AUTH_INVALID_TOKEN | 401 | Token expired or invalid |
| AUTH_UNAUTHORIZED | 403 | No permission |
| DOCTOR_NOT_APPROVED | 403 | Doctor pending approval |
| SLOT_NOT_AVAILABLE | 409 | Appointment slot taken |
| REVIEW_NOT_ALLOWED | 403 | Appointment not completed |
| REVIEW_ALREADY_SUBMITTED | 409 | Review already exists |
| PAYMENT_FAILED | 402 | Payment gateway error |
| SUBSCRIPTION_LIMIT_REACHED | 403 | Monthly booking limit |

---

## 📊 Success Metrics & KPIs

### Phase 1 Targets (Month 1-3)
| Metric | Target |
|--------|--------|
| Registered Patients | 2,000 |
| Approved Doctors | 200 |
| Completed Appointments | 5,000 |
| App Crash Rate | < 0.1% |
| API Response Time (p95) | < 500ms |

### Phase 2 Targets (Month 4-6)
| Metric | Target |
|--------|--------|
| Total Patients | 10,000 |
| Total Doctors | 1,000 |
| Total Appointments | 30,000 |
| Rating Submission Rate | > 60% |
| Doctor Subscription Conversion | > 20% |
| Appointment Completion Rate | > 75% |
| Monthly Revenue | $10,000+ |

---

## 🔧 Tech Stack

### Backend
| Layer | Technology |
|-------|-----------|
| Runtime | Node.js 20 LTS |
| Framework | NestJS |
| Database | PostgreSQL 16 + PostGIS |
| Search | Elasticsearch |
| Cache | Redis |
| Queue | BullMQ |
| Real-time | Socket.IO |
| Storage | MinIO / AWS S3 |
| Auth | JWT + Refresh Tokens |

### Mobile
| Layer | Technology |
|-------|-----------|
| Framework | React Native (Expo) |
| State | Redux Toolkit + RTK Query |
| Navigation | React Navigation v7 |
| Maps | React Native Maps |
| Push | Firebase (FCM) |

### Web Dashboards
| Layer | Technology |
|-------|-----------|
| Framework | Next.js 14 |
| UI | Tailwind CSS + shadcn/ui |
| State | Zustand |
| Charts | Recharts |
| Tables | TanStack Table |

### DevOps
| Layer | Technology |
|-------|-----------|
| Containerization | Docker + Docker Compose |
| Orchestration | Kubernetes (Phase 2) |
| CI/CD | GitHub Actions |
| Monitoring | Sentry + Datadog |
| Logs | ELK Stack |

---

## ⚡ Non-Functional Requirements

### Performance
- API Response: p50 < 200ms, p95 < 500ms, p99 < 1s
- Chat Message Delivery: < 100ms
- Image Upload: < 5 seconds for 5MB

### Scalability
- Phase 1: Handle 1,000 concurrent users
- Phase 2: Handle 10,000 concurrent users
- Horizontal scaling via stateless services

### Security
- All passwords: bcrypt (rounds: 12)
- Sensitive data: AES-256 encryption
- HTTPS only (TLS 1.3)
- Rate limiting: 100 req/min per IP
- File uploads: virus scanning + type validation
- OWASP Top 10 compliance

### Availability
- Uptime Target: 99.9% (< 9h downtime/year)
- Database: Primary + Read Replica + Daily Backup
- Graceful degradation for non-critical services

---

## 🚀 V2 Features (Post-Launch)

After hitting Phase 2 targets:

1. **Video Consultation** — WebRTC integration
2. **E-Prescription** — Digital prescription generation
3. **AI Symptom Checker** — Pre-booking triage
4. **Lab Booking** — Integration with local labs
5. **Pharmacy Integration** — Medication ordering
6. **Wallet System** — In-app credit balance
7. **Loyalty Points** — Gamification for retention
8. **Insurance Integration** — Claims processing
9. **Referral Program** — Doctor referral rewards
10. **Doctor Verification Badge** — Enhanced trust system

---

## 📋 Definition of Done (Per Sprint)

Each feature is DONE when:
- [ ] Unit Tests coverage > 80%
- [ ] API documentation updated (Swagger)
- [ ] Code reviewed (PR approved by 2 devs)
- [ ] Staging deployment verified
- [ ] QA signoff
- [ ] Mobile screens match Figma designs
- [ ] Arabic & English copy verified
- [ ] Error states handled gracefully

---

*Last Updated: Sprint Planning v1.0*  
*Next Review: End of Phase 1*
