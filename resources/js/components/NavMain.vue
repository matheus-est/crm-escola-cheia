<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { type MenuModule, type MenuItem, type MenuItemGroup } from '@/types';

const props = defineProps<{
    groups?: MenuItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

const openGroups = ref<Set<string>>(new Set());

const isGroup = (item: MenuItem): item is MenuItemGroup => {
    return 'items' in item && Array.isArray((item as MenuItemGroup).items);
};

const menuItems = computed(() => props.groups || []);

const toggleGroup = (slug: string) => {
    if (openGroups.value.has(slug)) {
        openGroups.value.delete(slug);
    } else {
        openGroups.value.add(slug);
    }
};

const isGroupOpen = (slug: string) => openGroups.value.has(slug);

const groupHasActiveItem = (group: MenuItemGroup) => {
    return group.items.some((item: MenuModule) => isCurrentUrl(item.url));
};

const initializeOpenGroups = () => {
    menuItems.value.forEach((item) => {
        if (isGroup(item) && groupHasActiveItem(item)) {
            openGroups.value.add(item.slug);
        }
    });
};

initializeOpenGroups();
</script>

<template>
    <SidebarGroup class="px-2 py-2">
        <SidebarMenu class="gap-0.5">
            <template v-for="item in menuItems" :key="item.slug">
                <template v-if="isGroup(item)">
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            class="h-9 cursor-pointer rounded-md text-sm font-medium"
                            :tooltip="item.name"
                            @click="toggleGroup(item.slug)"
                        >
                            <component
                                :is="item.icon ? item.icon : ChevronDown"
                                class="size-4"
                            />
                            <span>{{ item.name }}</span>
                            <component
                                :is="
                                    isGroupOpen(item.slug)
                                        ? ChevronDown
                                        : ChevronRight
                                "
                                class="ml-auto size-4"
                            />
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 max-h-0"
                        enter-to-class="opacity-100 max-h-[500px]"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 max-h-[500px]"
                        leave-to-class="opacity-0 max-h-0"
                    >
                        <SidebarMenuSub
                            v-if="isGroupOpen(item.slug)"
                            class="pl-2"
                        >
                            <SidebarMenuSubItem
                                v-for="subItem in item.items"
                                :key="subItem.slug"
                            >
                                <SidebarMenuSubButton
                                    :as-child
                                    :is-active="isCurrentUrl(subItem.url)"
                                    class="h-8 rounded-md text-sm font-normal"
                                >
                                    <Link :href="subItem.url">
                                        <span>{{ subItem.name }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </Transition>
                </template>

                <template v-else>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(item.url)"
                            :tooltip="item.name"
                            class="h-9 rounded-md text-sm font-medium"
                        >
                            <Link :href="item.url">
                                <component :is="item.icon" class="size-4" />
                                <span>{{ item.name }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </template>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
