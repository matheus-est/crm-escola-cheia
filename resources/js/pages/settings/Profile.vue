<script setup lang="ts">
import { Form, Head, usePage, router } from '@inertiajs/vue3';
import { FileDown, FileText } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TermsModal from '@/components/TermsModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit, exportMethod as exportProfile } from '@/routes/profile';
import { type BreadcrumbItem } from '@/types';

interface TermVersion {
    id: number;
    uuid: string;
    version: string;
    title: string;
    content: string;
    effective_at: string;
    is_active: boolean;
}

type Props = {
    status?: string;
};

defineProps<Props>();

const { t } = useI18n();
const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('profile.settings'),
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;
const currentTerm = page.props.currentTerm as TermVersion | null;
const needsTermAcceptance = page.props.needsTermAcceptance as boolean;
const canDeleteAccount = page.props.canDeleteAccount as boolean;

const showTermsModal = ref(false);

// Mostrar modal se precisar aceitar
if (needsTermAcceptance && currentTerm) {
    showTermsModal.value = true;
}

function exportMyData(): void {
    window.open(exportProfile().url, '_blank');
}

function handleTermAccepted(): void {
    showTermsModal.value = false;
    router.reload();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('profile.settings')" />

        <h1 class="sr-only">{{ $t('profile.settings') }}</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    :title="t('profile.information')"
                    :description="t('profile.description')"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">{{ $t('Name') }}</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            :placeholder="t('Full name')"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ $t('Email address') }}</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            :placeholder="t('Email address')"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >{{ $t('Save') }}</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ $t('Saved.') }}
                            </p>
                        </Transition>
                    </div>
                </Form>

                <div class="flex flex-wrap items-center gap-4">
                    <Button
                        variant="outline"
                        class="gap-2"
                        @click="showTermsModal = true"
                    >
                        <FileText class="h-4 w-4" />
                        Ver Termos de Uso
                    </Button>
                    <Button
                        variant="outline"
                        class="gap-2"
                        @click="exportMyData"
                    >
                        <FileDown class="h-4 w-4" />
                        Exportar Meus Dados
                    </Button>
                </div>
            </div>

            <!-- Modal de Termos -->
            <TermsModal
                v-if="currentTerm"
                :open="showTermsModal"
                :term="currentTerm"
                :show-accept-button="needsTermAcceptance"
                @update:open="showTermsModal = $event"
                @accept="handleTermAccepted"
            />

            <DeleteUser :can-delete-account="canDeleteAccount" />
        </SettingsLayout>
    </AppLayout>
</template>
