import { usePage } from '@inertiajs/vue3';
import type { MenuGroupItem, MenuModule } from '@/types/navigation';

type PermissionAction = keyof MenuModule['permissions'];

export function usePermission() {
    const page = usePage<{ menu: MenuGroupItem[] }>();

    function can(moduleSlug: string, action: PermissionAction): boolean {
        const menu = page.props.menu as MenuGroupItem[];
        if (!Array.isArray(menu)) return false;

        for (const group of menu) {
            if ('items' in group && Array.isArray(group.items)) {
                const mod = group.items.find(
                    (item) => item.slug === moduleSlug,
                );
                if (mod) return mod.permissions[action] ?? false;
            } else if ('permissions' in group && group.slug === moduleSlug) {
                return (
                    (group as unknown as MenuModule).permissions[action] ??
                    false
                );
            }
        }
        return false;
    }

    return { can };
}
