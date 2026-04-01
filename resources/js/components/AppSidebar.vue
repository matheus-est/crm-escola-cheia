<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import * as LucideIcons from 'lucide-vue-next';
import { computed } from 'vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type MenuModule } from '@/types';
import AppLogo from './AppLogo.vue';

interface MenuItem {
    id: number | null;
    uuid: string | null;
    name: string;
    slug: string;
    icon: string | null;
    url: string | null;
    order: number;
    items?: MenuModule[];
}

const page = usePage();

const menuData = computed<MenuItem[]>(
    () => (page.props.menu as MenuItem[]) ?? [],
);

const getIcon = (iconName: string | null) =>
    (LucideIcons as Record<string, any>)[iconName ?? ''] ??
    LucideIcons.LayoutGrid;

const mainNavItems = computed(() =>
    menuData.value.map((item) => ({
        ...item,
        icon: getIcon(item.icon),
    })),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="floating">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="mainNavItems" />
        </SidebarContent>
    </Sidebar>
</template>
