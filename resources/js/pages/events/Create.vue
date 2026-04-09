<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, DoorOpen, Info, Plus, Search } from 'lucide-vue-next';
import { type Component, computed, ref } from 'vue';
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
import { index, store } from '@/routes/tenant/events/index';
import type { BreadcrumbItem } from '@/types';
import type { Grade, Room } from '@/types/crm';

const props = defineProps<{
    grades: Grade[];
    rooms: Room[];
    school_name: string;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Eventos', href: index().url },
    { title: 'Criar Evento', href: '#' },
];

const toast = useToast();

type Tab = 'sobre' | 'salas';
const activeTab = ref<Tab>('sobre');

const tabs: { value: Tab; label: string; icon: Component }[] = [
    { value: 'sobre', label: 'Sobre o Evento', icon: Info },
    { value: 'salas', label: 'Salas', icon: DoorOpen },
];

// Checkbox has_no_date — controlled locally to disable date field
const hasNoDate = ref(false);

// Rooms selection
const selectedRoomUuids = ref<string[]>([]);
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

function handleSuccess(): void {
    toast.success('Evento criado com sucesso.');
}

function handleError(): void {
    toast.error('Erro ao criar evento. Verifique os campos.');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Criar Evento" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading title="Criar Evento" class="pt-8" />
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

                        <!-- Tab 1: Sobre o Evento -->
                        <div
                            v-show="activeTab === 'sobre'"
                            class="space-y-6 p-6"
                        >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="title">
                                        Título do Evento
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        placeholder="Título do evento"
                                        required
                                    />
                                    <InputError :message="errors.title" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="event_type"
                                        >Tipo do Evento</Label
                                    >
                                    <Select name="event_type">
                                        <SelectTrigger id="event_type">
                                            <SelectValue
                                                placeholder="Selecione..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="palestra"
                                                >Palestra</SelectItem
                                            >
                                            <SelectItem value="workshop"
                                                >Workshop</SelectItem
                                            >
                                            <SelectItem value="visita"
                                                >Visita</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="errors.event_type" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="grade_uuid">Série</Label>
                                    <Select name="grade_uuid">
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
                                                {{ grade.nome }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="errors.grade_uuid" />
                                </div>

                                <div
                                    class="flex items-center gap-2 sm:col-span-2"
                                >
                                    <input
                                        type="hidden"
                                        name="has_no_date"
                                        value="0"
                                    />
                                    <Checkbox
                                        id="has_no_date"
                                        name="has_no_date"
                                        :checked="hasNoDate"
                                        @update:checked="
                                            (val: boolean) => {
                                                hasNoDate = val;
                                            }
                                        "
                                    />
                                    <Label
                                        for="has_no_date"
                                        class="cursor-pointer"
                                    >
                                        Este evento não possui Data
                                    </Label>
                                </div>

                                <div class="space-y-2">
                                    <Label for="event_date"
                                        >Data do Evento</Label
                                    >
                                    <Input
                                        id="event_date"
                                        type="datetime-local"
                                        name="event_date"
                                        :disabled="hasNoDate"
                                    />
                                    <InputError :message="errors.event_date" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="location">Local</Label>
                                    <Input
                                        id="location"
                                        name="location"
                                        placeholder="Local do evento"
                                    />
                                    <InputError :message="errors.location" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="max_capacity"
                                        >Número Máximo De Inscritos</Label
                                    >
                                    <Input
                                        id="max_capacity"
                                        type="number"
                                        name="max_capacity"
                                        min="1"
                                        placeholder="Ex: 50"
                                    />
                                    <InputError
                                        :message="errors.max_capacity"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label>Unidade</Label>
                                    <Input
                                        :value="props.school_name"
                                        disabled
                                        class="bg-muted/50"
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
                            {{ processing ? 'Salvando...' : 'Criar Evento' }}
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
