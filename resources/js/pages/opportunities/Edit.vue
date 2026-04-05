<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: index(props.school.uuid).url },
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
    cpf: studentCpf,
    isLoading: isLoadingStudent,
    error: studentCpfError,
    triggerLookup: lookupStudent,
} = useCpfLookup({
    type: 'student',
    schoolUuid: props.school.uuid,
    onFound: (data) => {
        foundStudent.value = data as Student;
    },
    onNotFound: () => {
        foundStudent.value = null;
    },
});

watch(studentCpf, (newVal) => {
    if (newVal) lookupStudent(newVal);
});

// Guardian CPF lookup
const foundGuardian = ref<Guardian | null>(props.opportunity.guardian ?? null);

const {
    cpf: guardianCpf,
    isLoading: isLoadingGuardian,
    error: guardianCpfError,
    triggerLookup: lookupGuardian,
} = useCpfLookup({
    type: 'guardian',
    schoolUuid: props.school.uuid,
    onFound: (data) => {
        foundGuardian.value = data as Guardian;
    },
    onNotFound: () => {
        foundGuardian.value = null;
    },
});

watch(guardianCpf, (newVal) => {
    if (newVal) lookupGuardian(newVal);
});

const observationsValue = ref(props.opportunity.observations ?? '');

function handleSuccess(): void {
    toast.success('Oportunidade atualizada com sucesso.');
    router.visit(index(props.school.uuid).url);
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
                <Link
                    :href="index(props.school.uuid).url"
                    class="rounded-md p-2 hover:bg-muted"
                >
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
                        update({
                            school_uuid: props.school.uuid,
                            opportunity: props.opportunity.uuid,
                        }).url
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

                        <!-- Aluno -->
                        <div
                            class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                        >
                            <h3 class="text-lg font-medium">Aluno</h3>

                            <!-- Current student display -->
                            <div
                                v-if="foundStudent"
                                class="rounded-md border border-blue-200 bg-blue-50 p-3 text-sm dark:border-blue-800 dark:bg-blue-900/20"
                            >
                                <p
                                    class="font-medium text-blue-800 dark:text-blue-300"
                                >
                                    {{ foundStudent.nome }}
                                </p>
                                <p class="text-blue-600 dark:text-blue-400">
                                    CPF: {{ foundStudent.cpf }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="student-cpf"
                                    >Alterar CPF do Aluno</Label
                                >
                                <div class="relative">
                                    <Input
                                        id="student-cpf"
                                        v-model="studentCpf"
                                        placeholder="000.000.000-00"
                                        :disabled="isTerminal"
                                        class="pr-8"
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
                                <InputError :message="errors.student_id" />
                            </div>

                            <input
                                type="hidden"
                                name="student_id"
                                :value="foundStudent?.uuid ?? ''"
                            />
                        </div>

                        <!-- Responsável -->
                        <div
                            class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                        >
                            <h3 class="text-lg font-medium">Responsável</h3>

                            <!-- Current guardian display -->
                            <div
                                v-if="foundGuardian"
                                class="rounded-md border border-blue-200 bg-blue-50 p-3 text-sm dark:border-blue-800 dark:bg-blue-900/20"
                            >
                                <p
                                    class="font-medium text-blue-800 dark:text-blue-300"
                                >
                                    {{ foundGuardian.nome }}
                                </p>
                                <p class="text-blue-600 dark:text-blue-400">
                                    CPF: {{ foundGuardian.cpf }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="guardian-cpf"
                                    >Alterar CPF do Responsável</Label
                                >
                                <div class="relative">
                                    <Input
                                        id="guardian-cpf"
                                        v-model="guardianCpf"
                                        placeholder="000.000.000-00"
                                        :disabled="isTerminal"
                                        class="pr-8"
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
                                <InputError :message="errors.guardian_id" />
                            </div>

                            <input
                                type="hidden"
                                name="guardian_id"
                                :value="foundGuardian?.uuid ?? ''"
                            />
                        </div>

                        <!-- Dados da Oportunidade -->
                        <div
                            class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                        >
                            <h3 class="text-lg font-medium">
                                Dados da Oportunidade
                            </h3>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="grade_id"
                                        >Série/Turma
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <Select
                                        name="grade_id"
                                        :default-value="
                                            props.opportunity.grade?.uuid ?? ''
                                        "
                                        :disabled="isTerminal"
                                    >
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
                                                {{ grade.nome }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="errors.grade_id" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="school_year_id"
                                        >Ano Letivo
                                        <span class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <Select
                                        name="school_year_id"
                                        :default-value="
                                            props.opportunity.school_year
                                                ?.uuid ?? ''
                                        "
                                        :disabled="isTerminal"
                                    >
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
                                                {{ sy.nome }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="errors.school_year_id"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="lead_source_id"
                                        >Origem do Lead</Label
                                    >
                                    <Select
                                        name="lead_source_id"
                                        :default-value="
                                            props.opportunity.lead_source
                                                ?.uuid ?? ''
                                        "
                                        :disabled="isTerminal"
                                    >
                                        <SelectTrigger id="lead_source_id">
                                            <SelectValue
                                                placeholder="Nenhuma"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value=""
                                                >Nenhuma</SelectItem
                                            >
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

                                <div class="space-y-2">
                                    <Label for="responsible_user_id"
                                        >Responsável</Label
                                    >
                                    <Select
                                        name="responsible_user_id"
                                        :default-value="
                                            props.opportunity.responsible_user
                                                ?.uuid ?? ''
                                        "
                                        :disabled="isTerminal"
                                    >
                                        <SelectTrigger id="responsible_user_id">
                                            <SelectValue
                                                placeholder="Selecione..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value=""
                                                >Nenhum</SelectItem
                                            >
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
                            </div>

                            <div class="space-y-2">
                                <Label for="observations">Observações</Label>
                                <textarea
                                    id="observations"
                                    name="observations"
                                    rows="4"
                                    :disabled="isTerminal"
                                    v-model="observationsValue"
                                    class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Observações sobre a oportunidade..."
                                ></textarea>
                                <InputError :message="errors.observations" />
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="
                                    () =>
                                        router.visit(
                                            index(props.school.uuid).url,
                                        )
                                "
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
                                    processing
                                        ? 'Salvando...'
                                        : 'Salvar Alterações'
                                }}
                            </Button>
                        </div>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
