<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
    Student,
    TenantUser,
} from '@/types/crm';

const props = defineProps<{
    school: School;
    students: Student[];
    guardians: Guardian[];
    grades: Grade[];
    schoolYears: SchoolYear[];
    leadSources: LeadSource[];
    users: TenantUser[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: index().url },
    { title: 'Nova Oportunidade', href: '#' },
];

const toast = useToast();

// Student CPF lookup
const foundStudent = ref<Student | null>(null);

const {
    cpf: studentCpf,
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

watch(studentCpf, (newVal) => {
    lookupStudent(newVal);
});

// Guardian CPF lookup
const foundGuardian = ref<Guardian | null>(null);

const {
    cpf: guardianCpf,
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

watch(guardianCpf, (newVal) => {
    lookupGuardian(newVal);
});

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
                <Link
                    :href="index().url"
                    class="rounded-md p-2 hover:bg-muted"
                >
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
                    <div class="space-y-6 p-6">
                        <!-- Aluno -->
                        <div
                            class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                        >
                            <h3 class="text-lg font-medium">Aluno</h3>

                            <div class="space-y-2">
                                <Label for="student-cpf">CPF do Aluno</Label>
                                <div class="relative">
                                    <Input
                                        id="student-cpf"
                                        v-model="studentCpf"
                                        placeholder="000.000.000-00"
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

                            <div
                                v-if="foundStudent"
                                class="rounded-md border border-green-200 bg-green-50 p-3 text-sm dark:border-green-800 dark:bg-green-900/20"
                            >
                                <p
                                    class="font-medium text-green-800 dark:text-green-300"
                                >
                                    {{ foundStudent.nome }}
                                </p>
                                <p class="text-green-600 dark:text-green-400">
                                    CPF: {{ foundStudent.cpf }}
                                </p>
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

                            <div class="space-y-2">
                                <Label for="guardian-cpf"
                                    >CPF do Responsável</Label
                                >
                                <div class="relative">
                                    <Input
                                        id="guardian-cpf"
                                        v-model="guardianCpf"
                                        placeholder="000.000.000-00"
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

                            <div
                                v-if="foundGuardian"
                                class="rounded-md border border-green-200 bg-green-50 p-3 text-sm dark:border-green-800 dark:bg-green-900/20"
                            >
                                <p
                                    class="font-medium text-green-800 dark:text-green-300"
                                >
                                    {{ foundGuardian.nome }}
                                </p>
                                <p class="text-green-600 dark:text-green-400">
                                    CPF: {{ foundGuardian.cpf }}
                                </p>
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
                            </div>

                            <div class="space-y-2">
                                <Label for="observations">Observações</Label>
                                <textarea
                                    id="observations"
                                    name="observations"
                                    rows="4"
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
                                            index().url,
                                        )
                                "
                                :disabled="processing"
                                class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            >
                                Cancelar
                            </Button>

                            <Button
                                type="submit"
                                :disabled="processing"
                                class="bg-green-600 text-sm text-white hover:bg-green-700"
                            >
                                {{
                                    processing
                                        ? 'Salvando...'
                                        : 'Salvar Oportunidade'
                                }}
                            </Button>
                        </div>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
