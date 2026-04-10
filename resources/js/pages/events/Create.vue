<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, DoorOpen, Info } from 'lucide-vue-next';
import { type Component, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RoomFormDialog from '@/components/RoomFormDialog.vue';
import { Button } from '@/components/ui/button';
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
import type { EventType, Grade, Room } from '@/types/crm';

const props = defineProps<{
    grades: Grade[];
    rooms: Room[];
    school_name: string;
    event_types: EventType[];
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

// 🔥 estado simples
const hasNoDate = ref(false);
const eventDate = ref('');

function handleHasNoDateChange(val: boolean | 'indeterminate'): void {
    const checked = val === true;

    hasNoDate.value = checked;

    if (checked) {
        eventDate.value = '';
    }
}

// Dialog
const showRoomDialog = ref(false);

function handleRoomCreated(): void {
    router.reload({ preserveUrl: true });
}

function handleSuccess(): void {
    toast.success('Evento criado com sucesso.');
}

function handleError(): void {
    toast.error('Erro ao criar evento.');
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
                        <!-- Tabs -->
                        <div class="flex border-b">
                            <button
                                v-for="tab in tabs"
                                :key="tab.value"
                                type="button"
                                class="px-4 py-3 text-sm font-medium"
                                :class="
                                    activeTab === tab.value
                                        ? 'border-b-2 border-primary text-primary'
                                        : 'text-muted-foreground'
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

                        <!-- SOBRE -->
                        <div
                            v-show="activeTab === 'sobre'"
                            class="space-y-6 p-6"
                        >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="title">
                                        Título do Evento
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input id="title" name="title" required />
                                    <InputError :message="errors.title" />
                                </div>

                                <div class="space-y-2">
                                    <Label>
                                        Data do Evento
                                        <span
                                            v-if="!hasNoDate"
                                            class="text-destructive"
                                            >*</span
                                        >
                                    </Label>

                                    <input
                                        id="event_date"
                                        type="datetime-local"
                                        name="event_date"
                                        v-model="eventDate"
                                        :disabled="hasNoDate"
                                        class="border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] md:text-sm"
                                    />

                                    <InputError :message="errors.event_date" />

                                    <div class="flex items-center gap-2 pt-1">
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
                                            :model-value="hasNoDate"
                                            @update:modelValue="
                                                handleHasNoDateChange
                                            "
                                        />

                                        <Label>
                                            Este evento não possui Data
                                        </Label>
                                    </div>
                                </div>
                            </div>

                            <!-- restante intacto -->
                            <div class="grid grid-cols-6 gap-4">
                                <div class="col-span-1">
                                    <Label>Unidade</Label>
                                    <Input
                                        :default-value="props.school_name"
                                        readonly
                                        class="cursor-not-allowed bg-muted"
                                    />
                                </div>

                                <div class="col-span-2">
                                    <Label>Série</Label>
                                    <Select name="grade_uuid">
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Selecione..."
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="g in props.grades"
                                                :key="g.uuid"
                                                :value="g.uuid"
                                            >
                                                {{ g.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="col-span-1">
                                    <Label>Nº Máximo</Label>
                                    <Input type="number" name="max_capacity" />
                                </div>

                                <div class="col-span-2">
                                    <Label>Tipo</Label>
                                    <Select name="event_type_uuid">
                                        <SelectTrigger>
                                            <SelectValue
                                                placeholder="Selecione..."
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
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between border-t px-6 py-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="() => router.visit(index().url)"
                        >
                            Cancelar
                        </Button>

                        <Button type="submit" :disabled="processing">
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
