<script>
    (function () {
        const CSRF_REFRESH_URL = @json($csrfRefreshUrl);

        function setCsrfToken(token) {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && token) {
                meta.content = token;
            }
            document.querySelectorAll('input[name="_token"]').forEach((input) => {
                input.value = token;
            });
        }

        async function refreshCsrfToken() {
            try {
                const response = await fetch(CSRF_REFRESH_URL, {
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

        document.addEventListener('DOMContentLoaded', () => {
            setInterval(() => refreshCsrfToken(), 30 * 60 * 1000);

            document.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
                form.addEventListener('submit', async function (event) {
                    if (form.dataset.csrfRefreshed === '1') {
                        form.dataset.csrfRefreshed = '0';
                        return;
                    }

                    event.preventDefault();
                    await refreshCsrfToken();
                    form.dataset.csrfRefreshed = '1';
                    form.submit();
                });
            });
        });
    })();
</script>
