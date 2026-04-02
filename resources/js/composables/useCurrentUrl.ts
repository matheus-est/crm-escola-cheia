import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, DeepReadonly } from 'vue';
import { computed, readonly } from 'vue';
import { toUrl } from '@/lib/utils';

export type UseCurrentUrlReturn = {
    currentUrl: DeepReadonly<ComputedRef<string>>;
    isCurrentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) => boolean;
    whenCurrentUrl: <T, F = null>(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: T,
        ifFalse?: F,
    ) => T | F;
};

export function useCurrentUrl(): UseCurrentUrlReturn {
    // ✅ Moved inside the function so it's called within setup() context
    const page = usePage();
    const currentUrlReactive = computed(
        () => new URL(page.url, window?.location.origin).pathname,
    );

    function isCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) {
        const urlToCompare = currentUrl ?? currentUrlReactive.value;
        const urlString = toUrl(urlToCheck);

        // ✅ Guard against toUrl() returning undefined/null
        if (!urlString) {
            return false;
        }

        if (!urlString.startsWith('http')) {
            return (
                urlString === urlToCompare ||
                urlToCompare.startsWith(urlString + '/')
            );
        }

        try {
            const absoluteUrl = new URL(urlString);

            return (
                absoluteUrl.pathname === urlToCompare ||
                urlToCompare.startsWith(absoluteUrl.pathname + '/')
            );
        } catch {
            return false;
        }
    }

    function whenCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: any,
        ifFalse: any = null,
    ) {
        return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        isCurrentUrl,
        whenCurrentUrl,
    };
}
