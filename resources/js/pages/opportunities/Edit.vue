<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import { useCpfLookup } from '@/composables/useCpfLookup';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/tenant/opportunities';
import type { BreadcrumbItem } from '@/types';
import type {
    Grade,
    Guardian,
    LeadSource,
    Opportunity,
    School,
    SchoolYear,
    Segment,
    Student,
    TenantUser,
} from '@/types/crm';

const props = defineProps<{
    school: School;
    opportunity: Opportunity;
    students: Student[];
    guardians: Guardian[];
    grades: Grade[];
    schoolYears: SchoolYear[];
    leadSources: LeadSource[];
    users: TenantUser[];
    segments: Segment[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: index().url },
    { title: 'Editar Oportunidade', href: '#' },
];

const toast = useToast();

const isTerminal = computed(
    () =>
        props.opportunity.status === 'matricula' ||
        props.opportunity.status === 'recusado',
);

// Student CPF lookup
const foundStudent = ref<Student | null>(props.opportunity.student ?? null);

const {
    isLoading: isLoadingStudent,
    error: studentCpfError,
    triggerLookup: lookupStudent,
} = useCpfLookup({
    type: 'student',
    onFound: (data) => {
        foundStudent.value = data as Student;
    },
    onNotFound: () => {
        foundStudent.value = null;
    },
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

// Guardian CPF lookup
const foundGuardian = ref<Guardian | null>(props.opportunity.guardian ?? null);

const {
    isLoading: isLoadingGuardian,
    error: guardianCpfError,
    triggerLookup: lookupGuardian,
} = useCpfLookup({
    type: 'guardian',
    onFound: (data) => {
        foundGuardian.value = data as Guardian;
    },
    onNotFound: () => {
        foundGuardian.value = null;
    },
});

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

const historyValue = ref(props.opportunity.history ?? '');
const indicationsValue = ref(props.opportunity.indications ?? '');

function handleSuccess(): void {
    toast.success('Oportunidade atualizada com sucesso.');
    router.visit(index().url);
}

function handleError(): void {
    toast.error('Erro ao atualizar oportunidade. Verifique os campos.');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Oportunidade" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading
                    title="Editar Oportunidade"
                    description="Alterar os dados da oportunidade cadastrada."
                    class="pt-8"
                />
            </div>

            <div class="rounded-md border">
                <Form
                    method="put"
                    :action="
                        update({ opportunity: props.opportunity.uuid }).url
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                    @success="handleSuccess"
                    @error="handleError"
                >
                    <div class="space-y-6 p-6">
                        <!-- Terminal status alert -->
                        <Alert v-if="isTerminal" variant="destructive">
                            <AlertCircle class="h-4 w-4" />
                            <AlertTitle>Oportunidade encerrada</AlertTitle>
                            <AlertDescription>
                                Esta oportunidade está em status terminal e não
                                pode ser editada.
                            </AlertDescription>
                        </Alert>

                        <Tabs default-value="cadastro">
                            <TabsList
                                class="w-full justify-start rounded-none border-b bg-transparent px-2 pt-2"
                            >
                                <TabsTrigger
                                    value="cadastro"
                                    class="rounded-b-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                                    >Informações do Cadastro</TabsTrigger
                                >
                                <TabsTrigger
                                    value="aluno"
                                    class="rounded-b-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                                    >Aluno / Responsável</TabsTrigger
                                >
                                <TabsTrigger
                                    value="complementar"
                                    class="rounded-b-none data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                                    >Informações Complementares</TabsTrigger
                                >
                            </TabsList>

                            <!-- Aba 1: Informações do Cadastro -->
                            <TabsContent value="cadastro" class="m-0">
                                <div class="space-y-6 p-6">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <!-- Tipo de Cadastro -->
                                        <div class="space-y-2">
                                            <Label for="registration_type"
                                                >Tipo de Cadastro</Label
                                            >
                                            <Select
                                                name="registration_type"
                                                :default-value="
                                                    props.opportunity
                                                        .registration_type ??
                                                    undefined
                                                "
                                                :disabled="isTerminal"
                                            >
                                                <SelectTrigger
                                                    id="registration_type"
                                                >
                                                    <SelectValue
                                                        placeholder="Selecione..."
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        value="agendamento"
                                                        >Agendamento</SelectItem
                                                    >
                                                    <SelectItem value="evento"
                                                        >Evento</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="
                                                    errors.registration_type
                                                "
                                            />
                                        </div>

                                        <!-- Responsável pelo atendimento -->
                                        <div class="space-y-2">
                                            <Label for="responsible_user_id"
                                                >Responsável pelo
                                                Atendimento</Label
                                            >
                                            <Select
                                                name="responsible_user_id"
                                                :default-value="
                                                    props.opportunity
                                                        .responsible_user
                                                        ?.uuid ?? undefined
                                                "
                                                :disabled="isTerminal"
                                            >
                                                <SelectTrigger
                                                    id="responsible_user_id"
                                                >
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
                                                :message="
                                                    errors.responsible_user_id
                                                "
                                            />
                                        </div>

                                        <!-- Origem do Lead -->
                                        <div class="space-y-2">
                                            <Label for="lead_source_id"
                                                >Origem do Lead</Label
                                            >
                                            <Select
                                                name="lead_source_id"
                                                :default-value="
                                                    props.opportunity
                                                        .lead_source?.uuid ??
                                                    undefined
                                                "
                                                :disabled="isTerminal"
                                            >
                                                <SelectTrigger
                                                    id="lead_source_id"
                                                >
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
                                                        {{ ls.nome }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="errors.lead_source_id"
                                            />
                                        </div>

                                        <!-- Tarefa Vinculada (placeholder) -->
                                        <div class="space-y-2">
                                            <Label for="task_placeholder"
                                                >Tarefa Vinculada</Label
                                            >
                                            <Select disabled>
                                                <SelectTrigger
                                                    id="task_placeholder"
                                                >
                                                    <SelectValue
                                                        placeholder="Em breve..."
                                                    />
                                                </SelectTrigger>
                                                <SelectContent />
                                            </Select>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <!-- Aba 2: Aluno / Responsável -->
                            <TabsContent value="aluno" class="m-0">
                                <div class="space-y-6 p-6">
                                    <!-- Card Aluno -->
                                    <div
                                        class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                                    >
                                        <h3 class="text-lg font-medium">
                                            Aluno
                                        </h3>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="student_name">
                                                    Nome do Aluno
                                                    <span
                                                        class="text-destructive"
                                                        >*</span
                                                    >
                                                </Label>
                                                <Input
                                                    id="student_name"
                                                    name="student_name"
                                                    placeholder="Nome completo do aluno"
                                                    :default-value="
                                                        props.opportunity
                                                            .student?.nome ?? ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="
                                                        errors.student_name
                                                    "
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="student-cpf"
                                                    >CPF do Aluno</Label
                                                >
                                                <div class="relative">
                                                    <Input
                                                        id="student-cpf"
                                                        name="student_cpf"
                                                        placeholder="000.000.000-00"
                                                        class="pr-8"
                                                        :disabled="isTerminal"
                                                        :default-value="props.opportunity.student?.cpf ?? ''"
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
                                                    <span
                                                        class="text-destructive"
                                                        >*</span
                                                    >
                                                </Label>
                                                <Select
                                                    name="grade_id"
                                                    :default-value="
                                                        props.opportunity.grade
                                                            ?.uuid ?? ''
                                                    "
                                                    :disabled="isTerminal"
                                                >
                                                    <SelectTrigger
                                                        id="grade_id"
                                                    >
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
                                                            {{ grade.nome }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    :message="errors.grade_id"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="segment_id"
                                                    >Segmento</Label
                                                >
                                                <Select
                                                    name="segment_id"
                                                    :default-value="
                                                        props.opportunity
                                                            .segment?.uuid ??
                                                        undefined
                                                    "
                                                    :disabled="isTerminal"
                                                >
                                                    <SelectTrigger
                                                        id="segment_id"
                                                    >
                                                        <SelectValue
                                                            placeholder="Selecione..."
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem
                                                            v-for="segment in props.segments"
                                                            :key="segment.uuid"
                                                            :value="
                                                                segment.uuid
                                                            "
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
                                                    <span
                                                        class="text-destructive"
                                                        >*</span
                                                    >
                                                </Label>
                                                <Select
                                                    name="school_year_id"
                                                    :default-value="
                                                        props.opportunity
                                                            .school_year
                                                            ?.uuid ?? ''
                                                    "
                                                    :disabled="isTerminal"
                                                >
                                                    <SelectTrigger
                                                        id="school_year_id"
                                                    >
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
                                                            {{ sy.nome }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    :message="
                                                        errors.school_year_id
                                                    "
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label>Unidade</Label>
                                                <Input
                                                    :value="
                                                        props.school
                                                            .razao_social
                                                    "
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
                                        <h3 class="text-lg font-medium">
                                            Responsável
                                        </h3>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="guardian_name"
                                                    >Nome do Responsável</Label
                                                >
                                                <Input
                                                    id="guardian_name"
                                                    name="guardian_name"
                                                    placeholder="Nome completo do responsável"
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian?.nome ??
                                                        ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="
                                                        errors.guardian_name
                                                    "
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="guardian-cpf"
                                                    >CPF do Responsável</Label
                                                >
                                                <div class="relative">
                                                    <Input
                                                        id="guardian-cpf"
                                                        name="guardian_cpf"
                                                        placeholder="000.000.000-00"
                                                        class="pr-8"
                                                        :disabled="isTerminal"
                                                        :default-value="props.opportunity.guardian?.cpf ?? ''"
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
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian
                                                            ?.telefone ?? ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="
                                                        errors.guardian_phone
                                                    "
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
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian?.email ??
                                                        ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="
                                                        errors.guardian_email
                                                    "
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Endereço -->
                                    <div
                                        class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                                    >
                                        <h3 class="text-lg font-medium">
                                            Endereço
                                        </h3>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="zip_code"
                                                    >CEP</Label
                                                >
                                                <Input
                                                    id="zip_code"
                                                    name="zip_code"
                                                    placeholder="00000-000"
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian?.cep ?? ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="errors.zip_code"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="street"
                                                    >Logradouro</Label
                                                >
                                                <Input
                                                    id="street"
                                                    name="street"
                                                    placeholder="Rua, Av., etc."
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian
                                                            ?.logradouro ?? ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="errors.street"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="number"
                                                    >Número</Label
                                                >
                                                <Input
                                                    id="number"
                                                    name="number"
                                                    placeholder="Número"
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian?.numero ??
                                                        ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="errors.number"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="neighborhood"
                                                    >Bairro</Label
                                                >
                                                <Input
                                                    id="neighborhood"
                                                    name="neighborhood"
                                                    placeholder="Bairro"
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian?.bairro ??
                                                        ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="
                                                        errors.neighborhood
                                                    "
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="city">Cidade</Label>
                                                <Input
                                                    id="city"
                                                    name="city"
                                                    placeholder="Cidade"
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian?.cidade ??
                                                        ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="errors.city"
                                                />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="state"
                                                    >Estado</Label
                                                >
                                                <Input
                                                    id="state"
                                                    name="state"
                                                    placeholder="UF"
                                                    :default-value="
                                                        props.opportunity
                                                            .guardian?.estado ??
                                                        ''
                                                    "
                                                    :disabled="isTerminal"
                                                />
                                                <InputError
                                                    :message="errors.state"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <!-- Aba 3: Informações Complementares -->
                            <TabsContent value="complementar" class="m-0">
                                <div class="space-y-6 p-6">
                                    <div class="space-y-2">
                                        <Label for="history">Histórico</Label>
                                        <textarea
                                            id="history"
                                            name="history"
                                            rows="5"
                                            placeholder="Histórico da oportunidade..."
                                            :disabled="isTerminal"
                                            v-model="historyValue"
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
                                            :disabled="isTerminal"
                                            v-model="indicationsValue"
                                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        ></textarea>
                                        <InputError
                                            :message="errors.indications"
                                        />
                                    </div>
                                </div>
                            </TabsContent>
                        </Tabs>
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
                            Voltar
                        </Button>

                        <Button
                            v-if="!isTerminal"
                            type="submit"
                            :disabled="processing"
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            {{
                                processing ? 'Salvando...' : 'Salvar Alterações'
                            }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
