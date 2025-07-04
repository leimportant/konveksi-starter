import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    if (value === 'system') {
        // const mediaQueryList = window.matchMedia('(prefers-color-scheme: dark)');
        // const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.remove('dark'); // Always remove dark class for system theme
    } else {
        document.documentElement.classList.remove('dark'); // Always remove dark class, regardless of value
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};



const handleSystemThemeChange = () => {

    document.documentElement.classList.remove('dark'); // Ensure dark class is removed on system theme change
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always ensure dark mode is off on initialization
    document.documentElement.classList.remove('dark');

    // Clear any saved appearance to prevent re-enabling dark mode
    localStorage.removeItem('appearance');
    setCookie('appearance', '', -1); // Delete the cookie

    // No need to call updateTheme here as we are explicitly disabling dark mode

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const appearance = ref<Appearance>('system');

    onMounted(() => {
        initializeTheme();

        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.removeItem('appearance');

        // Remove from cookie for SSR...
        setCookie('appearance', '', -1); // Set max-age to -1 to delete the cookie

        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
