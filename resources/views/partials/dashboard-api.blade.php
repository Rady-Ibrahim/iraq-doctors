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

        if (!data.success && data.error) {
            if (typeof showError === 'function') {
                showError(data.error.message || 'حدث خطأ');
            }
        }

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

        if (!data.success && data.error) {
            if (typeof showError === 'function') {
                showError(data.error.message || 'حدث خطأ');
            }
        }

        return data;
    }

    document.addEventListener('DOMContentLoaded', () => startSessionKeepalive());
</script>
