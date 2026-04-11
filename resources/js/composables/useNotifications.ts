import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import echo from '@/echo';
import type { CrmNotification } from '@/types/crm';

export function useNotifications() {
    const unreadCount = ref(0);
    const notifications = ref<CrmNotification[]>([]);
    const loading = ref(false);

    async function fetchCount(): Promise<void> {
        const response = await fetch('/t/notifications/count', {
            headers: { Accept: 'application/json' },
        });
        const data = (await response.json()) as { count: number };
        unreadCount.value = data.count;
    }

    async function fetchNotifications(): Promise<void> {
        loading.value = true;
        try {
            const response = await fetch('/t/notifications', {
                headers: { Accept: 'application/json' },
            });
            const data = (await response.json()) as { data: CrmNotification[] };
            notifications.value = data.data;
        } finally {
            loading.value = false;
        }
    }

    async function markRead(id: string): Promise<void> {
        await fetch(`/t/notifications/${id}/read`, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content ?? '',
            },
        });
        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    }

    async function markAllRead(): Promise<void> {
        await fetch('/t/notifications/read-all', {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content ?? '',
            },
        });
        notifications.value.forEach((n) => {
            n.read_at = new Date().toISOString();
        });
        unreadCount.value = 0;
    }

    function listenForNew(): void {
        const page = usePage();
        const userId = (page.props.auth as { user: { id: number } }).user.id;
        echo.private(`App.Models.User.${userId}`).notification(
            (notification: CrmNotification) => {
                notifications.value.unshift(notification);
                unreadCount.value++;
            },
        );
    }

    return {
        unreadCount,
        notifications,
        loading,
        fetchCount,
        fetchNotifications,
        markRead,
        markAllRead,
        listenForNew,
    };
}
