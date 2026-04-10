<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, User } from 'lucide-vue-next';
import { type Component, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { useCepLookup } from '@/composables/useCepLookup';
import { useCpfLookup } from '@/composables/useCpfLookup';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/tenant/opportunities';
import type { BreadcrumbItem } from '@/types';
import type {
    Grade,
    Guardian,
    LeadSource,
    School,
    SchoolYear,
    Segment,
    Student,
    TenantUser,
} from '@/types/crm';

const props = defineProps<{
    school: School;
    grades: Grade[];
    schoolYears: SchoolYear[];
    leadSources: LeadSource[];
    users: TenantUser[];
    segments: Segment[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: index().url },
    { title: 'Nova Oportunidade', href: '#' },
];

const toast = useToast();

type Tab = 'cadastro' | 'aluno';
const activeTab = ref<Tab>('cadastro');

const tabs: { value: Tab; label: string; icon: Component }[] = [
    {
        value: 'cadastro',
        label: 'Informações do Cadastro',
        icon: ClipboardList,
    },
    { value: 'aluno', label: 'Aluno / Responsável', icon: User },
];

function fillInput(id: string, value: string): void {
    const el = document.getElementById(id) as
        | HTMLInputElement
        | HTMLTextAreaElement
        | null;
    if (el) el.value = value;
}

function fillGuardianFields(guardian: Guardian): void {
    fillInput('guardian_name', guardian.name ?? '');
    fillInput('guardian_cpf', guardian.cpf ?? '');
    fillInput('guardian_phone', guardian.phone ?? '');
    fillInput('guardian_email', guardian.email ?? '');
    fillInput('zip_code', guardian.zip_code ?? '');
    fillInput('street', guardian.street ?? '');
    fillInput('number', guardian.number ?? '');
    fillInput('complement', guardian.complement ?? '');
    fillInput('neighborhood', guardian.neighborhood ?? '');
    fillInput('city', guardian.city ?? '');
    fillInput('state', guardian.state ?? '');
}

const {
    isLoading: isLoadingStudent,
    error: studentCpfError,
    triggerLookup: lookupStudent,
} = useCpfLookup({
    type: 'student',
    onFound: (data) => {
        const student = data as Student;
        fillInput('student_name', student.name ?? '');
        if (student.guardian) {
            fillGuardianFields(student.guardian);
        }
    },
    onNotFound: () => {},
});

const {
    isLoading: isLoadingGuardian,
    error: guardianCpfError,
    triggerLookup: lookupGuardian,
} = useCpfLookup({
    type: 'guardian',
    onFound: (data) => {
        const guardian = data as Guardian;
        fillGuardianFields(guardian);
    },
    onNotFound: () => {},
});

function handleStudentCpfInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const d = input.value.replace(/\D/g, '').slice(0, 11);
    let m = d;
    if (d.length > 3) m = `${d.slice(0, 3)}.${d.slice(3)}`;
    if (d.length > 6) m = `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`;
    if (d.length > 9)
        m = `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`;
    input.value = m;
    lookupStudent(m);
}

function handleGuardianCpfInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const d = input.value.replace(/\D/g, '').slice(0, 11);
    let m = d;
    if (d.length > 3) m = `${d.slice(0, 3)}.${d.slice(3)}`;
    if (d.length > 6) m = `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`;
    if (d.length > 9)
        m = `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`;
    input.value = m;
    lookupGuardian(m);
}

function handleGuardianPhoneInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const d = input.value.replace(/\D/g, '').slice(0, 11);
    let m = d;
    if (d.length > 2) m = `(${d.slice(0, 2)}) ${d.slice(2)}`;
    if (d.length > 7) m = `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
    if (d.length <= 10 && d.length > 6)
        m = `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
    input.value = m;
}

const {
    lookup: cepLookup,
    isLoading: isLoadingCep,
    error: cepError,
} = useCepLookup();

function handleZipCodeInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const d = input.value.replace(/\D/g, '').slice(0, 8);
    input.value = d.length > 5 ? `${d.slice(0, 5)}-${d.slice(5)}` : d;
    cepError.value = null;
}

function handleZipCodeBlur(event: Event): void {
    const input = event.target as HTMLInputElement;
    void cepLookup(input.value, ({ street, neighborhood, city, state }) => {
        fillInput('street', street);
        fillInput('neighborhood', neighborhood);
        fillInput('city', city);
        fillInput('state', state);
    });
}

function handleSuccess(): void {
    toast.success('Oportunidade criada com sucesso.');
    router.visit(index().url);
}

function handleError(): void {
    toast.error('Erro ao criar oportunidade. Verifique os campos.');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nova Oportunidade" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading
                    title="Nova Oportunidade"
                    description="Cadastre uma nova oportunidade de matrícula."
                    class="pt-8"
                />
            </div>

            <div class="rounded-md border">
                <Form
                    method="post"
                    :action="store().url"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                    @success="handleSuccess"
                    @error="handleError"
                >
                    <div class="p-6">
                        <!-- Tabs nav -->
                        <div class="flex border-b">
                            <button
                                v-for="tab in tabs"
                                :key="tab.value"
                                type="button"
                                class="px-4 py-3 text-sm font-medium transition-colors"
                                :class="
                                    activeTab === tab.value
                                        ? 'border-b-2 border-primary text-primary'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                @click="activeTab = tab.value"
                            >
                                <component
                                    :is="tab.icon"
                                    class="mr-2 inline h-4 w-4"
                                />
                                {{ tab.label }}
                            </button>
                        </div>

                        <!-- Aba 1: Informações do Cadastro -->
                        <div
                            v-show="activeTab === 'cadastro'"
                            class="space-y-6 p-6"
                        >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="registration_type"
                                        >Tipo de Cadastro</Label
                                    >
                                    <Select name="registration_type">
                                        <SelectTrigger id="registration_type">
                                            <SelectValue
                                                placeholder="Selecione..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="agendamento"
                                                >Agendamento</SelectItem
                                            >
                                            <SelectItem value="evento"
                                                >Evento</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="errors.registration_type"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="responsible_user_id"
                                        >Responsável pelo Atendimento</Label
                                    >
                                    <Select name="responsible_user_id">
                                        <SelectTrigger id="responsible_user_id">
                                            <SelectValue
                                                placeholder="Selecione..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="user in props.users"
                                                :key="user.uuid"
                                                :value="user.uuid"
                                            >
                                                {{ user.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="errors.responsible_user_id"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="lead_source_id"
                                        >Origem do Lead</Label
                                    >
                                    <Select name="lead_source_id">
                                        <SelectTrigger id="lead_source_id">
                                            <SelectValue
                                                placeholder="Nenhuma"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="ls in props.leadSources"
                                                :key="ls.uuid"
                                                :value="ls.uuid"
                                            >
                                                {{ ls.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="errors.lead_source_id"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="task_type"
                                        >Tarefa Vinculada</Label
                                    >
                                    <Select name="task_type">
                                        <SelectTrigger id="task_type">
                                            <SelectValue
                                                placeholder="Selecione..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="retorno_ligacao"
                                                >Retorno de Ligação</SelectItem
                                            >
                                            <SelectItem value="agendamento"
                                                >Agendamento</SelectItem
                                            >
                                            <SelectItem value="lembrete_agenda"
                                                >Lembrete de Agenda</SelectItem
                                            >
                                            <SelectItem value="reagendamento"
                                                >Reagendamento</SelectItem
                                            >
                                            <SelectItem value="double_check"
                                                >Double Check</SelectItem
                                            >
                                            <SelectItem
                                                value="provavel_matricula"
                                                >Provável Matrícula</SelectItem
                                            >
                                            <SelectItem value="evento"
                                                >Evento</SelectItem
                                            >
                                            <SelectItem value="lembrete_evento"
                                                >Lembrete de Evento</SelectItem
                                            >
                                            <SelectItem
                                                value="reagendamento_evento"
                                                >Reagendamento de
                                                Evento</SelectItem
                                            >
                                            <SelectItem
                                                value="double_check_evento"
                                                >Double Check de
                                                Evento</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="errors.task_type" />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="history">Histórico</Label>
                                    <textarea
                                        id="history"
                                        name="history"
                                        rows="5"
                                        placeholder="Histórico da oportunidade..."
                                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    ></textarea>
                                    <InputError :message="errors.history" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="indications"
                                        >Indicações / Referências</Label
                                    >
                                    <textarea
                                        id="indications"
                                        name="indications"
                                        rows="5"
                                        placeholder="Indicações / referências..."
                                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    ></textarea>
                                    <InputError :message="errors.indications" />
                                </div>
                            </div>
                        </div>

                        <!-- Aba 2: Aluno / Responsável -->
                        <div
                            v-show="activeTab === 'aluno'"
                            class="space-y-6 p-6"
                        >
                            <!-- Card Aluno -->
                            <div
                                class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                            >
                                <h3 class="text-lg font-medium">Aluno</h3>

                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div class="space-y-2">
                                        <Label for="student_name">
                                            Nome do Aluno
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Input
                                            id="student_name"
                                            name="student_name"
                                            placeholder="Nome completo do aluno"
                                        />
                                        <InputError
                                            :message="errors.student_name"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="student_cpf"
                                            >CPF do Aluno</Label
                                        >
                                        <div class="relative">
                                            <Input
                                                id="student_cpf"
                                                name="student_cpf"
                                                placeholder="000.000.000-00"
                                                class="pr-8"
                                                @input="handleStudentCpfInput"
                                            />
                                            <span
                                                v-if="isLoadingStudent"
                                                class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                            ></span>
                                        </div>
                                        <p
                                            v-if="studentCpfError"
                                            class="text-xs text-destructive"
                                        >
                                            {{ studentCpfError }}
                                        </p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="student_birth_date"
                                            >Data de Nascimento</Label
                                        >
                                        <Input
                                            id="student_birth_date"
                                            name="student_birth_date"
                                            type="date"
                                        />
                                        <InputError
                                            :message="errors.student_birth_date"
                                        />
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-4">
                                    <div class="space-y-2">
                                        <Label for="grade_id">
                                            Série/Turma
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Select name="grade_id">
                                            <SelectTrigger id="grade_id">
                                                <SelectValue
                                                    placeholder="Selecione..."
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="grade in props.grades"
                                                    :key="grade.uuid"
                                                    :value="grade.uuid"
                                                >
                                                    {{ grade.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            :message="errors.grade_id"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="segment_id">Segmento</Label>
                                        <Select name="segment_id">
                                            <SelectTrigger id="segment_id">
                                                <SelectValue
                                                    placeholder="Selecione..."
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="segment in props.segments"
                                                    :key="segment.uuid"
                                                    :value="segment.uuid"
                                                >
                                                    {{ segment.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            :message="errors.segment_id"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="school_year_id">
                                            Ano Letivo
                                            <span class="text-destructive"
                                                >*</span
                                            >
                                        </Label>
                                        <Select name="school_year_id">
                                            <SelectTrigger id="school_year_id">
                                                <SelectValue
                                                    placeholder="Selecione..."
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="sy in props.schoolYears"
                                                    :key="sy.uuid"
                                                    :value="sy.uuid"
                                                >
                                                    {{ sy.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            :message="errors.school_year_id"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label>Unidade</Label>
                                        <Input
                                            :default-value="
                                                props.school.trade_name ??
                                                props.school.legal_name
                                            "
                                            readonly
                                            class="cursor-not-allowed bg-muted"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Card Responsável -->
                            <div
                                class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                            >
                                <h3 class="text-lg font-medium">Responsável</h3>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="guardian_name"
                                            >Nome do Responsável</Label
                                        >
                                        <Input
                                            id="guardian_name"
                                            name="guardian_name"
                                            placeholder="Nome completo do responsável"
                                        />
                                        <InputError
                                            :message="errors.guardian_name"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="guardian_cpf"
                                            >CPF do Responsável</Label
                                        >
                                        <div class="relative">
                                            <Input
                                                id="guardian_cpf"
                                                name="guardian_cpf"
                                                placeholder="000.000.000-00"
                                                class="pr-8"
                                                @input="handleGuardianCpfInput"
                                            />
                                            <span
                                                v-if="isLoadingGuardian"
                                                class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                            ></span>
                                        </div>
                                        <p
                                            v-if="guardianCpfError"
                                            class="text-xs text-destructive"
                                        >
                                            {{ guardianCpfError }}
                                        </p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="guardian_phone"
                                            >Telefone</Label
                                        >
                                        <Input
                                            id="guardian_phone"
                                            name="guardian_phone"
                                            placeholder="(00) 00000-0000"
                                            @input="handleGuardianPhoneInput"
                                        />
                                        <InputError
                                            :message="errors.guardian_phone"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="guardian_email"
                                            >E-mail</Label
                                        >
                                        <Input
                                            id="guardian_email"
                                            name="guardian_email"
                                            type="email"
                                            placeholder="email@exemplo.com"
                                        />
                                        <InputError
                                            :message="errors.guardian_email"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Card Endereço -->
                            <div
                                class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                            >
                                <h3 class="text-lg font-medium">Endereço</h3>

                                <div class="grid gap-4 sm:grid-cols-12">
                                    <div class="space-y-2 sm:col-span-2">
                                        <Label for="zip_code">CEP</Label>
                                        <div class="relative">
                                            <Input
                                                id="zip_code"
                                                name="zip_code"
                                                placeholder="00000-000"
                                                class="pr-8"
                                                @input="handleZipCodeInput"
                                                @blur="handleZipCodeBlur"
                                            />
                                            <span
                                                v-if="isLoadingCep"
                                                class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                            ></span>
                                        </div>
                                        <p
                                            v-if="cepError"
                                            class="text-xs text-destructive"
                                        >
                                            {{ cepError }}
                                        </p>
                                        <InputError
                                            :message="errors.zip_code"
                                        />
                                    </div>

                                    <div class="space-y-2 sm:col-span-5">
                                        <Label for="street">Logradouro</Label>
                                        <Input
                                            id="street"
                                            name="street"
                                            placeholder="Rua, Av., etc."
                                        />
                                        <InputError :message="errors.street" />
                                    </div>

                                    <div class="space-y-2 sm:col-span-2">
                                        <Label for="number">Número</Label>
                                        <Input
                                            id="number"
                                            name="number"
                                            placeholder="Número"
                                        />
                                        <InputError :message="errors.number" />
                                    </div>

                                    <div class="space-y-2 sm:col-span-3">
                                        <Label for="complement"
                                            >Complemento</Label
                                        >
                                        <Input
                                            id="complement"
                                            name="complement"
                                            placeholder="Sala, Andar, etc."
                                        />
                                        <InputError
                                            :message="errors.complement"
                                        />
                                    </div>

                                    <div class="space-y-2 sm:col-span-3">
                                        <Label for="neighborhood">Bairro</Label>
                                        <Input
                                            id="neighborhood"
                                            name="neighborhood"
                                            placeholder="Bairro"
                                        />
                                        <InputError
                                            :message="errors.neighborhood"
                                        />
                                    </div>

                                    <div class="space-y-2 sm:col-span-7">
                                        <Label for="city">Cidade</Label>
                                        <Input
                                            id="city"
                                            name="city"
                                            placeholder="Cidade"
                                        />
                                        <InputError :message="errors.city" />
                                    </div>

                                    <div class="space-y-2 sm:col-span-2">
                                        <Label for="state">UF</Label>
                                        <Input
                                            id="state"
                                            name="state"
                                            placeholder="UF"
                                            maxlength="2"
                                        />
                                        <InputError :message="errors.state" />
                                    </div>
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
                            :disabled="processing"
                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            @click="() => router.visit(index().url)"
                        >
                            Cancelar
                        </Button>

                        <Button
                            type="submit"
                            :disabled="
                                processing ||
                                !!guardianCpfError ||
                                isLoadingGuardian ||
                                !!studentCpfError ||
                                isLoadingStudent ||
                                !!cepError ||
                                isLoadingCep
                            "
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            {{
                                processing
                                    ? 'Salvando...'
                                    : 'Salvar Oportunidade'
                            }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
