<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { type Component, computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface Feature {
    icon: Component;
    title: string;
    description: string;
}

interface Props {
    headline?: string;
    subheadline?: string;
    features?: Feature[];
}

const props = withDefaults(defineProps<Props>(), {
    headline: 'Bem-vindo de volta.',
    subheadline: '',
    features: () => [],
});

const page = usePage();

const logoLight = computed(() => page.props.logo_light as string | null);
const logoDark = computed(() => page.props.logo_dark as string | null);
const appName = computed(
    () => (page.props.name as string | undefined) ?? 'Sistema',
);

// Left panel is always dark bg — prefer dark logo for contrast
const leftPanelLogoUrl = computed(() => {
    const logo = logoDark.value || logoLight.value;
    return logo ? `/storage/${logo}` : null;
});

// Right panel adapts to theme
const isDarkMode = ref(false);
const detectDarkMode = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
};

let observer: MutationObserver | null = null;

onMounted(() => {
    detectDarkMode();
    observer = new MutationObserver((mutations) => {
        if (mutations.some((m) => m.attributeName === 'class')) {
            detectDarkMode();
        }
    });
    observer.observe(document.documentElement, { attributes: true });
});

onBeforeUnmount(() => {
    observer?.disconnect();
});

const mobileLogoUrl = computed(() => {
    const preferredLogo = isDarkMode.value ? logoDark.value : logoLight.value;
    const fallbackLogo = isDarkMode.value ? logoLight.value : logoDark.value;
    const logo = preferredLogo || fallbackLogo;
    return logo ? `/storage/${logo}` : null;
});
</script>

<template>
    <div class="flex min-h-screen">
        <!-- Left panel -->
        <div
            class="relative hidden flex-col justify-between overflow-hidden px-14 py-12 lg:flex lg:w-[52%]"
            style="
                background-image: url('/images/auth-bg.jpg');
                background-size: cover;
                background-position: center;
            "
        >
            <!-- Dark overlay for text legibility -->
            <div
                class="pointer-events-none absolute inset-0 bg-slate-950/65"
            ></div>

            <!-- Background grid pattern -->
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.04]"
                style="
                    background-image:
                        linear-gradient(
                            rgba(255, 255, 255, 0.6) 1px,
                            transparent 1px
                        ),
                        linear-gradient(
                            90deg,
                            rgba(255, 255, 255, 0.6) 1px,
                            transparent 1px
                        );
                    background-size: 40px 40px;
                "
            ></div>

            <!-- Glow decorativo -->
            <div
                class="pointer-events-none absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-blue-500/15 blur-[120px]"
            ></div>
            <div
                class="pointer-events-none absolute -right-20 -bottom-20 h-[400px] w-[400px] rounded-full bg-indigo-500/10 blur-[100px]"
            ></div>

            <!-- Logo / brand -->
            <div class="relative z-10 flex items-center gap-3">
                <template v-if="leftPanelLogoUrl">
                    <img
                        :src="leftPanelLogoUrl"
                        alt="Logo"
                        class="h-10 w-auto object-contain"
                    />
                </template>
                <template v-else>
                    <img
                        src="/images/operis-avatar-blue.png"
                        alt="Operis CRM"
                        class="h-10 w-auto object-contain"
                    />
                </template>
                <span class="text-xl font-semibold tracking-tight text-white">
                    {{ appName }}
                </span>
            </div>

            <!-- Center content -->
            <div class="relative z-10 space-y-10">
                <div class="space-y-3">
                    <h1
                        class="font-[EurostileLTProUnicode-Bold] text-4xl leading-tight font-bold tracking-tight text-white"
                    >
                        {{ props.headline }}
                    </h1>
                    <p
                        v-if="props.subheadline"
                        class="max-w-sm text-sm leading-relaxed text-zinc-400"
                    >
                        {{ props.subheadline }}
                    </p>
                </div>

                <!-- Feature list -->
                <div v-if="props.features.length > 0" class="space-y-4">
                    <div
                        v-for="feature in props.features"
                        :key="feature.title"
                        class="flex items-start gap-3.5"
                    >
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-blue-400/10 ring-1 ring-blue-400/20"
                        >
                            <component
                                :is="feature.icon"
                                class="h-4 w-4 text-blue-300"
                            />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">
                                {{ feature.title }}
                            </p>
                            <p class="text-xs text-zinc-500">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10">
                <p class="text-xs text-zinc-600">
                    &copy; {{ new Date().getFullYear() }} {{ appName }}. Todos
                    os direitos reservados.
                </p>
            </div>
        </div>

        <!-- Right panel — formulário -->
        <div
            class="flex flex-1 flex-col items-center justify-center bg-background px-6 py-12 lg:px-16"
        >
            <!-- Mobile logo -->
            <div class="mb-8 flex items-center gap-2 lg:hidden">
                <template v-if="mobileLogoUrl">
                    <img
                        :src="mobileLogoUrl"
                        alt="Logo"
                        class="h-8 w-auto object-contain"
                    />
                </template>
                <template v-else>
                    <img
                        src="/images/operis-avatar-blue.png"
                        alt="Operis CRM"
                        class="h-8 w-auto object-contain"
                    />
                </template>
                <span class="text-lg font-semibold">{{ appName }}</span>
            </div>

            <slot />
        </div>
    </div>
</template>
