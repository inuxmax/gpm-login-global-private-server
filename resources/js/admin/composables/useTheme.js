import { onMounted, ref, watch } from 'vue';

const STORAGE_KEY = 'admin_theme';

function applyTheme(theme) {
    const root = document.documentElement;
    if (theme === 'dark') {
        root.classList.add('dark');
    } else {
        root.classList.remove('dark');
    }
}

export function useTheme() {
    const theme = ref(localStorage.getItem(STORAGE_KEY) || 'light');

    function setTheme(value) {
        theme.value = value === 'dark' ? 'dark' : 'light';
        localStorage.setItem(STORAGE_KEY, theme.value);
        applyTheme(theme.value);
    }

    function toggleTheme() {
        setTheme(theme.value === 'dark' ? 'light' : 'dark');
    }

    onMounted(() => applyTheme(theme.value));

    watch(theme, (v) => applyTheme(v));

    return { theme, setTheme, toggleTheme };
}

/** Call before Vue mount to avoid flash */
export function initThemeEarly() {
    applyTheme(localStorage.getItem(STORAGE_KEY) || 'light');
}
