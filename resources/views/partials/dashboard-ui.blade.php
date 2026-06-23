<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .swal2-popup { font-family: 'Cairo', sans-serif !important; direction: rtl; text-align: right; }
    .swal2-actions { flex-direction: row-reverse; }
</style>
<script>
    async function confirmAction(message, options = {}) {
        const result = await Swal.fire({
            title: options.title || 'تأكيد الإجراء',
            text: message,
            icon: options.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: options.confirmColor || '{{ $confirmColor ?? '#3b82f6' }}',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: options.confirmText || 'نعم، متابعة',
            cancelButtonText: options.cancelText || 'إلغاء',
            reverseButtons: true,
            focusCancel: true,
        });
        return result.isConfirmed;
    }

    window.alert = function(message) {
        const text = String(message ?? '');
        const isSuccess = /تم |نجاح|بنجاح|تمت/.test(text);
        Swal.fire({
            text,
            icon: isSuccess ? 'success' : 'error',
            confirmButtonText: 'حسناً',
            confirmButtonColor: isSuccess ? '#10b981' : '#ef4444',
        });
    };
</script>
