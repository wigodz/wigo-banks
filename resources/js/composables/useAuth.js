import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const TOKEN_KEY = 'auth_token';

/**
 * Reads the one-time OAuth token shared by the backend (if any) and persists
 * it to localStorage so it survives across page loads.
 */
export function persistTokenFromPage() {
    const token = usePage().props.auth?.token;

    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
    }
}

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
}

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);

    function logout() {
        router.post(
            '/logout',
            {},
            {
                onFinish: () => clearToken(),
            },
        );
    }

    return { user, logout };
}
