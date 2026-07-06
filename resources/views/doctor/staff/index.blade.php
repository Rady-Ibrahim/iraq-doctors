@extends('doctor.layout')

@section('title', 'فريق العيادة')
@section('page-title', 'فريق العيادة')
@section('page-description', 'إدارة حسابات السكرتارية وصلاحياتهم')

@section('content')
<div class="flex justify-end mb-4">
    <button onclick="openAddStaffModal()" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">
        <i class="fas fa-user-plus ml-2"></i>إضافة سكرتير
    </button>
</div>

<div id="staffList" class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-8 text-center text-gray-500">
        <i class="fas fa-spinner fa-spin text-2xl text-teal-600"></i>
        <p class="mt-2">جاري تحميل الفريق...</p>
    </div>
</div>

<!-- Add Staff Modal -->
<div id="addStaffModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white">
            <h3 class="text-lg font-bold text-gray-800" id="staffModalTitle">إضافة سكرتير</h3>
            <button onclick="closeStaffModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="staffForm" onsubmit="saveStaff(event)" class="p-6 space-y-4">
            <input type="hidden" id="staffMemberId" value="">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">الاسم</label>
                <input type="text" id="staffName" required class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الهاتف</label>
                <input type="tel" id="staffPhone" required class="w-full px-4 py-2 border rounded-lg" placeholder="07xxxxxxxxx">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">البريد الإلكتروني (اختياري)</label>
                <input type="email" id="staffEmail" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div id="passwordFields">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">كلمة المرور</label>
                    <input type="password" id="staffPassword" minlength="8" class="w-full px-4 py-2 border rounded-lg">
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">تأكيد كلمة المرور</label>
                    <input type="password" id="staffPasswordConfirmation" minlength="8" class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">الصلاحيات</label>
                <div id="permissionsList" class="space-y-2 max-h-48 overflow-y-auto border rounded-lg p-3"></div>
            </div>
            <button type="submit" class="w-full py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">حفظ</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
let permissionCatalog = {};
let staffMembers = [];

async function loadPermissionsCatalog() {
    const data = await apiCall('/doctor/api/staff/permissions');
    if (data?.success) {
        permissionCatalog = data.data || {};
        renderPermissionCheckboxes([]);
    }
}

function renderPermissionCheckboxes(selected = []) {
    const container = document.getElementById('permissionsList');
    container.innerHTML = Object.entries(permissionCatalog).map(([key, label]) => `
        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
            <input type="checkbox" name="permissions" value="${key}" class="rounded text-teal-600"
                ${selected.includes(key) ? 'checked' : ''}>
            <span>${label}</span>
        </label>
    `).join('');
}

function getSelectedPermissions() {
    return Array.from(document.querySelectorAll('#permissionsList input[name="permissions"]:checked'))
        .map((input) => input.value);
}

async function loadStaff() {
    const data = await apiCall('/doctor/api/staff');
    if (!data?.success) {
        document.getElementById('staffList').innerHTML = '<div class="p-8 text-center text-red-500">تعذر تحميل الفريق</div>';
        return;
    }

    staffMembers = data.data || [];
    renderStaffList();
}

function renderStaffList() {
    const container = document.getElementById('staffList');

    if (!staffMembers.length) {
        container.innerHTML = `
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-user-nurse text-4xl mb-3 text-gray-300"></i>
                <p>لا يوجد سكرتارية بعد</p>
                <p class="text-sm mt-1">أضف سكرتيراً لمساعدتك في إدارة العيادة</p>
            </div>`;
        return;
    }

    container.innerHTML = `
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-right px-6 py-3 text-sm font-semibold text-gray-600">الاسم</th>
                    <th class="text-right px-6 py-3 text-sm font-semibold text-gray-600">الهاتف</th>
                    <th class="text-right px-6 py-3 text-sm font-semibold text-gray-600">الحالة</th>
                    <th class="text-right px-6 py-3 text-sm font-semibold text-gray-600">الصلاحيات</th>
                    <th class="text-right px-6 py-3 text-sm font-semibold text-gray-600">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                ${staffMembers.map((member) => `
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">${member.user?.name || '-'}</td>
                        <td class="px-6 py-4 text-sm text-gray-600" dir="ltr">${member.user?.phone || '-'}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${member.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">
                                ${member.status === 'active' ? 'نشط' : 'معطل'}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">${(member.permissions || []).length} صلاحية</td>
                        <td class="px-6 py-4">
                            <div class="flex gap-2">
                                <button onclick="editStaff(${member.id})" class="text-teal-600 hover:text-teal-800 text-sm">تعديل</button>
                                <button onclick="toggleStaffStatus(${member.id}, '${member.status}')" class="text-amber-600 hover:text-amber-800 text-sm">
                                    ${member.status === 'active' ? 'تعطيل' : 'تفعيل'}
                                </button>
                                <button onclick="deleteStaff(${member.id})" class="text-red-600 hover:text-red-800 text-sm">حذف</button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>`;
}

function openAddStaffModal() {
    document.getElementById('staffModalTitle').textContent = 'إضافة سكرتير';
    document.getElementById('staffMemberId').value = '';
    document.getElementById('staffForm').reset();
    document.getElementById('staffPassword').required = true;
    document.getElementById('staffPasswordConfirmation').required = true;
    document.getElementById('passwordFields').classList.remove('hidden');
    renderPermissionCheckboxes(Object.keys(permissionCatalog).filter((key) =>
        ['appointments.view', 'appointments.manage', 'patients.view', 'calendar.view'].includes(key)
    ));
    document.getElementById('addStaffModal').classList.remove('hidden');
    document.getElementById('addStaffModal').classList.add('flex');
}

function editStaff(memberId) {
    const member = staffMembers.find((item) => item.id === memberId);
    if (!member) return;

    document.getElementById('staffModalTitle').textContent = 'تعديل السكرتير';
    document.getElementById('staffMemberId').value = member.id;
    document.getElementById('staffName').value = member.user?.name || '';
    document.getElementById('staffPhone').value = member.user?.phone || '';
    document.getElementById('staffEmail').value = member.user?.email || '';
    document.getElementById('staffPassword').required = false;
    document.getElementById('staffPasswordConfirmation').required = false;
    document.getElementById('passwordFields').classList.remove('hidden');
    renderPermissionCheckboxes(member.permissions || []);
    document.getElementById('addStaffModal').classList.remove('hidden');
    document.getElementById('addStaffModal').classList.add('flex');
}

function closeStaffModal() {
    document.getElementById('addStaffModal').classList.add('hidden');
    document.getElementById('addStaffModal').classList.remove('flex');
}

async function saveStaff(event) {
    event.preventDefault();

    const memberId = document.getElementById('staffMemberId').value;
    const payload = {
        name: document.getElementById('staffName').value.trim(),
        phone: document.getElementById('staffPhone').value.trim(),
        email: document.getElementById('staffEmail').value.trim() || null,
        permissions: getSelectedPermissions(),
    };

    const password = document.getElementById('staffPassword').value;
    const passwordConfirmation = document.getElementById('staffPasswordConfirmation').value;

    if (!memberId) {
        if (!password || password.length < 8) {
            showError('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
            return;
        }
        payload.password = password;
        payload.password_confirmation = passwordConfirmation;
    } else if (password) {
        payload.password = password;
        payload.password_confirmation = passwordConfirmation;
    }

    showLoading();
    const endpoint = memberId ? `/doctor/api/staff/${memberId}` : '/doctor/api/staff';
    const data = memberId
        ? await apiCall(endpoint, { method: 'PUT', body: JSON.stringify(payload) })
        : await apiPost(endpoint, payload);
    hideLoading();

    if (!data?.success) {
        showError(data?.error?.message || data?.message || 'فشل حفظ البيانات');
        return;
    }

    showSuccess(data.message || 'تم الحفظ بنجاح');
    closeStaffModal();
    await loadStaff();
}

async function toggleStaffStatus(memberId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const confirmed = await confirmAction(newStatus === 'active' ? 'تفعيل هذا السكرتير؟' : 'تعطيل هذا السكرتير؟');
    if (!confirmed) return;

    const data = await apiCall(`/doctor/api/staff/${memberId}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status: newStatus }),
    });

    if (!data?.success) {
        showError(data?.error?.message || 'فشل تحديث الحالة');
        return;
    }

    showSuccess(data.message || 'تم تحديث الحالة');
    await loadStaff();
}

async function deleteStaff(memberId) {
    const confirmed = await confirmAction('حذف هذا السكرتير نهائياً؟');
    if (!confirmed) return;

    const data = await apiCall(`/doctor/api/staff/${memberId}`, { method: 'DELETE' });
    if (!data?.success) {
        showError(data?.error?.message || 'فشل الحذف');
        return;
    }

    showSuccess(data.message || 'تم الحذف بنجاح');
    await loadStaff();
}

window.addEventListener('load', async () => {
    await loadPermissionsCatalog();
    await loadStaff();
});
</script>
@endsection
