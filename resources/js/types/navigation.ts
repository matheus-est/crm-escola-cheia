import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export type BreadcrumbItem = {
    title: string;
    href?: string;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

export type MenuModule = {
    id: number;
    name: string;
    slug: string;
    icon: string;
    url: string;
    menu_group_id: number | null;
    permissions: {
        list: boolean;
        add: boolean;
        edit: boolean;
        view: boolean;
        delete: boolean;
    };
};

export type MenuGroupItem = {
    id: number | null;
    uuid: string | null;
    name: string;
    slug: string;
    icon: string | null;
    order: number;
    items: MenuModule[];
};

export type MenuItemSimple = {
    id: number | null;
    uuid: string | null;
    name: string;
    slug: string;
    icon: string | null;
    url: string | null;
    order: number;
};

export type MenuItemGroup = {
    id: number | null;
    uuid: string | null;
    name: string;
    slug: string;
    icon: string | null;
    order: number;
    items: MenuModule[];
};

export type MenuItem = MenuItemSimple | MenuItemGroup;

export type NavGroup = MenuItemGroup;
