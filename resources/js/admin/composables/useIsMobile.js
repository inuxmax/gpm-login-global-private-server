import { onBeforeUnmount, ref } from 'vue';

const QUERY = '(max-width: 768px)';

export function useIsMobile() {
    const mql = window.matchMedia(QUERY);
    const isMobile = ref(mql.matches);

    const onChange = (e) => (isMobile.value = e.matches);
    mql.addEventListener('change', onChange);

    onBeforeUnmount(() => mql.removeEventListener('change', onChange));

    return isMobile;
}
