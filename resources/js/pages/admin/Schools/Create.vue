<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useCepLookup } from '@/composables/useCepLookup';
import { useCnpjLookup, maskCnpj } from '@/composables/useCnpjLookup';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/admin/schools';
import { type BreadcrumbItem } from '@/types';

interface Role {
    uuid: string;
    name: string;
}

defineProps<{
    availableRoles?: Role[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
    { title: 'Nova Escola', href: '#' },
];

const toast = useToast();

const {
    isLoading: isLoadingCep,
    error: cepError,
    lookup: lookupCep,
} = useCepLookup();

const {
    isLoading: isLoadingCnpj,
    error: cnpjError,
    cnpjNotFound,
    lookup: lookupCnpj,
} = useCnpjLookup();

// CNPJ state — DOM-only mask, no reactive ref (avoids re-render delay)
const legalNameValue = ref('');
const userModifiedSlug = false;

function fillInput(id: string, value: string): void {
    const el = document.getElementById(id) as HTMLInputElement | null;
    if (el) el.value = value;
}

function handleCepInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const d = input.value.replace(/\D/g, '').slice(0, 8);
    input.value = d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d;
}

function handleCepBlur(event: Event): void {
    const input = event.target as HTMLInputElement;
    void lookupCep(input.value, ({ street, neighborhood, city, state }) => {
        fillInput('street', street);
        fillInput('neighborhood', neighborhood);
        fillInput('city', city);
        fillInput('state', state);
    });
}

function handleTradeNameInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (userModifiedSlug) return;
    const value = input.value || legalNameValue.value;
    fillInput(
        'slug',
        value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-'),
    );
}

function handleCnpjInput(e: Event) {
    const input = e.target as HTMLInputElement;
    const masked = maskCnpj(input.value);
    input.value = masked; // DOM only — Vue never re-renders this input
    if (masked.replace(/\D/g, '').length === 14) {
        lookupCnpj(masked, (data) => {
            legalNameValue.value = data.legal_name;
            fillInput('trade_name', data.trade_name);
            const slugSource = data.trade_name || data.legal_name;
            const slugEl = document.getElementById(
                'slug',
            ) as HTMLInputElement | null;
            if (!slugEl?.value) {
                fillInput(
                    'slug',
                    slugSource
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/[^a-z0-9\s-]/g, '')
                        .trim()
                        .replace(/\s+/g, '-'),
                );
            }
            if (data.zip_code) {
                const d = data.zip_code.replace(/\D/g, '');
                fillInput(
                    'zip_code',
                    d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d,
                );
                // Trigger CEP lookup to auto-fill full address
                const zipInput = document.getElementById(
                    'zip_code',
                ) as HTMLInputElement | null;
                if (zipInput)
                    void lookupCep(
                        zipInput.value,
                        ({ street, neighborhood, city, state }) => {
                            fillInput('street', street);
                            fillInput('neighborhood', neighborhood);
                            fillInput('city', city);
                            fillInput('state', state);
                        },
                    );
            }
            if (data.street) fillInput('street', data.street);
            if (data.neighborhood) fillInput('neighborhood', data.neighborhood);
            if (data.city) fillInput('city', data.city);
            if (data.state) fillInput('state', data.state);
        });
    }
}

const handleSuccess = () => {
    toast.success('Escola criada com sucesso.');
    // backend redirects to edit — Inertia follows automatically
};

const handleError = () => {
    toast.error('Erro ao criar escola. Verifique os campos.');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nova Escola" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading
                    title="Nova Escola"
                    description="Cadastre uma nova escola e sua unidade matriz no sistema."
                    class="pt-8"
                />
            </div>

            <div class="rounded-md border">
                <Tabs default-value="geral">
                    <TabsList
                        class="w-full justify-start rounded-none border-b bg-transparent px-2 pt-2"
                    >
                        <TabsTrigger
                            value="geral"
                            class="rounded-b-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                        >
                            Dados Gerais
                        </TabsTrigger>

                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <span class="inline-flex">
                                        <TabsTrigger
                                            value="responsaveis"
                                            disabled
                                            class="flex items-center gap-1 rounded-b-none opacity-50"
                                        >
                                            <Users class="h-4 w-4" />
                                            Responsáveis
                                        </TabsTrigger>
                                    </span>
                                </TooltipTrigger>
                                <TooltipContent>
                                    Salve a escola primeiro para gerenciar
                                    responsáveis
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </TabsList>

                    <TabsContent value="geral" class="m-0">
                        <Form
                            method="post"
                            :action="store().url"
                            class="space-y-6"
                            v-slot="{ errors, processing }"
                            @success="handleSuccess"
                            @error="handleError"
                        >
                            <div class="space-y-6 p-6">
                                <!-- Dados Gerais -->
                                <div
                                    class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                                >
                                    <h3 class="text-lg font-medium">
                                        Dados Gerais
                                    </h3>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="cnpj">
                                                CNPJ
                                                <span class="text-destructive"
                                                    >*</span
                                                >
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="cnpj"
                                                    name="cnpj"
                                                    placeholder="00.000.000/0000-00"
                                                    maxlength="18"
                                                    @input="handleCnpjInput"
                                                />
                                                <span
                                                    v-if="isLoadingCnpj"
                                                    class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                                />
                                            </div>
                                            <p
                                                v-if="cnpjError"
                                                class="text-xs text-destructive"
                                            >
                                                {{ cnpjError }}
                                            </p>
                                            <p
                                                v-if="cnpjNotFound"
                                                class="text-xs text-muted-foreground"
                                            >
                                                CNPJ não encontrado na base
                                                pública. Preencha os campos
                                                manualmente.
                                            </p>
                                            <InputError
                                                :message="errors.cnpj"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="legal_name"
                                                >Razão Social
                                                <span class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="legal_name"
                                                name="legal_name"
                                                placeholder="Escola Exemplo"
                                                v-model="legalNameValue"
                                            />
                                            <InputError
                                                :message="errors.legal_name"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="trade_name"
                                                >Nome Fantasia</Label
                                            >
                                            <Input
                                                id="trade_name"
                                                name="trade_name"
                                                placeholder="Nome exibido no sistema (opcional)"
                                                @input="handleTradeNameInput"
                                            />
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Quando preenchido, é exibido no
                                                menu de seleção de escola.
                                            </p>
                                            <InputError
                                                :message="errors.trade_name"
                                            />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="slug"
                                                >Slug da Instância
                                                <span class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="slug"
                                                name="slug"
                                                placeholder="gerado-automaticamente"
                                                required
                                                @input="
                                                    () => {
                                                        userModifiedSlug = true;
                                                    }
                                                "
                                            />
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Obrigatório. Gerado
                                                automaticamente a partir do nome
                                                fantasia.
                                            </p>
                                            <InputError
                                                :message="errors.slug"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Unidade Matriz -->
                                <div class="border-t pt-4">
                                    <h3 class="mb-4 text-lg font-medium">
                                        Endereço da Unidade Matriz
                                    </h3>

                                    <input
                                        type="hidden"
                                        name="units[0][name]"
                                        :value="
                                            legalNameValue || 'Unidade Matriz'
                                        "
                                    />

                                    <div class="grid gap-4 sm:grid-cols-12">
                                        <div class="space-y-2 sm:col-span-2">
                                            <Label for="zip_code">CEP</Label>
                                            <div class="relative">
                                                <Input
                                                    id="zip_code"
                                                    name="units[0][zip_code]"
                                                    placeholder="00000-000"
                                                    maxlength="9"
                                                    @input="handleCepInput"
                                                    @blur="handleCepBlur"
                                                />
                                                <span
                                                    v-if="isLoadingCep"
                                                    class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                                />
                                            </div>
                                            <InputError
                                                :message="
                                                    errors['units.0.zip_code']
                                                "
                                            />
                                            <p
                                                v-if="cepError"
                                                class="text-xs text-destructive"
                                            >
                                                {{ cepError }}
                                            </p>
                                        </div>

                                        <div class="space-y-2 sm:col-span-5">
                                            <Label for="street"
                                                >Logradouro</Label
                                            >
                                            <Input
                                                id="street"
                                                name="units[0][street]"
                                                placeholder="Rua, Avenida, etc."
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.street']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-2">
                                            <Label for="number"
                                                >Número
                                                <span class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="number"
                                                name="units[0][number]"
                                                placeholder="123"
                                                required
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.number']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-3">
                                            <Label for="complement"
                                                >Complemento</Label
                                            >
                                            <Input
                                                id="complement"
                                                name="units[0][complement]"
                                                placeholder="Sala, Andar, etc."
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.complement']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-3">
                                            <Label for="neighborhood"
                                                >Bairro</Label
                                            >
                                            <Input
                                                id="neighborhood"
                                                name="units[0][neighborhood]"
                                                placeholder="Bairro"
                                            />
                                            <InputError
                                                :message="
                                                    errors[
                                                        'units.0.neighborhood'
                                                    ]
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-7">
                                            <Label for="city">Cidade</Label>
                                            <Input
                                                id="city"
                                                name="units[0][city]"
                                                placeholder="Cidade"
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.city']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-2">
                                            <Label for="state">UF</Label>
                                            <Input
                                                id="state"
                                                name="units[0][state]"
                                                placeholder="UF"
                                                maxlength="2"
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.state']
                                                "
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="() => router.visit(index().url)"
                                        :disabled="processing"
                                        class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        Cancelar
                                    </Button>

                                    <Button
                                        type="submit"
                                        :disabled="
                                            processing ||
                                            !!cnpjError ||
                                            isLoadingCnpj
                                        "
                                        class="bg-green-600 text-sm text-white hover:bg-green-700"
                                    >
                                        {{
                                            processing
                                                ? 'Salvando...'
                                                : 'Salvar Escola'
                                        }}
                                    </Button>
                                </div>
                            </div>
                        </Form>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
