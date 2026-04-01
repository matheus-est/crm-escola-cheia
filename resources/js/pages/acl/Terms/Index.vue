<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Plus,
    Trash2,
    FileText,
    FileEdit,
    CheckCircle2,
    XCircle,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import Heading from '@/components/Heading.vue';
import PerPageSelect from '@/components/PerPageSelect.vue';
import TablePagination from '@/components/TablePagination.vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, create, edit, destroy } from '@/routes/terms';
import { type BreadcrumbItem } from '@/types';

interface TermVersion {
    id: number;
    uuid: string;
    version: string;
    title: string;
    content: string;
    effective_at: string;
    is_active: boolean;
    created_at: string;
    is_deletable: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedTerms {
    data: TermVersion[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Filters {
    sort_by: 'title' | 'version' | 'effective_at' | 'created_at';
    sort_dir: 'asc' | 'desc';
    per_page: number | 'all';
}

const props = defineProps<{
    terms: PaginatedTerms;
    filters?: Filters;
}>();

const page = usePage();
const permissions = computed(
    () => (page.props.auth?.permissions as string[]) ?? [],
);
const canEdit = computed(() => permissions.value.includes('terms_edit'));
const canDelete = computed(() => permissions.value.includes('terms_delete'));
const canAdd = computed(() => permissions.value.includes('terms_add'));

const terms = computed(() => props.terms.data);

const localFilters = ref({
    sort_by: props.filters?.sort_by ?? 'created_at',
    sort_dir: props.filters?.sort_dir ?? 'desc',
    per_page: String(props.filters?.per_page ?? 10),
});

function updatePerPage(value: string): void {
    localFilters.value.per_page = value;
    router.post(
        index().url,
        { ...localFilters.value },
        { preserveScroll: true },
    );
}

// View modal
const showViewModal = ref(false);
const termToView = ref<TermVersion | null>(null);

function openViewModal(term: TermVersion): void {
    termToView.value = term;
    showViewModal.value = true;
}

// Delete modal
const showDeleteModal = ref(false);
const termToDelete = ref<TermVersion | null>(null);

function openDeleteModal(term: TermVersion): void {
    termToDelete.value = term;
    showDeleteModal.value = true;
}

function handleDeleteSuccess(): void {
    showDeleteModal.value = false;
    termToDelete.value = null;
    router.reload({ preserveScroll: true });
}

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Termos de Uso', href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Termos de Uso" />

        <div class="space-y-6">
            <div class="flex items-start justify-between">
                <Heading title="Termos de Uso" />
            </div>

            <div class="flex items-center justify-between">
                <Link
                    v-if="canAdd"
                    :href="create().url"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nova Versão
                </Link>

                <PerPageSelect
                    :model-value="localFilters.per_page"
                    @update:model-value="updatePerPage"
                />
            </div>

            <div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Título
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Versão
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Vigência
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Criado em
                            </th>
                            <th
                                class="px-3 pb-3 text-right font-medium text-muted-foreground"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr
                            v-for="term in terms"
                            :key="term.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ term.title ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                v{{ term.version }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{
                                    term.effective_at
                                        ? new Date(
                                              term.effective_at,
                                          ).toLocaleDateString('pt-BR')
                                        : '—'
                                }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        term.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-400 dark:ring-emerald-500/20'
                                            : 'bg-muted/60 text-muted-foreground ring-border/50'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="term.is_active"
                                        class="mr-1 h-3 w-3"
                                    />
                                    <XCircle v-else class="mr-1 h-3 w-3" />
                                    {{ term.is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{
                                    new Date(
                                        term.created_at,
                                    ).toLocaleDateString('pt-BR')
                                }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        v-if="canEdit"
                                        :href="edit(term.uuid).url"
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                    >
                                        <FileEdit class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded p-1 hover:bg-muted"
                                        title="Visualizar"
                                        @click="openViewModal(term)"
                                    >
                                        <FileText class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="canDelete && term.is_deletable"
                                        type="button"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteModal(term)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div
                    v-if="terms.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <FileText class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhum termo encontrado
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Comece criando uma nova versão dos termos.
                    </p>
                    <Link
                        v-if="canAdd"
                        :href="create().url"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Nova Versão
                    </Link>
                </div>
            </div>

            <TablePagination :paginator="props.terms" />
        </div>

        <Dialog v-if="termToView" v-model:open="showViewModal">
            <DialogContent
                class="flex max-h-[90vh] w-[90vw] max-w-2xl flex-col gap-0 p-0"
            >
                <div class="shrink-0 border-b px-8 py-5">
                    <p
                        class="text-xs font-medium tracking-widest text-muted-foreground uppercase"
                    >
                        Termos de Uso
                    </p>
                    <h2 class="mt-1 text-lg font-semibold text-foreground">
                        Termos de Uso e Política de Privacidade
                    </h2>
                    <div
                        class="mt-1.5 flex items-center gap-3 text-xs text-muted-foreground"
                    >
                        <span>Versão {{ termToView.version }}</span>
                        <span
                            class="h-1 w-1 rounded-full bg-muted-foreground/40"
                        ></span>
                        <span
                            >Vigência:
                            {{
                                new Date(
                                    termToView.effective_at,
                                ).toLocaleDateString('pt-BR')
                            }}</span
                        >
                        <span
                            class="h-1 w-1 rounded-full bg-muted-foreground/40"
                        ></span>
                        <span
                            class="inline-flex items-center gap-1"
                            :class="
                                termToView.is_active
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <CheckCircle2
                                v-if="termToView.is_active"
                                class="h-3 w-3"
                            />
                            <XCircle v-else class="h-3 w-3" />
                            {{
                                termToView.is_active
                                    ? 'Versão ativa'
                                    : 'Versão inativa'
                            }}
                        </span>
                    </div>
                </div>

                <!-- Conteúdo rolável -->
                <div class="flex-1 overflow-y-auto px-8 py-6">
                    <div
                        class="prose max-w-none prose-neutral dark:prose-invert prose-headings:font-semibold prose-headings:text-foreground prose-h1:text-xl prose-h2:text-base prose-h3:text-sm prose-p:text-sm prose-p:leading-relaxed prose-p:text-foreground/80 prose-strong:font-semibold prose-strong:text-foreground prose-li:text-sm prose-li:text-foreground/80 prose-hr:border-border"
                        v-html="termToView.content"
                    />
                </div>
            </DialogContent>
        </Dialog>

        <!-- Modal excluir -->
        <ConfirmDeleteModal
            v-if="termToDelete"
            v-model:open="showDeleteModal"
            title="Confirmar Exclusão"
            :message="`Tem certeza que deseja excluir a versão ${termToDelete.version} dos termos de uso?`"
            :action="destroy(termToDelete.uuid).url"
            success-message="Termo excluído com sucesso."
            @success="handleDeleteSuccess"
        />
    </AppLayout>
</template>
