<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show } from '@/routes/tenant/opportunities';
import type { Opportunity } from '@/types/crm';

const props = defineProps<{
    opportunity: Opportunity;
}>();
</script>

<template>
    <div
        class="cursor-pointer rounded-lg border bg-card p-3 shadow-sm transition-shadow hover:shadow-md"
    >
        <Link
            :href="show({ opportunity: props.opportunity.uuid }).url"
            class="block space-y-0.5"
        >
            <!-- Line 1: Guardian name (responsável) -->
            <p class="truncate text-sm leading-tight font-medium">
                {{ props.opportunity.guardian?.name ?? '—' }}
            </p>
            <!-- Line 2: Aluno: student name -->
            <p class="truncate text-xs text-muted-foreground">
                Aluno: {{ props.opportunity.student?.name ?? '—' }}
            </p>
            <!-- Line 3: Série: grade - segment -->
            <p class="truncate text-xs text-muted-foreground">
                <template
                    v-if="props.opportunity.grade || props.opportunity.segment"
                >
                    Série:
                    <span v-if="props.opportunity.grade">{{
                        props.opportunity.grade.name
                    }}</span>
                    <span
                        v-if="
                            props.opportunity.grade && props.opportunity.segment
                        "
                    >
                        -
                    </span>
                    <span v-if="props.opportunity.segment">{{
                        props.opportunity.segment.name
                    }}</span>
                </template>
                <template v-else>Série: —</template>
            </p>
            <!-- Blank spacer -->
            <div class="h-2" />
            <!-- Line 5: Responsible user (atendente) -->
            <p class="truncate text-xs text-muted-foreground">
                {{
                    props.opportunity.responsible_user?.name ??
                    'Sem responsável'
                }}
            </p>
        </Link>
    </div>
</template>
