<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
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
import { validate_cpf } from '@/routes/tenant/guardians';
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

type Tab = 'cadastro' | 'aluno' | 'complementar';
const activeTab = ref<Tab>('cadastro');

const tabs = [
    { value: 'cadastro' as Tab, label: 'Informações do Cadastro' },
    { value: 'aluno' as Tab, label: 'Aluno / Responsável' },
    { value: 'complementar' as Tab, label: 'Informações Complementares' },
];

const guardianFilledByStudent = ref(false);
const guardianCpfInvalidError = ref<string | null>(null);
const isValidatingGuardianCpf = ref(false);

function fillInput(id: string, value: string): void {
    const el = document.getElementById(id) as
        | HTMLInputElement
        | HTMLTextAreaElement
        | null;
    if (el) el.value = value;
}

function fillGuardianFields(guardian: Guardian): void {
    fillInput('guardian_name', guardian.nome ?? '');
    fillInput('guardian_cpf', guardian.cpf ?? '');
    fillInput('guardian_phone', guardian.telefone ?? '');
    fillInput('guardian_email', guardian.email ?? '');
    fillInput('zip_code', guardian.cep ?? '');
    fillInput('street', guardian.logradouro ?? '');
    fillInput('number', guardian.numero ?? '');
    fillInput('neighborhood', guardian.bairro ?? '');
    fillInput('city', guardian.cidade ?? '');
    fillInput('state', guardian.estado ?? '');
}

const {
    isLoading: isLoadingStudent,
    error: studentCpfError,
    triggerLookup: lookupStudent,
} = useCpfLookup({
    type: 'student',
    onFound: (data) => {
        const student = data as Student;
        fillInput('student_name', student.nome ?? '');
        if (student.guardian) {
            guardianFilledByStudent.value = true;
            fillGuardianFields(student.guardian);
        }
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
    guardianCpfInvalidError.value = null;
}

async function handleGuardianCpfBlur(event: Event): Promise<void> {
    if (guardianFilledByStudent.value) {
        guardianFilledByStudent.value = false;
        return;
    }

    const input = event.target as HTMLInputElement;
    const cpf = input.value.replace(/\D/g, '');

    if (cpf.length !== 11) {
        if (cpf.length > 0) {
            guardianCpfInvalidError.value = 'CPF inválido';
        }
        return;
    }

    isValidatingGuardianCpf.value = true;
    guardianCpfInvalidError.value = null;

    try {
        const response = await fetch(validate_cpf(cpf).url, {
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            const data = (await response.json()) as {
                valid: boolean;
                exists: boolean;
                guardian?: Guardian;
            };

            if (!data.valid) {
                guardianCpfInvalidError.value = 'CPF inválido';
            } else if (data.exists && data.guardian) {
                guardianCpfInvalidError.value = null;
                fillGuardianFields(data.guardian);
            } else {
                guardianCpfInvalidError.value = null;
            }
        }
    } catch {
        // silent — network errors do not block submission
    } finally {
        isValidatingGuardianCpf.value = false;
    }
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
}

function handleZipCodeBlur(event: Event): void {
    const input = event.target as HTMLInputElement;
    void cepLookup(input.value, ({ logradouro, bairro, cidade, estado }) => {
        fillInput('street', logradouro);
        fillInput('neighborhood', bairro);
        fillInput('city', cidade);
        fillInput('state', estado);
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
                                                :key="user.id"
                                                :value="user.id"
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
                                                :key="ls.id"
                                                :value="ls.id"
                                            >
                                                {{ ls.nome }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="errors.lead_source_id"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="task_placeholder"
                                        >Tarefa Vinculada</Label
                                    >
                                    <Select disabled>
                                        <SelectTrigger id="task_placeholder">
                                            <SelectValue
                                                placeholder="Em breve..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent />
                                    </Select>
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

                                <div class="grid gap-4 sm:grid-cols-2">
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
                                                    :key="grade.id"
                                                    :value="grade.id"
                                                >
                                                    {{ grade.nome }}
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
                                                    :key="segment.id"
                                                    :value="segment.id"
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
                                                    :key="sy.id"
                                                    :value="sy.id"
                                                >
                                                    {{ sy.nome }}
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
                                            :value="props.school.razao_social"
                                            disabled
                                            class="bg-muted/50"
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
                                                @blur="handleGuardianCpfBlur"
                                            />
                                            <span
                                                v-if="isValidatingGuardianCpf"
                                                class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                            ></span>
                                        </div>
                                        <p
                                            v-if="guardianCpfInvalidError"
                                            class="text-xs text-destructive"
                                        >
                                            {{ guardianCpfInvalidError }}
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

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
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

                                    <div class="space-y-2">
                                        <Label for="street">Logradouro</Label>
                                        <Input
                                            id="street"
                                            name="street"
                                            placeholder="Rua, Av., etc."
                                        />
                                        <InputError :message="errors.street" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="number">Número</Label>
                                        <Input
                                            id="number"
                                            name="number"
                                            placeholder="Número"
                                        />
                                        <InputError :message="errors.number" />
                                    </div>

                                    <div class="space-y-2">
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

                                    <div class="space-y-2">
                                        <Label for="city">Cidade</Label>
                                        <Input
                                            id="city"
                                            name="city"
                                            placeholder="Cidade"
                                        />
                                        <InputError :message="errors.city" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="state">Estado</Label>
                                        <Input
                                            id="state"
                                            name="state"
                                            placeholder="UF"
                                        />
                                        <InputError :message="errors.state" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Aba 3: Informações Complementares -->
                        <div
                            v-show="activeTab === 'complementar'"
                            class="space-y-6 p-6"
                        >
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
                                !!guardianCpfInvalidError ||
                                isValidatingGuardianCpf
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
