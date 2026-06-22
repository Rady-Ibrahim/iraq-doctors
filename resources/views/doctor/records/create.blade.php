@extends('doctor.layout')

@section('title', 'سجل طبي جديد')
@section('page-title', 'سجل طبي جديد')
@section('page-description', 'إضافة سجل طبي جديد للمريض')

@section('content')
@if(request('appointment_id'))
<div class="mb-6 bg-teal-50 border border-teal-200 rounded-lg p-4">
    <p class="text-teal-800 font-semibold"><i class="fas fa-link ml-2"></i>إضافة سجل طبي لموعد مكتمل من التطبيق</p>
    <p class="text-sm text-teal-700 mt-1">سيتم ربط السجل بالموعد رقم {{ request('appointment_id') }}</p>
</div>
@endif
<!-- Back Button -->
<div class="mb-6">
    <a href="/doctor/dashboard/records" class="text-teal-600 hover:text-teal-700 flex items-center gap-2">
        <i class="fas fa-arrow-right"></i>
        <span>العودة إلى السجلات</span>
    </a>
</div>

<!-- Record Form -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form id="recordForm" onsubmit="createRecord(event)">
                <div class="space-y-6">
                    <!-- Patient Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">المريض *</label>
                        <select id="patientId" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                            <option value="">اختر المريض</option>
                        </select>
                    </div>

                    <!-- Record Type -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">نوع السجل *</label>
                        <select id="recordType" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                            <option value="">اختر النوع</option>
                            <option value="diagnosis">تشخيص</option>
                            <option value="treatment">علاج</option>
                            <option value="lab_test">اختبار معملي</option>
                            <option value="imaging">تصوير</option>
                            <option value="surgery">جراحة</option>
                            <option value="consultation">استشارة</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">العنوان *</label>
                        <input type="text" id="recordTitle" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                            placeholder="عنوان السجل">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">الوصف *</label>
                        <textarea id="recordDescription" rows="5" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                            placeholder="اكتب وصفاً تفصيلياً للسجل"></textarea>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">المرفقات</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                            <input type="file" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                class="hidden" onchange="handleFileSelect(event)">
                            <label for="fileInput" class="cursor-pointer">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-gray-600">اضغط لرفع الملفات أو اسحب الملفات هنا</p>
                                <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG, DOC (حد أقصى 10MB لكل ملف)</p>
                            </label>
                        </div>
                        <div id="fileList" class="mt-4 space-y-2">
                            <!-- Selected files will be listed here -->
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ملاحظات</label>
                        <textarea id="recordNotes" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"
                            placeholder="ملاحظات إضافية"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
                            <i class="fas fa-save ml-2"></i>حفظ السجل
                        </button>
                        <a href="/doctor/dashboard/records" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center">
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
                <p>• حدد نوع السجل المناسب</p>
                <p>• اكتب عنواناً واضحاً</p>
                <p>• اكتب وصفاً تفصيلياً</p>
                <p>• أرفق الملفات الضرورية</p>
                <p>• أضف ملاحظات إذا لزم الأمر</p>
            </div>
        </div>

        <!-- Record Types Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">أنواع السجلات</h3>
            <div class="space-y-3">
                <div class="p-3 bg-blue-50 rounded-lg">
                    <p class="font-semibold text-blue-800">تشخيص</p>
                    <p class="text-xs text-blue-600">نتائج التشخيص الطبي</p>
                </div>
                <div class="p-3 bg-green-50 rounded-lg">
                    <p class="font-semibold text-green-800">علاج</p>
                    <p class="text-xs text-green-600">خطة العلاج الموصى بها</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg">
                    <p class="font-semibold text-purple-800">اختبار معملي</p>
                    <p class="text-xs text-purple-600">نتائج الفحوصات المخبرية</p>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg">
                    <p class="font-semibold text-orange-800">تصوير</p>
                    <p class="text-xs text-orange-600">نتائج الأشعة والتصوير</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let selectedFiles = [];
let linkedAppointmentId = null;

window.addEventListener('load', async function() {
    const urlParams = new URLSearchParams(window.location.search);
    linkedAppointmentId = urlParams.get('appointment_id');
    await loadPatients();
});

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

function handleFileSelect(event) {
    const files = event.target.files;
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Check file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert(`الملف ${file.name} يتجاوز الحد الأقصى المسموح (10MB)`);
            continue;
        }
        
        // Check file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!allowedTypes.includes(file.type)) {
            alert(`نوع الملف ${file.name} غير مدعوم`);
            continue;
        }
        
        selectedFiles.push(file);
    }
    
    renderFileList();
}

function renderFileList() {
    const container = document.getElementById('fileList');
    
    if (selectedFiles.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = selectedFiles.map((file, index) => `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-3">
                <i class="fas fa-file text-gray-500"></i>
                <div>
                    <p class="text-sm font-semibold text-gray-800">${file.name}</p>
                    <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
                </div>
            </div>
            <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    renderFileList();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

async function createRecord(event) {
    event.preventDefault();
    
    try {
        showLoading();
        
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('patient_id', document.getElementById('patientId').value);
        if (linkedAppointmentId) formData.append('appointment_id', linkedAppointmentId);
        formData.append('type', document.getElementById('recordType').value);
        formData.append('title', document.getElementById('recordTitle').value);
        formData.append('description', document.getElementById('recordDescription').value);
        formData.append('notes', document.getElementById('recordNotes').value);
        
        // Add files
        for (let i = 0; i < selectedFiles.length; i++) {
            formData.append('files[]', selectedFiles[i]);
        }

        const data = await apiUpload('/doctor/api/records', formData);

        if (data && data.success) {
            alert('تم إنشاء السجل بنجاح');
            window.location.href = '/doctor/dashboard/records';
        } else {
            alert(data.error?.message || 'فشل إنشاء السجل');
        }
    } catch (error) {
        console.error('Error creating record:', error);
        alert('حدث خطأ أثناء إنشاء السجل');
    } finally {
        hideLoading();
    }
}
</script>
@endsection
