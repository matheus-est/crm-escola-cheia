<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle,
    Chrome,
    Computer,
    Globe,
    Laptop,
    LogOut,
    Monitor,
    Smartphone,
    Tablet,
    XCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';
import PerPageSelect from '@/components/PerPageSelect.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import TablePagination from './TablePagination.vue';

interface LoginAudit {
    id: number;
    user_id: number | null;
    ip_address: string | null;
    login_type: string | null;
    browser: string | null;
    browser_version: string | null;
    os: string | null;
    device: string | null;
    session_id: string | null;
    logged_in_at: string;
    logged_out_at: string | null;
    success: boolean;
    failure_reason: string | null;
    created_at: string;
}

interface Props {
    loginAudits: {
        data: LoginAudit[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { per_page: number | 'all' };
    loginAuditsRoute: (params: { user_uuid: string }) => { url: string };
    userUuid: string;
}

const props = defineProps<Props>();

const selectedAudit = ref<LoginAudit | null>(null);
const isDialogOpen = ref(false);

const localFilters = ref({
    per_page: String(props.filters?.per_page ?? 10),
});

function updatePerPage(value: string) {
    localFilters.value.per_page = value;
    router.post(
        props.loginAuditsRoute({ user_uuid: props.userUuid }).url,
        { per_page: value },
        { preserveScroll: true },
    );
}

function openDetails(audit: LoginAudit) {
    selectedAudit.value = audit;
    isDialogOpen.value = true;
}

function getLoginTypeIcon(type: string | null) {
    switch (type) {
        case 'api':
            return Globe;
        case 'mobile':
            return Smartphone;
        default:
            return Monitor;
    }
}

function getLoginTypeBadgeClass(type: string | null) {
    switch (type) {
        case 'api':
            return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950 dark:text-blue-400';
        case 'mobile':
            return 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950 dark:text-purple-400';
        default:
            return 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-950 dark:text-gray-400';
    }
}

function getBrowserIcon(browser: string | null) {
    if (!browser) return Chrome;
    const b = browser.toLowerCase();
    if (b.includes('chrome')) return Chrome;
    if (b.includes('firefox')) return Globe;
    if (b.includes('safari')) return Globe;
    return Chrome;
}

function getDeviceIcon(device: string | null) {
    if (!device) return Computer;
    const d = device.toLowerCase();
    if (d.includes('mobile') || d.includes('phone')) return Smartphone;
    if (d.includes('tablet') || d.includes('ipad')) return Tablet;
    return Laptop;
}

function getStatusBadgeClass(success: boolean) {
    return success
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-400'
        : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950 dark:text-red-400';
}

function formatDate(date: string) {
    return new Date(date).toLocaleString('pt-BR');
}

function formatDateTimeLocal(date: string | null) {
    if (!date) return '—';
    return new Date(date).toLocaleString('pt-BR');
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header com título e paginação -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium">Histórico de Login</h3>
            <PerPageSelect
                :model-value="localFilters.per_page"
                @update:model-value="updatePerPage"
            />
        </div>

        <!-- Tabela -->
        <div
            v-if="
                loginAudits && loginAudits.data && loginAudits.data.length > 0
            "
        >
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th
                            class="px-3 pb-2 text-left text-sm font-medium text-muted-foreground"
                        >
                            Data/Hora
                        </th>
                        <th
                            class="px-3 pb-2 text-left text-sm font-medium text-muted-foreground"
                        >
                            IP
                        </th>
                        <th
                            class="px-3 pb-2 text-left text-sm font-medium text-muted-foreground"
                        >
                            Tipo
                        </th>
                        <th
                            class="px-3 pb-2 text-left text-sm font-medium text-muted-foreground"
                        >
                            Dispositivo
                        </th>
                        <th
                            class="px-3 pb-2 text-left text-sm font-medium text-muted-foreground"
                        >
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="audit in loginAudits.data"
                        :key="audit.id"
                        class="cursor-pointer hover:bg-muted/30"
                        @click="openDetails(audit)"
                    >
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <Calendar
                                    class="h-4 w-4 text-muted-foreground"
                                />
                                {{ formatDate(audit.logged_in_at) }}
                            </div>
                        </td>
                        <td class="px-3 py-2 font-mono text-xs">
                            {{ audit.ip_address ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    getLoginTypeBadgeClass(audit.login_type),
                                ]"
                            >
                                <component
                                    :is="getLoginTypeIcon(audit.login_type)"
                                    class="mr-1 h-3 w-3"
                                />
                                {{
                                    audit.login_type === 'api'
                                        ? 'API'
                                        : audit.login_type === 'mobile'
                                          ? 'Mobile'
                                          : 'Web'
                                }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            <span
                                v-if="audit.device || audit.browser"
                                class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <component
                                    :is="getDeviceIcon(audit.device)"
                                    class="h-3 w-3"
                                />
                                {{ audit.device ?? 'Desktop' }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-3 py-2">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    getStatusBadgeClass(audit.success),
                                ]"
                            >
                                <CheckCircle
                                    v-if="audit.success"
                                    class="mr-1 h-3 w-3"
                                />
                                <XCircle v-else class="mr-1 h-3 w-3" />
                                {{ audit.success ? 'Sucesso' : 'Falha' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <TablePagination :paginator="loginAudits" />
        </div>

        <!-- Empty state -->
        <div v-else class="py-8 text-center text-muted-foreground">
            Nenhum histórico de login encontrado.
        </div>

        <!-- Dialog de Detalhes -->
        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Detalhes do Login</DialogTitle>
                    <DialogDescription>
                        Informações completas do registro de acesso
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedAudit" class="space-y-4 py-4">
                    <!-- Status -->
                    <div
                        class="flex items-center justify-between rounded-lg bg-muted/50 p-3"
                    >
                        <span class="text-sm font-medium">Status</span>
                        <span
                            :class="[
                                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                getStatusBadgeClass(selectedAudit.success),
                            ]"
                        >
                            <CheckCircle
                                v-if="selectedAudit.success"
                                class="mr-1 h-3 w-3"
                            />
                            <XCircle v-else class="mr-1 h-3 w-3" />
                            {{ selectedAudit.success ? 'Sucesso' : 'Falha' }}
                        </span>
                    </div>

                    <!-- Data/Hora -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <div
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <Calendar class="h-3 w-3" />
                                Data/Hora do Login
                            </div>
                            <div class="text-sm font-medium">
                                {{
                                    formatDateTimeLocal(
                                        selectedAudit.logged_in_at,
                                    )
                                }}
                            </div>
                        </div>
                        <div
                            v-if="selectedAudit.logged_out_at"
                            class="space-y-1"
                        >
                            <div
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <LogOut class="h-3 w-3" />
                                Data/Hora do Logout
                            </div>
                            <div class="text-sm font-medium">
                                {{
                                    formatDateTimeLocal(
                                        selectedAudit.logged_out_at,
                                    )
                                }}
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Acesso -->
                    <div class="space-y-1">
                        <div class="text-xs text-muted-foreground">
                            Tipo de Acesso
                        </div>
                        <span
                            :class="[
                                'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
                                getLoginTypeBadgeClass(
                                    selectedAudit.login_type,
                                ),
                            ]"
                        >
                            <component
                                :is="getLoginTypeIcon(selectedAudit.login_type)"
                                class="mr-1 h-3 w-3"
                            />
                            {{
                                selectedAudit.login_type === 'api'
                                    ? 'API'
                                    : selectedAudit.login_type === 'mobile'
                                      ? 'Mobile'
                                      : 'Web'
                            }}
                        </span>
                    </div>

                    <!-- IP -->
                    <div class="space-y-1">
                        <div class="text-xs text-muted-foreground">
                            Endereço IP
                        </div>
                        <div class="font-mono text-sm">
                            {{ selectedAudit.ip_address ?? '—' }}
                        </div>
                    </div>

                    <!-- Dispositivo -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <div class="text-xs text-muted-foreground">
                                Dispositivo
                            </div>
                            <div class="flex items-center gap-1 text-sm">
                                <component
                                    :is="getDeviceIcon(selectedAudit.device)"
                                    class="h-4 w-4"
                                />
                                {{ selectedAudit.device ?? 'Desktop' }}
                            </div>
                        </div>
                        <div v-if="selectedAudit.os" class="space-y-1">
                            <div class="text-xs text-muted-foreground">
                                Sistema Operacional
                            </div>
                            <div class="text-sm">{{ selectedAudit.os }}</div>
                        </div>
                    </div>

                    <!-- Navegador -->
                    <div v-if="selectedAudit.browser" class="space-y-1">
                        <div class="text-xs text-muted-foreground">
                            Navegador
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <component
                                :is="getBrowserIcon(selectedAudit.browser)"
                                class="h-4 w-4"
                            />
                            {{ selectedAudit.browser }}
                            <span
                                v-if="selectedAudit.browser_version"
                                class="text-muted-foreground"
                            >
                                ({{ selectedAudit.browser_version }})
                            </span>
                        </div>
                    </div>

                    <!-- Session ID -->
                    <div v-if="selectedAudit.session_id" class="space-y-1">
                        <div class="text-xs text-muted-foreground">
                            ID da Sessão
                        </div>
                        <div class="font-mono text-xs break-all">
                            {{ selectedAudit.session_id }}
                        </div>
                    </div>

                    <!-- Motivo da Falha -->
                    <div v-if="selectedAudit.failure_reason" class="space-y-1">
                        <div class="text-xs text-muted-foreground">
                            Motivo da Falha
                        </div>
                        <div
                            class="text-sm font-medium text-red-600 dark:text-red-400"
                        >
                            {{ selectedAudit.failure_reason }}
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
