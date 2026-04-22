import axios from 'axios';

const config = window.__APP_CONFIG__ || {};

// SPA-specific admin endpoints (session auth + CSRF)
export const http = axios.create({
    baseURL: config.apiBaseUrl || '/admin/api',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

// Existing v1 API (Sanctum token or session). For SPA we rely on same session.
export const apiV1 = axios.create({
    baseURL: config.apiV1BaseUrl || '/api',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

function attachCsrf(instance) {
    instance.interceptors.request.use((req) => {
        const token =
            config.csrfToken ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            req.headers['X-CSRF-TOKEN'] = token;
        }
        return req;
    });

    instance.interceptors.response.use(
        (res) => res,
        (err) => {
            if (err?.response?.status === 401) {
                window.location.href = '/admin/auth';
            }
            return Promise.reject(err);
        }
    );
}

attachCsrf(http);
attachCsrf(apiV1);

export default http;
