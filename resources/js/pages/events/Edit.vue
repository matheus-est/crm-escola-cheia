<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    DoorOpen,
    Info,
    Link2,
    Plus,
    Search,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-vue-next';
import { type Component, computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RoomFormDialog from '@/components/RoomFormDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/tenant/events/index';
import { attach, detach } from '@/routes/tenant/events/opportunities/index';
import type { BreadcrumbItem } from '@/types';
import type {
    AvailableOpportunity,
    Event,
    EventType,
    Grade,
    Opportunity,
    OpportunityStatus,
    Room,
} from '@/types/crm';

const props = defineProps<{
    event: Event & {
        opportunities: (Opportunity & { student?: { name: string } | null })[];
    };
    grades: Grade[];
    rooms: Room[];
    school_name: string;
    event_types: EventType[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Eventos', href: index().url },
    { title: 'Editar Evento', href: '#' },
];

const toast = useToast();

type Tab = 'sobre' | 'salas' | 'oportunidades';
const activeTab = ref<Tab>('sobre');

const tabs: { value: Tab; label: string; icon: Component }[] = [
    { value: 'sobre', label: 'Sobre o Evento', icon: Info },
    { value: 'salas', label: 'Salas', icon: DoorOpen },
    { value: 'oportunidades', label: 'Oportunidades', icon: Users },
];

// has_no_date checkbox
const hasNoDate = ref(Boolean(props.event.has_no_date));
const eventDateEdit = ref(
    props.event.has_no_date ? '' : formatDatetimeLocal(props.event.event_date),
);

function handleHasNoDateChange(val: boolean): void {
    hasNoDate.value = val;
    if (val) eventDateEdit.value = '';
}

// Rooms selection — pre-select rooms from event
const selectedRoomUuids = ref<string[]>(
    props.event.rooms?.map((r) => r.uuid) ?? [],
);
const roomSearch = ref('');

const filteredRooms = computed(() =>
    props.rooms.filter((r) =>
        r.name.toLowerCase().includes(roomSearch.value.toLowerCase()),
    ),
);

function toggleRoom(uuid: string): void {
    const idx = selectedRoomUuids.value.indexOf(uuid);
    if (idx === -1) {
        selectedRoomUuids.value.push(uuid);
    } else {
        selectedRoomUuids.value.splice(idx, 1);
    }
}

function isRoomSelected(uuid: string): boolean {
    return selectedRoomUuids.value.includes(uuid);
}

// RoomFormDialog
const showRoomDialog = ref(false);

function handleRoomCreated(): void {
    router.reload({ preserveUrl: true });
}

function formatDatetimeLocal(dateStr: string | null): string {
    if (!dateStr) return '';
    return dateStr.slice(0, 16);
}

function handleSuccess(): void {
    toast.success('Evento atualizado com sucesso.');
}

function handleError(): void {
    toast.error('Erro ao atualizar evento. Verifique os campos.');
}

// Opportunity status labels/classes
const statusLabels: Record<OpportunityStatus, string> = {
    cadastro_inicial: 'Cadastro Inicial',
    agendamento: 'Agendamento',
    visita: 'Visita',
    matricula: 'Matrícula',
    recusado: 'Recusado',
};

const statusClasses: Record<OpportunityStatus, string> = {
    cadastro_inicial:
        'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20',
    agendamento:
        'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20',
    visita: 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/20',
    matricula:
        'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20',
    recusado:
        'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
};

// Available opportunities fetch + attach
const availableOpportunities = ref<AvailableOpportunity[]>([]);
const isLoadingAvailable = ref(false);
const attachingUuid = ref<string | null>(null);

async function fetchAvailableOpportunities(): Promise<void> {
    isLoadingAvailable.value = true;
    try {
        const response = await fetch(
            `/t/events/${props.event.uuid}/available-opportunities`,
            { headers: { Accept: 'application/json' } },
        );
        if (response.ok) {
            availableOpportunities.value =
                (await response.json()) as AvailableOpportunity[];
        }
    } catch {
        // silent — não bloqueia a UI
    } finally {
        isLoadingAvailable.value = false;
    }
}

function handleAttach(opportunityUuid: string): void {
    attachingUuid.value = opportunityUuid;
    router.post(
        attach({ event: props.event.uuid }).url,
        { opportunity_uuid: opportunityUuid },
        {
            preserveUrl: true,
            onSuccess: () => {
                void fetchAvailableOpportunities();
                toast.success('Oportunidade vinculada com sucesso.');
                attachingUuid.value = null;
            },
            onError: () => {
                toast.error('Erro ao vincular oportunidade.');
                attachingUuid.value = null;
            },
        },
    );
}

watch(activeTab, (tab) => {
    if (tab === 'oportunidades') {
        void fetchAvailableOpportunities();
    }
});

// Detach opportunity
const detachingUuid = ref<string | null>(null);

function handleDetach(opportunityUuid: string): void {
    detachingUuid.value = opportunityUuid;
    router.delete(
        detach({ event: props.event.uuid, opportunity: opportunityUuid }).url,
        {
            onSuccess: () => {
                toast.success('Oportunidade desvinculada.');
                detachingUuid.value = null;
                router.reload({ preserveUrl: true });
            },
            onError: () => {
                toast.error('Erro ao desvincular oportunidade.');
                detachingUuid.value = null;
            },
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar Evento" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading title="Editar Evento" class="pt-8" />
            </div>

            <div class="rounded-md border">
                <Form
                    method="put"
                    :action="update({ event: props.event.uuid }).url"
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

                        <!-- Tab 1: Sobre o Evento -->
                        <div
                            v-show="activeTab === 'sobre'"
                            class="space-y-6 p-6"
                        >
                            <div class="grid gap-4 sm:grid-cols-5">
                                <div class="space-y-2 sm:col-span-5">
                                    <Label for="title">
                                        Título do Evento
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        :default-value="props.event.title"
                                        placeholder="Título do evento"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="grade_uuid">Série</Label>
                                    <Select
                                        name="grade_uuid"
                                        :default-value="
                                            props.event.grade?.uuid ?? undefined
                                        "
                                    >
                                        <SelectTrigger id="grade_uuid">
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
                                    <InputError :message="errors.grade_uuid" />
                                </div>

                                <div class="space-y-2 sm:col-span-1">
                                    <Label for="max_capacity"
                                        >Nº Máximo de Inscritos</Label
                                    >
                                    <Input
                                        id="max_capacity"
                                        type="number"
                                        name="max_capacity"
                                        min="1"
                                        :default-value="
                                            props.event.max_capacity !== null
                                                ? String(
                                                      props.event.max_capacity,
                                                  )
                                                : ''
                                        "
                                        placeholder="Ex: 50"
                                        class="max-w-[6rem]"
                                    />
                                    <InputError
                                        :message="errors.max_capacity"
                                    />
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="event_type_uuid"
                                        >Tipo do Evento</Label
                                    >
                                    <Select
                                        name="event_type_uuid"
                                        :default-value="
                                            props.event.event_type?.uuid ??
                                            undefined
                                        "
                                    >
                                        <SelectTrigger id="event_type_uuid">
                                            <SelectValue
                                                placeholder="Selecione o tipo..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="et in props.event_types"
                                                :key="et.uuid"
                                                :value="et.uuid"
                                            >
                                                {{ et.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="errors.event_type_uuid"
                                    />
                                </div>

                                <div
                                    class="flex items-center gap-2 sm:col-span-5"
                                >
                                    <input
                                        type="hidden"
                                        name="has_no_date"
                                        value="0"
                                    />
                                    <input
                                        v-if="hasNoDate"
                                        type="hidden"
                                        name="has_no_date"
                                        value="1"
                                    />
                                    <Checkbox
                                        id="has_no_date"
                                        :model-value="hasNoDate"
                                        @update:modelValue="handleHasNoDateChange"
                                    />
                                    <Label
                                        for="has_no_date"
                                        class="cursor-pointer"
                                    >
                                        Este evento não possui Data
                                    </Label>
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="event_date"
                                        >Data do Evento
                                        <span
                                            v-if="!hasNoDate"
                                            class="text-destructive"
                                            >*</span
                                        ></Label
                                    >
                                    <input
                                        id="event_date"
                                        type="datetime-local"
                                        v-model="eventDateEdit"
                                        name="event_date"
                                        :disabled="hasNoDate"
                                        class="border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm"
                                    />
                                    <InputError :message="errors.event_date" />
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="location">Local</Label>
                                    <Input
                                        id="location"
                                        name="location"
                                        :default-value="
                                            props.event.location ?? ''
                                        "
                                        placeholder="Local do evento"
                                    />
                                    <InputError :message="errors.location" />
                                </div>

                                <div class="space-y-2 sm:col-span-1">
                                    <Label>Unidade</Label>
                                    <Input
                                        :default-value="props.school_name"
                                        readonly
                                        class="cursor-not-allowed bg-muted"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Salas -->
                        <div
                            v-show="activeTab === 'salas'"
                            class="space-y-4 p-6"
                        >
                            <!-- Hidden inputs for selected rooms -->
                            <input
                                v-for="uuid in selectedRoomUuids"
                                :key="uuid"
                                type="hidden"
                                name="room_uuids[]"
                                :value="uuid"
                            />

                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div class="relative flex-1">
                                    <Search
                                        class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground"
                                    />
                                    <Input
                                        v-model="roomSearch"
                                        placeholder="Buscar sala..."
                                        class="pl-9"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="shrink-0"
                                    @click="showRoomDialog = true"
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Adicionar Sala
                                </Button>
                            </div>

                            <div
                                v-if="filteredRooms.length > 0"
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <Card
                                    v-for="room in filteredRooms"
                                    :key="room.uuid"
                                    class="cursor-pointer transition-all"
                                    :class="
                                        isRoomSelected(room.uuid)
                                            ? 'ring-2 ring-primary'
                                            : 'hover:bg-muted/30'
                                    "
                                    @click="toggleRoom(room.uuid)"
                                >
                                    <CardContent class="p-4">
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-medium text-foreground"
                                                >
                                                    {{ room.name }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        room.capacity
                                                            ? `máx. ${room.capacity} pessoas`
                                                            : 'Sem capacidade definida'
                                                    }}
                                                </p>
                                            </div>
                                            <span
                                                v-if="room.is_external"
                                                class="inline-flex shrink-0 items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20 ring-inset dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/20"
                                            >
                                                Externa
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <div
                                v-else
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                {{
                                    roomSearch
                                        ? 'Nenhuma sala encontrada para a busca.'
                                        : 'Nenhuma sala cadastrada.'
                                }}
                            </div>
                        </div>

                        <!-- Tab 3: Oportunidades -->
                        <div
                            v-show="activeTab === 'oportunidades'"
                            class="space-y-6 p-6"
                        >
                            <!-- Linked opportunities table -->
                            <div v-if="props.event.opportunities.length > 0">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th
                                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                                            >
                                                Aluno
                                            </th>
                                            <th
                                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                                            >
                                                Status
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
                                            v-for="opp in props.event
                                                .opportunities"
                                            :key="opp.uuid"
                                            class="transition-colors hover:bg-muted/30"
                                        >
                                            <td
                                                class="px-3 py-3 font-medium text-foreground"
                                            >
                                                {{ opp.student?.name ?? '—' }}
                                            </td>
                                            <td class="px-3 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                                    :class="
                                                        statusClasses[
                                                            opp.status
                                                        ]
                                                    "
                                                >
                                                    {{
                                                        statusLabels[opp.status]
                                                    }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 rounded p-1 text-destructive hover:bg-muted"
                                                    title="Desvincular"
                                                    :disabled="
                                                        detachingUuid ===
                                                        opp.uuid
                                                    "
                                                    @click="
                                                        handleDetach(opp.uuid)
                                                    "
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    <span class="text-xs"
                                                        >Desvincular</span
                                                    >
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-else
                                class="py-8 text-center text-sm text-muted-foreground"
                            >
                                Nenhuma oportunidade vinculada a este evento.
                            </div>

                            <!-- Available opportunities table -->
                            <div class="rounded-md border bg-muted/20 p-4">
                                <h3
                                    class="mb-3 flex items-center gap-2 text-sm font-medium"
                                >
                                    <UserPlus class="h-4 w-4" />
                                    Vincular Oportunidade
                                </h3>

                                <div
                                    v-if="isLoadingAvailable"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    Carregando oportunidades...
                                </div>

                                <div
                                    v-else-if="
                                        availableOpportunities.length === 0
                                    "
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    Nenhuma oportunidade disponível para
                                    vincular.
                                </div>

                                <table v-else class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th
                                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                                            >
                                                Data de Criação
                                            </th>
                                            <th
                                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                                            >
                                                Oportunidade
                                            </th>
                                            <th
                                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                                            >
                                                Etapa
                                            </th>
                                            <th
                                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                                            >
                                                Ano Letivo
                                            </th>
                                            <th
                                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                                            >
                                                Porta do Funil
                                            </th>
                                            <th
                                                class="px-3 pb-3 text-right font-medium text-muted-foreground"
                                            >
                                                Vincular
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border/50">
                                        <tr
                                            v-for="opp in availableOpportunities"
                                            :key="opp.uuid"
                                            class="transition-colors hover:bg-muted/30"
                                        >
                                            <td
                                                class="px-3 py-3 text-muted-foreground"
                                            >
                                                {{
                                                    new Date(
                                                        opp.created_at,
                                                    ).toLocaleDateString(
                                                        'pt-BR',
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-3 py-3 font-medium text-foreground"
                                            >
                                                {{
                                                    opp.guardian_name ??
                                                    opp.student_name ??
                                                    '—'
                                                }}
                                            </td>
                                            <td class="px-3 py-3">
                                                <span
                                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                                    :class="
                                                        statusClasses[
                                                            opp.status
                                                        ]
                                                    "
                                                >
                                                    {{
                                                        statusLabels[opp.status]
                                                    }}
                                                </span>
                                            </td>
                                            <td
                                                class="px-3 py-3 text-muted-foreground"
                                            >
                                                {{
                                                    opp.school_year_name ?? '—'
                                                }}
                                            </td>
                                            <td
                                                class="px-3 py-3 text-muted-foreground"
                                            >
                                                {{
                                                    opp.registration_type ===
                                                    'agendamento'
                                                        ? 'Agendamento'
                                                        : opp.registration_type ===
                                                            'evento'
                                                          ? 'Evento'
                                                          : '—'
                                                }}
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 rounded p-1 text-green-600 hover:bg-muted disabled:opacity-50"
                                                    title="Vincular"
                                                    :disabled="
                                                        attachingUuid ===
                                                        opp.uuid
                                                    "
                                                    @click="
                                                        handleAttach(opp.uuid)
                                                    "
                                                >
                                                    <Link2 class="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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

        <RoomFormDialog
            v-model:open="showRoomDialog"
            mode="create"
            @success="handleRoomCreated"
        />
    </AppLayout>
</template>
