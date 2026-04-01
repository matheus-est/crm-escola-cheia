<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldCheck, Lock, Eye, EyeOff } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';
import TermsModal from '@/components/TermsModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { update as passwordChangeUpdate } from '@/routes/password/change';

interface TermVersion {
    id: number;
    uuid: string;
    version: string;
    title: string;
    content: string;
    effective_at: string;
    is_active: boolean;
}

const props = defineProps<{
    currentTerm: TermVersion | null;
    needsTermAcceptance: boolean;
}>();

const toast = useToast();

const showPassword = ref(false);
const showConfirmation = ref(false);
const password = ref('');
const showRequirements = ref(false);
const acceptedTerms = ref(false);
const showTermsModal = ref(false);

const form = useForm({
    password: '',
    password_confirmation: '',
    accepted_terms: false,
});

const isPasswordValid = computed(
    () =>
        password.value.length >= 8 &&
        /[A-Z]/.test(password.value) &&
        /[a-z]/.test(password.value) &&
        /\d/.test(password.value) &&
        /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(password.value),
);

const canSubmit = computed(() => isPasswordValid.value && acceptedTerms.value);

onMounted(() => {
    if (props.needsTermAcceptance && props.currentTerm) {
        showTermsModal.value = true;
    }
});

function handleTermAccepted(): void {
    showTermsModal.value = false;
    acceptedTerms.value = true;
    form.accepted_terms = true;
}

function handleSubmit(): void {
    form.password = password.value;
    form.accepted_terms = acceptedTerms.value;

    form.post(passwordChangeUpdate().url, {
        onSuccess: () =>
            toast.success('Senha criada com sucesso. Bem-vindo ao sistema!'),
        onError: (errors) =>
            toast.error(
                errors.password ??
                    errors.accepted_terms ??
                    'Verifique os campos e tente novamente.',
            ),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Criar Senha" />

        <div
            class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12"
        >
            <div class="w-full max-w-sm">
                <div class="mb-6 text-center">
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10"
                    >
                        <ShieldCheck class="h-7 w-7 text-primary" />
                    </div>
                    <h1 class="text-xl font-semibold text-foreground">
                        Crie sua senha de acesso
                    </h1>
                    <p class="mt-1.5 text-sm text-muted-foreground">
                        Primeiro acesso ao sistema. Defina uma senha segura para
                        continuar.
                    </p>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="password">Nova senha</Label>
                            <div class="relative">
                                <Input
                                    id="password"
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Mínimo 8 caracteres"
                                    required
                                    autocomplete="new-password"
                                    class="pr-10"
                                    @focus="showRequirements = true"
                                />
                                <button
                                    type="button"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    :aria-label="
                                        showPassword
                                            ? 'Ocultar senha'
                                            : 'Exibir senha'
                                    "
                                    @click="showPassword = !showPassword"
                                >
                                    <Eye v-if="showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <PasswordRequirements
                                :password="password"
                                :show="showRequirements"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="password_confirmation"
                                >Confirmar senha</Label
                            >
                            <div class="relative">
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="
                                        showConfirmation ? 'text' : 'password'
                                    "
                                    placeholder="Repita a senha"
                                    required
                                    autocomplete="new-password"
                                    class="pr-10"
                                />
                                <button
                                    type="button"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    :aria-label="
                                        showConfirmation
                                            ? 'Ocultar confirmação'
                                            : 'Exibir confirmação'
                                    "
                                    @click="
                                        showConfirmation = !showConfirmation
                                    "
                                >
                                    <Eye
                                        v-if="showConfirmation"
                                        class="h-4 w-4"
                                    />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <InputError
                                :message="form.errors.password_confirmation"
                            />
                        </div>

                        <div class="flex items-start gap-2.5 pt-1">
                            <input
                                id="accepted_terms"
                                v-model="acceptedTerms"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <Label
                                for="accepted_terms"
                                class="cursor-pointer text-sm leading-relaxed font-normal text-muted-foreground"
                            >
                                Li e aceito os
                                <button
                                    type="button"
                                    class="font-medium text-foreground underline-offset-4 hover:underline"
                                    @click="showTermsModal = true"
                                >
                                    Termos de Uso
                                </button>
                            </Label>
                        </div>
                        <InputError :message="form.errors.accepted_terms" />

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="!canSubmit || form.processing"
                        >
                            <Lock class="mr-2 h-4 w-4" />
                            {{
                                form.processing
                                    ? 'Salvando...'
                                    : canSubmit
                                      ? 'Alterar senha e entrar'
                                      : 'Preencha todos os campos'
                            }}
                        </Button>
                    </form>
                </div>
            </div>
        </div>

        <TermsModal
            v-if="currentTerm"
            :open="showTermsModal"
            :term="currentTerm"
            :show-accept-button="needsTermAcceptance"
            @update:open="showTermsModal = $event"
            @accept="handleTermAccepted"
        />
    </AppLayout>
</template>
