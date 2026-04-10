<script setup lang="ts">
import { Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Loader2,
    Trash2,
    UserPlus,
} from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useCepLookup } from '@/composables/useCepLookup';
import { useCnpjLookup, maskCnpj } from '@/composables/useCnpjLookup';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/admin/schools';
import {
    destroy as destroyUser,
    storeOrCreate,
} from '@/routes/admin/schools/users';
import { checkEmail as checkEmailRoute } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import type { School } from '@/types/crm';

interface Role {
    uuid: string;
    name: string;
}

const props = defineProps<{
    school: School;
    availableRoles?: Role[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
    { title: 'Editar Escola', href: '#' },
];

const toast = useToast();

const selectedStatus = ref(props.school.status);
const slugUnlocked = ref(false);

function unlockSlug() {
    slugUnlocked.value = true;
    toast.warning(
        'Slug desbloqueado. Alterar este valor quebrará todos os links públicos de captação desta escola.',
    );
}

const handleSuccess = () => {
    toast.success('Escola atualizada com sucesso.');
};

const handleError = () => {
    toast.error('Erro ao atualizar escola. Verifique os campos.');
};

// CEP lookup
const {
    isLoading: isLoadingCep,
    error: cepError,
    lookup: lookupCep,
} = useCepLookup();

// CNPJ mask + lookup — DOM-only mask, no reactive ref (avoids re-render delay)
const {
    isLoading: isLoadingCnpj,
    error: cnpjError,
    lookup: lookupCnpj,
} = useCnpjLookup();

const legalNameValue = ref(props.school.legal_name ?? '');

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
        fillInput('unit-street', street);
        fillInput('unit-neighborhood', neighborhood);
        fillInput('unit-city', city);
        fillInput('unit-state', state);
    });
}

function handleCnpjInput(e: Event) {
    const input = e.target as HTMLInputElement;
    const masked = maskCnpj(input.value);
    input.value = masked; // DOM only — Vue never re-renders this input
    if (masked.replace(/\D/g, '').length === 14) {
        lookupCnpj(masked, (data) => {
            legalNameValue.value = data.legal_name;
            fillInput('trade_name', data.trade_name);
            if (slugUnlocked.value) {
                const slugSource = data.trade_name || data.legal_name;
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
                    'unit-zip_code',
                    d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d,
                );
                // Trigger CEP lookup to auto-fill full address
                const zipInput = document.getElementById(
                    'unit-zip_code',
                ) as HTMLInputElement | null;
                if (zipInput)
                    void lookupCep(
                        zipInput.value,
                        ({ street, neighborhood, city, state }) => {
                            fillInput('unit-street', street);
                            fillInput('unit-neighborhood', neighborhood);
                            fillInput('unit-city', city);
                            fillInput('unit-state', state);
                        },
                    );
            }
            if (data.street) fillInput('unit-street', data.street);
            if (data.neighborhood)
                fillInput('unit-neighborhood', data.neighborhood);
            if (data.city) fillInput('unit-city', data.city);
            if (data.state) fillInput('unit-state', data.state);
        });
    }
}

// Inline user creation form
const userForm = useForm({
    name: '',
    email: '',
    role_id: '',
});

type EmailStatus = 'idle' | 'checking' | 'exists' | 'new';
const emailStatus = ref<EmailStatus>('idle');
const emailStatusName = ref('');

async function checkUserEmail() {
    const email = userForm.email.trim();
    if (!email || !email.includes('@')) {
        emailStatus.value = 'idle';
        return;
    }

    emailStatus.value = 'checking';
    try {
        const url = checkEmailRoute.url({ query: { email } });
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
        });
        const data = (await response.json()) as {
            exists: boolean;
            name?: string;
        };
        if (data.exists) {
            emailStatus.value = 'exists';
            emailStatusName.value = data.name ?? '';
            if (data.name && !userForm.name) {
                userForm.name = data.name;
            }
        } else {
            emailStatus.value = 'new';
            emailStatusName.value = '';
        }
    } catch {
        emailStatus.value = 'idle';
    }
}

function submitUserForm() {
    userForm.post(storeOrCreate({ school: props.school.uuid }).url, {
        onSuccess: () => {
            userForm.reset();
            emailStatus.value = 'idle';
            emailStatusName.value = '';
        },
    });
}

function detachUser(userUuid: string) {
    router.delete(destroyUser({ school: props.school.uuid, userUuid }).url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Escola" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading
                    title="Editar Escola"
                    description="Alterar os dados da escola cadastrada."
                    class="pt-8"
                />
            </div>

            <div class="rounded-md border">
                <Tabs default-value="details">
                    <TabsList
                        class="w-full justify-start rounded-none border-b bg-transparent px-2 pt-2"
                    >
                        <TabsTrigger
                            value="details"
                            class="rounded-b-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                        >
                            Dados da Escola
                        </TabsTrigger>
                        <TabsTrigger
                            value="users"
                            class="rounded-b-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                        >
                            Responsáveis
                        </TabsTrigger>
                    </TabsList>

                    <!-- Aba Dados da Escola -->
                    <TabsContent value="details" class="m-0">
                        <Form
                            method="put"
                            :action="update({ school: props.school.uuid }).url"
                            class="space-y-6"
                            v-slot="{ errors, processing }"
                            @success="handleSuccess"
                            @error="handleError"
                        >
                            <div class="space-y-6 p-6">
                                <!-- Slug warning -->
                                <Alert
                                    class="border-amber-500/50 bg-amber-50/50 text-amber-600 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-500"
                                >
                                    <AlertCircle class="h-4 w-4" />
                                    <AlertTitle>Atenção</AlertTitle>
                                    <AlertDescription>
                                        Alterar o slug atual
                                        <strong
                                            >quebrará automaticamente</strong
                                        >
                                        quaisquer links do formulário de
                                        captação público espalhados por anúncios
                                        ou pelo site da escola. Altere apenas se
                                        estritamente necessário.
                                    </AlertDescription>
                                </Alert>

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
                                                :default-value="
                                                    props.school.cnpj
                                                "
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
                                        <InputError :message="errors.cnpj" />
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
                                            :default-value="
                                                props.school.trade_name ?? ''
                                            "
                                        />
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Quando preenchido, é exibido no menu
                                            de seleção de escola.
                                        </p>
                                        <InputError
                                            :message="errors.trade_name"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="slug"
                                            >Slug da Instância</Label
                                        >
                                        <div class="flex gap-2">
                                            <Input
                                                id="slug"
                                                name="slug"
                                                :default-value="
                                                    props.school.slug
                                                "
                                                placeholder="..."
                                                :readonly="!slugUnlocked"
                                                :class="[
                                                    'flex-1',
                                                    !slugUnlocked
                                                        ? 'cursor-not-allowed bg-muted'
                                                        : 'border-amber-500 ring-1 ring-amber-500/50 focus-visible:ring-amber-500',
                                                ]"
                                            />
                                            <Button
                                                v-if="!slugUnlocked"
                                                type="button"
                                                variant="outline"
                                                class="shrink-0 border-amber-500 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:hover:bg-amber-500/10"
                                                @click="unlockSlug"
                                            >
                                                Desbloquear
                                            </Button>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 rounded-md border border-amber-500/50 bg-amber-50/50 px-3 text-xs font-medium text-amber-600 dark:bg-amber-500/10 dark:text-amber-500"
                                            >
                                                <AlertCircle class="h-3 w-3" />
                                                Editando
                                            </span>
                                        </div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            O slug é usado nas URLs públicas dos
                                            formulários de captação.
                                        </p>
                                        <InputError :message="errors.slug" />
                                    </div>

                                    <div class="space-y-2 sm:col-span-2">
                                        <Label for="status">Status</Label>
                                        <Select
                                            name="status"
                                            v-model="selectedStatus"
                                        >
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Selecione..."
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active"
                                                    >Ativo</SelectItem
                                                >
                                                <SelectItem value="inactive"
                                                    >Inativo</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="errors.status" />
                                    </div>
                                </div>

                                <!-- Endereço da Unidade Matriz -->
                                <div class="border-t pt-4">
                                    <h3
                                        class="mb-4 text-base font-medium text-muted-foreground"
                                    >
                                        Endereço da Unidade Matriz
                                    </h3>

                                    <!-- hidden field: units[0][name] required by backend -->
                                    <input
                                        type="hidden"
                                        name="units[0][name]"
                                        :value="
                                            props.school.units?.[0]?.name ??
                                            props.school.legal_name
                                        "
                                    />

                                    <div class="grid gap-4 sm:grid-cols-12">
                                        <div class="space-y-2 sm:col-span-2">
                                            <Label for="unit-zip_code"
                                                >CEP</Label
                                            >
                                            <div class="relative">
                                                <Input
                                                    id="unit-zip_code"
                                                    name="units[0][zip_code]"
                                                    placeholder="00000-000"
                                                    maxlength="9"
                                                    :default-value="
                                                        props.school.units?.[0]
                                                            ?.zip_code ?? ''
                                                    "
                                                    @input="handleCepInput"
                                                    @blur="handleCepBlur"
                                                />
                                                <span
                                                    v-if="isLoadingCep"
                                                    class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                                />
                                            </div>
                                            <p
                                                v-if="cepError"
                                                class="text-xs text-destructive"
                                            >
                                                {{ cepError }}
                                            </p>
                                            <InputError
                                                :message="
                                                    errors['units.0.zip_code']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-5">
                                            <Label for="unit-street"
                                                >Logradouro</Label
                                            >
                                            <Input
                                                id="unit-street"
                                                name="units[0][street]"
                                                placeholder="Rua, Avenida, etc."
                                                :default-value="
                                                    props.school.units?.[0]
                                                        ?.street ?? ''
                                                "
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.street']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-2">
                                            <Label for="unit-number"
                                                >Número
                                                <span class="text-destructive"
                                                    >*</span
                                                ></Label
                                            >
                                            <Input
                                                id="unit-number"
                                                name="units[0][number]"
                                                placeholder="123"
                                                required
                                                :default-value="
                                                    props.school.units?.[0]
                                                        ?.number ?? ''
                                                "
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.number']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-3">
                                            <Label for="unit-complement"
                                                >Complemento</Label
                                            >
                                            <Input
                                                id="unit-complement"
                                                name="units[0][complement]"
                                                placeholder="Sala, Andar, etc."
                                                :default-value="
                                                    props.school.units?.[0]
                                                        ?.complement ?? ''
                                                "
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.complement']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-3">
                                            <Label for="unit-neighborhood"
                                                >Bairro</Label
                                            >
                                            <Input
                                                id="unit-neighborhood"
                                                name="units[0][neighborhood]"
                                                placeholder="Bairro"
                                                :default-value="
                                                    props.school.units?.[0]
                                                        ?.neighborhood ?? ''
                                                "
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
                                            <Label for="unit-city"
                                                >Cidade</Label
                                            >
                                            <Input
                                                id="unit-city"
                                                name="units[0][city]"
                                                placeholder="Cidade"
                                                :default-value="
                                                    props.school.units?.[0]
                                                        ?.city ?? ''
                                                "
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.city']
                                                "
                                            />
                                        </div>

                                        <div class="space-y-2 sm:col-span-2">
                                            <Label for="unit-state">UF</Label>
                                            <Input
                                                id="unit-state"
                                                name="units[0][state]"
                                                placeholder="UF"
                                                maxlength="2"
                                                :default-value="
                                                    props.school.units?.[0]
                                                        ?.state ?? ''
                                                "
                                            />
                                            <InputError
                                                :message="
                                                    errors['units.0.state']
                                                "
                                            />
                                        </div>
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
                                            : 'Salvar Alterações'
                                    }}
                                </Button>
                            </div>
                        </Form>
                    </TabsContent>

                    <!-- Aba Responsáveis -->
                    <TabsContent value="users" class="m-0 space-y-6 p-6">
                        <!-- Formulário inline de adição -->
                        <div
                            class="space-y-4 rounded-lg border bg-card p-4 shadow-sm"
                        >
                            <h3
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <UserPlus class="h-4 w-4" />
                                Adicionar Responsável
                            </h3>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="space-y-2">
                                    <Label for="user-email">E-mail</Label>
                                    <div class="relative">
                                        <Input
                                            id="user-email"
                                            v-model="userForm.email"
                                            type="email"
                                            placeholder="email@escola.com"
                                            @blur="checkUserEmail"
                                        />
                                        <Loader2
                                            v-if="emailStatus === 'checking'"
                                            class="absolute top-2.5 right-3 h-4 w-4 animate-spin text-muted-foreground"
                                        />
                                    </div>
                                    <p
                                        v-if="emailStatus === 'exists'"
                                        class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        Usuário existente — será vinculado
                                    </p>
                                    <p
                                        v-else-if="emailStatus === 'new'"
                                        class="text-xs text-muted-foreground"
                                    >
                                        Novo usuário — receberá acesso por
                                        e-mail
                                    </p>
                                    <InputError
                                        :message="userForm.errors.email"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="user-name">Nome</Label>
                                    <Input
                                        id="user-name"
                                        v-model="userForm.name"
                                        placeholder="Nome completo"
                                        :readonly="emailStatus === 'exists'"
                                        :class="
                                            emailStatus === 'exists'
                                                ? 'bg-muted'
                                                : ''
                                        "
                                    />
                                    <InputError
                                        :message="userForm.errors.name"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="user-role">Perfil</Label>
                                    <Select v-model="userForm.role_id">
                                        <SelectTrigger id="user-role">
                                            <SelectValue
                                                placeholder="Selecione..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="r in props.availableRoles ??
                                                []"
                                                :key="r.uuid"
                                                :value="r.uuid"
                                            >
                                                {{ r.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="userForm.errors.role_id"
                                    />
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button
                                    type="button"
                                    :disabled="
                                        userForm.processing ||
                                        !userForm.email ||
                                        !userForm.role_id
                                    "
                                    @click="submitUserForm"
                                >
                                    {{
                                        userForm.processing
                                            ? 'Adicionando...'
                                            : 'Adicionar Responsável'
                                    }}
                                </Button>
                            </div>
                        </div>

                        <!-- Tabela de responsáveis vinculados -->
                        <div class="rounded-md border">
                            <table class="w-full text-left text-sm">
                                <thead
                                    class="bg-muted/50 text-muted-foreground"
                                >
                                    <tr>
                                        <th class="px-4 py-3 font-medium">
                                            Nome
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            E-mail
                                        </th>
                                        <th class="px-4 py-3 font-medium">
                                            Perfil
                                        </th>
                                        <th
                                            class="px-4 py-3 text-right font-medium"
                                        >
                                            Ação
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="su in props.school.users ?? []"
                                        :key="su.uuid"
                                    >
                                        <td class="px-4 py-3">{{ su.name }}</td>
                                        <td
                                            class="px-4 py-3 text-muted-foreground"
                                        >
                                            {{ su.email }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary ring-1 ring-primary/20 ring-inset"
                                            >
                                                {{ su.role?.name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="rounded p-1 text-destructive hover:bg-muted"
                                                title="Remover Vínculo"
                                                @click="detachUser(su.uuid)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            (props.school.users ?? [])
                                                .length === 0
                                        "
                                    >
                                        <td
                                            colspan="4"
                                            class="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            Nenhum responsável vinculado a esta
                                            escola ainda.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
