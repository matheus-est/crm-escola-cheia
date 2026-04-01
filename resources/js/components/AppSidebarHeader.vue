<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown } from 'lucide-vue-next';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <header
        class="flex h-12 shrink-0 items-center gap-2 border-b border-border/50 bg-background/95 px-4 shadow-sm backdrop-blur transition-[width,height] ease-linear supports-[backdrop-filter]:bg-background/60"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger
                class="-ml-1 text-muted-foreground hover:text-foreground"
            />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="ml-auto flex items-center gap-1">
            <AppearanceToggle />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        class="flex h-8 items-center gap-2 px-2 text-sm"
                        data-test="header-user-menu-button"
                    >
                        <UserInfo :user="user" />
                        <ChevronsUpDown
                            class="size-3.5 text-muted-foreground"
                        />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="min-w-56 rounded-lg"
                    align="end"
                    :side-offset="8"
                >
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
