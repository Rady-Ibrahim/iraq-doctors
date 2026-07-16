@extends('doctor.layout')

@section('title', 'وصفة طبية جديدة')
@section('page-title', 'وصفة طبية جديدة')
@section('page-description', 'إضافة وصفة طبية جديدة للمريض')

@section('content')
<!-- Back Button -->
<div class="mb-6">
    <a href="/doctor/dashboard/prescriptions" class="text-teal-600 hover:text-teal-700 flex items-center gap-2">
        <i class="fas fa-arrow-right"></i>
        <span>العودة إلى الوصفات</span>
    </a>
</div>

<!-- Prescription Form -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form id="prescriptionForm" onsubmit="createPrescription(event)">
                <div class="space-y-6">
                    <!-- Patient Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">المريض *</label>
                        <select id="patientId" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                            <option value="">اختر المريض</option>
                        </select>
                    </div>

                    <!-- Diagnosis -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">التشخيص *</label>
                        <textarea id="diagnosis" rows="3" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                            placeholder="اكتب التشخيص"></textarea>
                    </div>

                    <!-- Medicines -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-700">الأدوية <span class="text-gray-400 font-normal">(اختياري إذا وُجدت إحالة)</span></label>
                            <button type="button" onclick="addMedicine()" class="text-teal-600 hover:text-teal-700 text-sm">
                                <i class="fas fa-plus ml-1"></i>إضافة دواء
                            </button>
                        </div>
                        <div id="medicinesList" class="space-y-4">
                            <!-- Medicine items will be added here -->
                        </div>
                    </div>

                    <!-- Referral -->
                    <div class="border-t pt-6">
                        <h3 class="text-md font-semibold text-gray-800 mb-1">إحالة المريض</h3>
                        <p class="text-xs text-gray-500 mb-4">اختياري — يمكن اختيار صيدلية فقط أو مختبر فقط أو الاثنين، بدون أدوية إذا أردت</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">صيدلية مرشّحة</label>
                                <select id="recommendedPharmacyId" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                    <option value="">— بدون —</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">مختبر مرشّح</label>
                                <select id="recommendedLaboratoryId" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                    <option value="">— بدون —</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">تحاليل مطلوبة (سطر لكل تحليل)</label>
                            <textarea id="labTests" rows="3" placeholder="مثال: CBC&#10;سكر صائم&#10;وظائف كلى"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ملاحظات</label>
                        <textarea id="notes" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                            placeholder="ملاحظات إضافية"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                            <i class="fas fa-save ml-2"></i>حفظ الوصفة
                        </button>
                        <a href="/doctor/dashboard/prescriptions" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center">
                            إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Instructions -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">تعليمات</h3>
            <div class="space-y-3 text-sm text-gray-600">
                <p>• اختر المريض من القائمة</p>
                <p>• اكتب التشخيص بدقة</p>
                <p>• أضف الأدوية المطلوبة</p>
                <p>• حدد الجرعة لكل دواء</p>
                <p>• أضف ملاحظات إذا لزم الأمر</p>
            </div>
        </div>

        <!-- Recent Prescriptions -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">الوصفات الأخيرة</h3>
            <div id="recentPrescriptions" class="space-y-3">
                <p class="text-gray-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let medicineCounter = 0;

window.addEventListener('load', async function() {
    await loadPatients();
    await loadRecentPrescriptions();
    await loadReferralOptions();
});

async function loadReferralOptions() {
    try {
        const data = await apiCall('/doctor/api/referral-options');
        if (!data?.success) return;
        const phSel = document.getElementById('recommendedPharmacyId');
        const labSel = document.getElementById('recommendedLaboratoryId');
        (data.data.pharmacies || []).forEach(p => {
            const o = document.createElement('option');
            o.value = p.id; o.textContent = p.name;
            phSel.appendChild(o);
        });
        (data.data.laboratories || []).forEach(l => {
            const o = document.createElement('option');
            o.value = l.id; o.textContent = l.name;
            labSel.appendChild(o);
        });
    } catch (e) {}
}

async function loadPatients() {
    try {
        const data = await apiCall('/doctor/api/patients?limit=100');
        
        if (data.success) {
            const select = document.getElementById('patientId');
            data.data.forEach(patient => {
                const option = document.createElement('option');
                option.value = patient.id;
                option.textContent = patient.name || 'غير محدد';
                select.appendChild(option);
            });

            // Pre-select patient if provided in URL
            const urlParams = new URLSearchParams(window.location.search);
            const patientId = urlParams.get('patient_id');
            if (patientId) {
                select.value = patientId;
            }
        }
    } catch (error) {
        console.error('Error loading patients:', error);
    }
}

async function loadRecentPrescriptions() {
    try {
        const data = await apiCall('/doctor/api/prescriptions?limit=5');
        
        if (data.success) {
            renderRecentPrescriptions(data.data);
        }
    } catch (error) {
        console.error('Error loading recent prescriptions:', error);
    }
}

function renderRecentPrescriptions(prescriptions) {
    const container = document.getElementById('recentPrescriptions');
    
    if (prescriptions.length === 0) {
        container.innerHTML = '<p class="text-gray-500">لا توجد وصفات</p>';
        return;
    }

    container.innerHTML = prescriptions.map(prescription => `
        <div class="p-3 bg-gray-50 rounded-lg">
            <p class="font-semibold text-gray-800">${prescription.patient_name || 'غير محدد'}</p>
            <p class="text-sm text-gray-600">${prescription.medicines_count || 0} دواء</p>
            <p class="text-xs text-gray-500">${formatDate(prescription.created_at)}</p>
        </div>
    `).join('');
}

function addMedicine() {
    medicineCounter++;
    const container = document.getElementById('medicinesList');
    
    const medicineDiv = document.createElement('div');
    medicineDiv.className = 'p-4 bg-gray-50 rounded-lg';
    medicineDiv.id = `medicine-${medicineCounter}`;
    
    medicineDiv.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">اسم الدواء</label>
                <input type="text" name="medicines[${medicineCounter}][name]"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                    placeholder="اسم الدواء">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">الجرعة</label>
                <input type="text" name="medicines[${medicineCounter}][dosage]"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                    placeholder="مثال: 500mg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">عدد المرات</label>
                <input type="number" name="medicines[${medicineCounter}][frequency]" min="1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                    placeholder="مثال: 3">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">المدة</label>
                <input type="text" name="medicines[${medicineCounter}][duration]"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                    placeholder="مثال: 7 أيام">
            </div>
        </div>
        <button type="button" onclick="removeMedicine(${medicineCounter})" class="mt-2 text-red-600 hover:text-red-700 text-sm">
            <i class="fas fa-trash ml-1"></i>حذف الدواء
        </button>
    `;
    
    container.appendChild(medicineDiv);
}

function removeMedicine(id) {
    const medicineDiv = document.getElementById(`medicine-${id}`);
    if (medicineDiv) {
        medicineDiv.remove();
    }
}

async function createPrescription(event) {
    event.preventDefault();
    
    try {
        showLoading();
        
        // Collect medicines
        const medicines = [];
        const medicineItems = document.querySelectorAll('[id^="medicine-"]');
        
        medicineItems.forEach(item => {
            const name = item.querySelector('[name*="[name]"]').value;
            const dosage = item.querySelector('[name*="[dosage]"]').value;
            const frequency = item.querySelector('[name*="[frequency]"]').value;
            const duration = item.querySelector('[name*="[duration]"]').value;
            
            if (name && dosage && frequency && duration) {
                medicines.push({ name, dosage, frequency, duration });
            }
        });

        const labTests = document.getElementById('labTests').value
            .split('\n').map(s => s.trim()).filter(Boolean);
        const phId = document.getElementById('recommendedPharmacyId').value;
        const labId = document.getElementById('recommendedLaboratoryId').value;

        if (medicines.length === 0 && !phId && !labId && labTests.length === 0) {
            alert('أضف دواء واحداً على الأقل، أو اختر صيدلية/مختبر مرشّح، أو اكتب تحاليل مطلوبة');
            hideLoading();
            return;
        }

        // أدوية ناقصة الحقول (بدأ يملأها ولم يكمل)
        const incompleteMedicine = Array.from(medicineItems).some(item => {
            const name = item.querySelector('[name*="[name]"]').value.trim();
            const dosage = item.querySelector('[name*="[dosage]"]').value.trim();
            const frequency = item.querySelector('[name*="[frequency]"]').value.trim();
            const duration = item.querySelector('[name*="[duration]"]').value.trim();
            const any = name || dosage || frequency || duration;
            const all = name && dosage && frequency && duration;
            return any && !all;
        });
        if (incompleteMedicine) {
            alert('أكمل بيانات الدواء (الاسم والجرعة والتكرار والمدة) أو احذف الصف الفارغ');
            hideLoading();
            return;
        }

        const payload = {
            patient_id: document.getElementById('patientId').value,
            diagnosis: document.getElementById('diagnosis').value,
            medicines: medicines,
            notes: document.getElementById('notes').value,
        };
        if (phId) payload.recommended_pharmacy_id = parseInt(phId);
        if (labId) payload.recommended_laboratory_id = parseInt(labId);
        if (labTests.length) payload.lab_tests = labTests;

        const data = await apiCall('/doctor/api/prescriptions', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (data.success) {
            alert('تم إنشاء الوصفة بنجاح');
            window.location.href = '/doctor/dashboard/prescriptions';
        } else {
            alert(data.error?.message || 'فشل إنشاء الوصفة');
        }
    } catch (error) {
        console.error('Error creating prescription:', error);
        alert('حدث خطأ أثناء إنشاء الوصفة');
    } finally {
        hideLoading();
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-IQ');
}
</script>
@endsection
