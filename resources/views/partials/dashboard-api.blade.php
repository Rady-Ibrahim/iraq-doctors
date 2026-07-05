<script>
    const DASHBOARD_LOGIN_URL = @json($loginUrl);
    const DASHBOARD_CSRF_REFRESH_URL = @json($csrfRefreshUrl);

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function setCsrfToken(token) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && token) {
            meta.content = token;
        }

        document.querySelectorAll('input[name="_token"]').forEach((input) => {
            input.value = token;
        });
    }

    function goToLogin() {
        window.location.replace(DASHBOARD_LOGIN_URL);
    }

    async function refreshCsrfToken() {
        try {
            const response = await fetch(DASHBOARD_CSRF_REFRESH_URL, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return false;
            }

            const data = await response.json();
            if (data.token) {
                setCsrfToken(data.token);
                return true;
            }
        } catch (e) {
            return false;
        }

        return false;
    }

    function startSessionKeepalive(intervalMs = 30 * 60 * 1000) {
        setInterval(() => refreshCsrfToken(), intervalMs);
    }

    function clearFieldErrors() {
        document.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
        });
    }

    function showFieldErrors(details) {
        if (!details || typeof details !== 'object') {
            return;
        }

        clearFieldErrors();

        Object.entries(details).forEach(([field, messages]) => {
            const el = document.querySelector(`[data-error-for="${field}"]`);
            if (el && Array.isArray(messages) && messages.length > 0) {
                el.textContent = messages[0];
            }
        });
    }

    function handleApiError(data) {
        if (!data || data.success !== false || !data.error) {
            return;
        }

        if (data.error.code === 'VALIDATION_ERROR' && data.error.details) {
            showFieldErrors(data.error.details);
            return;
        }

        if (typeof showError === 'function') {
            showError(data.error.message || 'حدث خطأ');
        }
    }

    async function apiCall(endpoint, options = {}, retried = false) {
        const response = await fetch(endpoint, {
            ...options,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        });

        if (response.status === 419 && !retried) {
            if (await refreshCsrfToken()) {
                return apiCall(endpoint, options, true);
            }
        }

        if (response.status === 401 || response.status === 419) {
            goToLogin();
            return;
        }

        const data = await response.json();

        handleApiError(data);

        return data;
    }

    async function apiUpload(endpoint, formData, method = 'POST', retried = false) {
        const response = await fetch(endpoint, {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        if (response.status === 419 && !retried) {
            if (await refreshCsrfToken()) {
                return apiUpload(endpoint, formData, method, true);
            }
        }

        if (response.status === 401 || response.status === 419) {
            goToLogin();
            return;
        }

        const data = await response.json();

        handleApiError(data);

        return data;
    }

    document.addEventListener('DOMContentLoaded', () => startSessionKeepalive());
</script>
