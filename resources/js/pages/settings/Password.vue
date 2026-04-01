<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/user-password';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('Password Settings'),
        href: edit().url,
    },
];

const showPasswordRequirements = ref(false);
const newPassword = ref('');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('Password Settings')" />

        <h1 class="sr-only">{{ $t('Password Settings') }}</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Update password"
                    :description="
                        t(
                            'Ensure your account is using a long, random password to stay secure',
                        )
                    "
                />

                <Form
                    v-bind="PasswordController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">{{
                            $t('Current password')
                        }}</Label>
                        <Input
                            id="current_password"
                            name="current_password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            :placeholder="t('Current password')"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">{{ $t('New password') }}</Label>
                        <Input
                            id="password"
                            v-model="newPassword"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            :placeholder="t('New password')"
                            @focus="showPasswordRequirements = true"
                        />
                        <InputError :message="errors.password" />

                        <PasswordRequirements
                            :password="newPassword"
                            :show="showPasswordRequirements"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">{{
                            $t('Confirm password')
                        }}</Label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            :placeholder="t('Confirm password')"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                            >{{ $t('Save') }} {{ $t('Password') }}</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-green-600 dark:text-green-400"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
