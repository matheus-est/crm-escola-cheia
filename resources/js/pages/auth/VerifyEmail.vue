<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const { t } = useI18n();

defineProps<{
    status?: string;
}>();

const form = useForm({});

const resend = () => {
    form.post(send().url);
};

const doLogout = () => {
    useForm({}).post(logout().url);
};
</script>

<template>
    <AuthLayout
        :title="t('Verify email')"
        :description="
            t(
                'Please verify your email address by clicking on the link we just emailed to you.',
            )
        "
    >
        <Head :title="t('Email verification')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{
                $t(
                    'A new verification link has been sent to the email address you provided during registration.',
                )
            }}
        </div>

        <form @submit.prevent="resend" class="space-y-6 text-center">
            <Button
                type="submit"
                :disabled="form.processing"
                variant="secondary"
            >
                <Spinner v-if="form.processing" />
                {{ $t('Resend Verification Email') }}
            </Button>

            <TextLink
                as="button"
                type="button"
                class="mx-auto block text-sm"
                @click="doLogout"
            >
                {{ $t('Log Out') }}
            </TextLink>
        </form>
    </AuthLayout>
</template>
