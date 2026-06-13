import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);

    function logout() {
        router.post('/logout');
    }

    return { user, logout };
}
