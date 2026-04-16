<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Filter,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    entryColorClass,
    getMonthGrid,
    getWeekDays,
    toApiDateTime,
} from '@/lib/calendarEvent';
import {
    entries as calendarEntries,
    index as calendarIndex,
} from '@/routes/tenant/calendar/index';
import { edit as editEvent } from '@/routes/tenant/events/index';
import { show as showOpportunity } from '@/routes/tenant/opportunities/index';
import type { BreadcrumbItem } from '@/types';
import type { CalendarEntry } from '@/types/crm';

interface CalendarFilters {
    date_from?: string;
    date_to?: string;
    assigned_user_uuid?: string;
}

const props = defineProps<{
    entries: CalendarEntry[];
    filters: CalendarFilters;
    users: { uuid: string; name: string }[];
    currentUser: string;
}>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Agenda', href: '#' }];

// — State —
const view = ref<'month' | 'week'>('month');

// Initialize currentDate from props.filters.date_from if available
function initCurrentDate(): Date {
    if (props.filters.date_from) {
        const d = new Date(props.filters.date_from);
        if (!isNaN(d.getTime())) return d;
    }
    return new Date();
}

const currentDate = ref<Date>(initCurrentDate());
const localEntries = ref<CalendarEntry[]>(props.entries);
const isLoading = ref<boolean>(false);
const localFilters = ref<CalendarFilters>({
    date_from: props.filters.date_from ?? undefined,
    date_to: props.filters.date_to ?? undefined,
    assigned_user_uuid: props.filters.assigned_user_uuid ?? undefined,
});

const hasActiveFilter = computed(() => !!localFilters.value.assigned_user_uuid);
const filterCount = computed(() =>
    localFilters.value.assigned_user_uuid ? 1 : 0,
);

// Sync localEntries when props.entries changes (after router.get navigation)
watch(
    () => props.entries,
    (newEntries) => {
        localEntries.value = newEntries;
    },
);

// — Date range helpers —
function getDateRange(): { dateFrom: Date; dateTo: Date } {
    if (view.value === 'month') {
        const y = currentDate.value.getFullYear();
        const m = currentDate.value.getMonth();
        const dateFrom = new Date(y, m, 1, 0, 0, 0);
        const dateTo = new Date(y, m + 1, 0, 23, 59, 59);
        return { dateFrom, dateTo };
    } else {
        const days = getWeekDays(currentDate.value);
        const dateFrom = new Date(days[0]);
        dateFrom.setHours(0, 0, 0);
        const dateTo = new Date(days[6]);
        dateTo.setHours(23, 59, 59);
        return { dateFrom, dateTo };
    }
}

// — Fetch entries via JSON (same pattern as useNotifications) —
async function fetchEntries(): Promise<void> {
    isLoading.value = true;
    try {
        const { dateFrom, dateTo } = getDateRange();
        const csrfToken =
            (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement | null
            )?.content ?? '';

        const queryOptions: Record<string, string> = {
            date_from: toApiDateTime(dateFrom),
            date_to: toApiDateTime(dateTo),
        };
        if (localFilters.value.assigned_user_uuid) {
            queryOptions['assigned_user_uuid'] =
                localFilters.value.assigned_user_uuid;
        }

        const response = await fetch(calendarEntries.url(queryOptions), {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        });
        localEntries.value = (await response.json()) as CalendarEntry[];
    } finally {
        isLoading.value = false;
    }
}

// — Navigation (persists date range in session via router.get) —
function navigatePeriod(d: Date): void {
    const { dateFrom, dateTo } = (() => {
        if (view.value === 'month') {
            const y = d.getFullYear();
            const m = d.getMonth();
            return {
                dateFrom: new Date(y, m, 1, 0, 0, 0),
                dateTo: new Date(y, m + 1, 0, 23, 59, 59),
            };
        } else {
            const days = getWeekDays(d);
            const df = new Date(days[0]);
            df.setHours(0, 0, 0);
            const dt = new Date(days[6]);
            dt.setHours(23, 59, 59);
            return { dateFrom: df, dateTo: dt };
        }
    })();

    const params: Record<string, string> = {
        date_from: toApiDateTime(dateFrom),
        date_to: toApiDateTime(dateTo),
    };
    if (localFilters.value.assigned_user_uuid) {
        params['assigned_user_uuid'] = localFilters.value.assigned_user_uuid;
    }

    router.get(calendarIndex().url, params, { preserveUrl: true });
}

function prevPeriod(): void {
    const d = new Date(currentDate.value);
    if (view.value === 'month') {
        d.setMonth(d.getMonth() - 1);
    } else {
        d.setDate(d.getDate() - 7);
    }
    currentDate.value = d;
    navigatePeriod(d);
}

function nextPeriod(): void {
    const d = new Date(currentDate.value);
    if (view.value === 'month') {
        d.setMonth(d.getMonth() + 1);
    } else {
        d.setDate(d.getDate() + 7);
    }
    currentDate.value = d;
    navigatePeriod(d);
}

function goToToday(): void {
    currentDate.value = new Date();
    navigatePeriod(currentDate.value);
}

function switchView(v: 'month' | 'week'): void {
    view.value = v;
    void fetchEntries();
}

// — Filter —
function updateFilterUser(value: string): void {
    localFilters.value.assigned_user_uuid = value === 'all' ? undefined : value;
}

function applyFilters(): void {
    void fetchEntries();
}

function clearFilters(): void {
    localFilters.value.assigned_user_uuid = undefined;
    void fetchEntries();
}

// — Period label —
const periodLabel = computed<string>(() => {
    const d = currentDate.value;
    if (view.value === 'month') {
        return new Intl.DateTimeFormat('pt-BR', {
            month: 'long',
            year: 'numeric',
        }).format(d);
    }
    const days = getWeekDays(d);
    const start = new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
    }).format(days[0]);
    const end = new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(days[6]);
    return `${start} – ${end}`;
});

// — Month grid —
const monthDays = computed<Date[]>(() =>
    getMonthGrid(currentDate.value.getFullYear(), currentDate.value.getMonth()),
);

const weekDayHeaders = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

function isSameDay(a: Date, b: Date): boolean {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

function isCurrentMonth(d: Date): boolean {
    return d.getMonth() === currentDate.value.getMonth();
}

function isToday(d: Date): boolean {
    return isSameDay(d, new Date());
}

function entriesForDay(day: Date): CalendarEntry[] {
    return localEntries.value.filter((e) => {
        const eDate = new Date(e.date);
        return isSameDay(eDate, day);
    });
}

// — Week view —
const weekDays = computed<Date[]>(() => getWeekDays(currentDate.value));
const weekHours = Array.from({ length: 15 }, (_, i) => i + 7); // 07 to 21

function entriesForSlot(day: Date, hour: number): CalendarEntry[] {
    return localEntries.value.filter((e) => {
        const eDate = new Date(e.date);
        // Entries with no meaningful time (midnight) default to 08:00
        const effectiveHour =
            eDate.getHours() === 0 && eDate.getMinutes() === 0
                ? 8
                : eDate.getHours();
        return isSameDay(eDate, day) && effectiveHour === hour;
    });
}

// — Navigation to entry —
function navigateToEntry(entry: CalendarEntry): void {
    if (entry.type === 'task') {
        router.visit(showOpportunity({ opportunity: entry.link_uuid }).url);
    } else {
        router.visit(editEvent({ event: entry.link_uuid }).url);
    }
}

function formatHour(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

function formatDayHeader(d: Date): string {
    return new Intl.DateTimeFormat('pt-BR', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
    }).format(d);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Agenda" />

        <div class="space-y-4">
            <!-- Page header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Heading title="Agenda" />

                <div class="flex items-center gap-2">
                    <!-- View toggle -->
                    <div class="flex rounded-lg border bg-background">
                        <button
                            type="button"
                            class="rounded-l-lg px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="
                                view === 'month'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted/50'
                            "
                            @click="switchView('month')"
                        >
                            Mês
                        </button>
                        <button
                            type="button"
                            class="rounded-r-lg px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="
                                view === 'week'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:bg-muted/50'
                            "
                            @click="switchView('week')"
                        >
                            Semana
                        </button>
                    </div>

                    <!-- Navigation -->
                    <Button variant="outline" size="sm" @click="prevPeriod">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button variant="outline" size="sm" @click="goToToday">
                        Hoje
                    </Button>
                    <Button variant="outline" size="sm" @click="nextPeriod">
                        <ChevronRight class="h-4 w-4" />
                    </Button>

                    <!-- Period label -->
                    <span
                        class="min-w-[160px] text-center text-sm font-semibold text-foreground capitalize"
                    >
                        {{ periodLabel }}
                    </span>

                    <!-- Filter accordion -->
                    <Accordion
                        type="single"
                        collapsible
                        class="w-72"
                        defaultValue="closed"
                    >
                        <AccordionItem value="filter" class="border-none">
                            <AccordionTrigger
                                class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors hover:no-underline"
                                :class="
                                    hasActiveFilter
                                        ? 'border-primary/40 bg-primary/5 text-primary dark:bg-primary/10'
                                        : 'border-border bg-background hover:bg-muted/50'
                                "
                            >
                                <Filter class="h-4 w-4 shrink-0" />
                                <span>Filtrar</span>
                                <span
                                    v-if="hasActiveFilter"
                                    class="ml-auto rounded-full bg-primary px-1.5 py-0.5 text-xs text-primary-foreground"
                                >
                                    {{ filterCount }}
                                </span>
                            </AccordionTrigger>

                            <AccordionContent class="pt-2">
                                <div
                                    class="rounded-lg border bg-card p-4 shadow-sm"
                                >
                                    <form
                                        class="space-y-4"
                                        @submit.prevent="applyFilters"
                                    >
                                        <div
                                            v-if="props.users.length > 0"
                                            class="space-y-1.5"
                                        >
                                            <label
                                                class="text-xs font-medium text-muted-foreground"
                                                >Responsável</label
                                            >
                                            <Select
                                                :default-value="
                                                    localFilters.assigned_user_uuid ||
                                                    'all'
                                                "
                                                @update:model-value="
                                                    updateFilterUser
                                                "
                                            >
                                                <SelectTrigger class="h-8">
                                                    <SelectValue
                                                        placeholder="Todos"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all"
                                                        >Todos</SelectItem
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
                                        </div>

                                        <div
                                            class="flex items-center justify-between gap-2 pt-1"
                                        >
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                type="button"
                                                class="h-8 text-muted-foreground"
                                                @click="clearFilters"
                                            >
                                                Limpar filtros
                                            </Button>
                                            <Button
                                                type="submit"
                                                size="sm"
                                                class="h-8"
                                            >
                                                Aplicar
                                            </Button>
                                        </div>
                                    </form>
                                </div>
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>
            </div>

            <!-- Loading indicator -->
            <div
                v-if="isLoading"
                class="flex items-center justify-center py-8 text-sm text-muted-foreground"
            >
                <span class="animate-pulse">Carregando...</span>
            </div>

            <!-- Month View -->
            <div
                v-if="view === 'month' && !isLoading"
                class="rounded-lg border bg-card shadow-sm"
            >
                <!-- Day headers -->
                <div class="grid grid-cols-7 border-b">
                    <div
                        v-for="header in weekDayHeaders"
                        :key="header"
                        class="px-2 py-2 text-center text-xs font-medium text-muted-foreground"
                    >
                        {{ header }}
                    </div>
                </div>

                <!-- Calendar grid -->
                <div class="grid grid-cols-7">
                    <div
                        v-for="(day, idx) in monthDays"
                        :key="idx"
                        class="min-h-[100px] border-r border-b p-1.5 last:border-r-0"
                        :class="[
                            !isCurrentMonth(day) ? 'bg-muted/20' : '',
                            isToday(day)
                                ? 'bg-primary/5 ring-1 ring-primary/20 ring-inset'
                                : '',
                        ]"
                    >
                        <!-- Day number -->
                        <div class="mb-1 flex justify-end">
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium"
                                :class="[
                                    isToday(day)
                                        ? 'bg-primary text-primary-foreground'
                                        : isCurrentMonth(day)
                                          ? 'text-foreground'
                                          : 'text-muted-foreground/50',
                                ]"
                            >
                                {{ day.getDate() }}
                            </span>
                        </div>

                        <!-- Entry pills (max 3) -->
                        <div class="space-y-0.5">
                            <button
                                v-for="entry in entriesForDay(day).slice(0, 3)"
                                :key="entry.uuid"
                                type="button"
                                class="w-full truncate rounded border-l-2 px-1.5 py-0.5 text-left text-xs transition-opacity hover:opacity-80"
                                :class="entryColorClass(entry.sub_type)"
                                :title="entry.title"
                                @click="navigateToEntry(entry)"
                            >
                                {{ entry.title }}
                            </button>

                            <!-- +N mais -->
                            <span
                                v-if="entriesForDay(day).length > 3"
                                class="block px-1 text-xs text-muted-foreground"
                            >
                                +{{ entriesForDay(day).length - 3 }} mais
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Week View -->
            <div
                v-if="view === 'week' && !isLoading"
                class="rounded-lg border bg-card shadow-sm"
            >
                <!-- Day headers -->
                <div class="grid grid-cols-8 border-b">
                    <div
                        class="px-2 py-2 text-center text-xs font-medium text-muted-foreground"
                    >
                        Hora
                    </div>
                    <div
                        v-for="day in weekDays"
                        :key="day.toISOString()"
                        class="px-2 py-2 text-center text-xs font-medium"
                        :class="
                            isToday(day)
                                ? 'text-primary'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ formatDayHeader(day) }}
                    </div>
                </div>

                <!-- Hour rows -->
                <div class="divide-y overflow-auto" style="max-height: 640px">
                    <div
                        v-for="hour in weekHours"
                        :key="hour"
                        class="grid grid-cols-8"
                    >
                        <!-- Time label -->
                        <div
                            class="flex items-start justify-center px-2 py-1 text-xs text-muted-foreground"
                        >
                            {{ formatHour(hour) }}
                        </div>

                        <!-- Day cells -->
                        <div
                            v-for="day in weekDays"
                            :key="day.toISOString()"
                            class="min-h-[48px] border-l p-0.5"
                            :class="isToday(day) ? 'bg-primary/5' : ''"
                        >
                            <button
                                v-for="entry in entriesForSlot(day, hour)"
                                :key="entry.uuid"
                                type="button"
                                class="mb-0.5 w-full truncate rounded border-l-2 px-1.5 py-0.5 text-left text-xs transition-opacity hover:opacity-80"
                                :class="entryColorClass(entry.sub_type)"
                                :title="entry.title"
                                @click="navigateToEntry(entry)"
                            >
                                {{ entry.title }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div
                class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
            >
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-blue-200 bg-blue-100"
                    ></span>
                    Agendamento
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-amber-200 bg-amber-100"
                    ></span>
                    Lembrete Agenda
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-teal-200 bg-teal-100"
                    ></span>
                    Lembrete Evento
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-purple-200 bg-purple-100"
                    ></span>
                    Evento
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-rose-200 bg-rose-100"
                    ></span>
                    Retorno de Ligação
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-indigo-200 bg-indigo-100"
                    ></span>
                    Double Check
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-green-200 bg-green-100"
                    ></span>
                    Provável Matrícula
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-purple-200 bg-purple-100"
                    ></span>
                    Reagendamento de Evento
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-3 w-3 rounded border-l-2 border-violet-200 bg-violet-100"
                    ></span>
                    Double Check Evento
                </div>
            </div>

            <!-- Empty state (when not loading and no entries) -->
            <div
                v-if="!isLoading && localEntries.length === 0"
                class="flex flex-col items-center justify-center rounded-lg border bg-card py-16 text-center"
            >
                <div class="mb-4 rounded-full bg-muted/50 p-4">
                    <CalendarDays class="h-8 w-8 text-muted-foreground/50" />
                </div>
                <p class="text-sm font-medium text-foreground">
                    Nenhum item neste período
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        hasActiveFilter
                            ? 'Tente remover os filtros aplicados.'
                            : 'Não há tarefas ou eventos agendados.'
                    }}
                </p>
                <Button
                    v-if="hasActiveFilter"
                    variant="outline"
                    class="mt-4"
                    @click="clearFilters"
                >
                    Limpar Filtros
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
