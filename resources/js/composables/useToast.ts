// resources/js/composables/useToast.ts
import { useToast as useToastify } from 'vue-toastification';

export function useToast() {
    const toast = useToastify();
    return {
        success: (msg: string) => toast.success(msg),
        error: (msg: string) => toast.error(msg),
        info: (msg: string) => toast.info(msg),
        warning: (msg: string) => toast.warning(msg),
    };
}
