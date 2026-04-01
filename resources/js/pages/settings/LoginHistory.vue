<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import LoginHistoryTable from '@/components/LoginHistoryTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/login-history';
import { type BreadcrumbItem } from '@/types';

interface User {
    uuid: string;
    name: string;
}

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
}

interface Props {
    user: User;
    loginAudits: {
        data: LoginAudit[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { per_page: number | 'all' };
}

const props = defineProps<Props>();

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('Login History'),
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('Login History')" />

        <h1 class="sr-only">{{ t('Login History') }}</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    :title="t('Login History')"
                    :description="
                        t('View your login history and account access details')
                    "
                />

                <LoginHistoryTable
                    :login-audits="props.loginAudits"
                    :filters="props.filters"
                    :login-audits-route="edit"
                    :user-uuid="props.user.uuid"
                />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
