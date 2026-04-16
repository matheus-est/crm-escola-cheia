<script setup lang="ts">
import { Bell, Eye } from 'lucide-vue-next';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useNotifications } from '@/composables/useNotifications';

const {
    unreadCount,
    notifications,
    loading,
    fetchCount,
    fetchNotifications,
    markRead,
    markAllRead,
    listenForNew,
} = useNotifications();

onMounted(async () => {
    await fetchCount();
    listenForNew();
});

async function onOpen(open: boolean): Promise<void> {
    if (open) {
        await fetchNotifications();
    }
}

function relativeTime(isoString: string): string {
    const diff = Date.now() - new Date(isoString).getTime();
    const minutes = Math.floor(diff / 60000);
    if (minutes < 1) return 'agora';
    if (minutes < 60) return `${minutes}min`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h`;
    return `${Math.floor(hours / 24)}d`;
}
</script>

<template>
    <Popover @update:open="onOpen">
        <PopoverTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="h-5 w-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-80 p-0" align="end">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <span class="text-sm font-semibold">Notificações</span>
                <button
                    v-if="unreadCount > 0"
                    class="text-xs text-muted-foreground hover:text-foreground"
                    @click="markAllRead"
                >
                    Marcar todas como lidas
                </button>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <div
                    v-if="loading"
                    class="flex items-center justify-center py-8"
                >
                    <span class="text-sm text-muted-foreground"
                        >Carregando...</span
                    >
                </div>
                <div
                    v-else-if="notifications.length === 0"
                    class="flex items-center justify-center py-8"
                >
                    <span class="text-sm text-muted-foreground"
                        >Nenhuma notificação</span
                    >
                </div>
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="flex items-start gap-3 border-b px-4 py-3 last:border-0"
                    :class="{ 'bg-muted/30': notification.read_at === null }"
                >
                    <a
                        :href="notification.data.opportunity_url"
                        class="block min-w-0 flex-1"
                        @click="markRead(notification.id)"
                    >
                        <p class="text-sm leading-tight font-medium">
                            {{ notification.data.task_type_label }}
                        </p>
                        <p class="mt-0.5 text-xs text-primary hover:underline">
                            {{ notification.data.opportunity_guardian_name }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ relativeTime(notification.created_at) }}
                        </p>
                    </a>
                    <button
                        v-if="notification.read_at === null"
                        class="mt-0.5 text-muted-foreground hover:text-foreground"
                        title="Marcar como lida"
                        @click="markRead(notification.id)"
                    >
                        <Eye class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
