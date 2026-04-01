import Swal from 'sweetalert2';

export function useToast() {
    const show = (
        message: string,
        type: 'success' | 'error' | 'warning' | 'info' = 'success',
    ) => {
        const colors = {
            success: '#22c55e',
            error: '#ef4444',
            warning: '#eab308',
            info: '#3b82f6',
        };

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            background: colors[type],
            color: '#ffffff',
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            },
        });

        Toast.fire({
            icon: type,
            title: message,
        });
    };

    const success = (message: string) => show(message, 'success');
    const error = (message: string) => show(message, 'error');
    const warning = (message: string) => show(message, 'warning');
    const info = (message: string) => show(message, 'info');

    return {
        show,
        success,
        error,
        warning,
        info,
    };
}
