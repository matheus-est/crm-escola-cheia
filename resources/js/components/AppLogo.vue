<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';

const page = usePage();

const appName = computed(() => (page.props.name as string) || 'Sistema');
const logoLight = computed(() => page.props.logo_light as string | null);
const logoDark = computed(() => page.props.logo_dark as string | null);

// Monitora classe .dark no <html>
const isDarkMode = ref(false);

// Função helper para detectar dark mode
const detectDarkMode = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
};

// Inicializar ao montar e setupar MutationObserver
onMounted(() => {
    detectDarkMode();

    const observer = new MutationObserver((mutations) => {
        // Verificar se mutação é no atributo 'class'
        if (mutations.some((m) => m.attributeName === 'class')) {
            detectDarkMode();
        }
    });

    observer.observe(document.documentElement, { attributes: true });

    onBeforeUnmount(() => observer.disconnect());
});

// Adapta logo baseado no modo escuro/claro
const logoUrl = computed(() => {
    const preferredLogo = isDarkMode.value ? logoDark.value : logoLight.value;
    const fallbackLogo = isDarkMode.value ? logoLight.value : logoDark.value;

    const logo = preferredLogo || fallbackLogo;
    return logo ? `/storage/${logo}` : null;
});
</script>

<template>
    <!-- Imagem personalizada -->
    <template v-if="logoUrl">
        <div
            class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-transparent"
        >
            <img :src="logoUrl" :alt="appName" class="size-8 object-contain" />
        </div>
    </template>

    <!-- Fallback: ícone Operis nativo -->
    <template v-else>
        <div
            class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary"
        >
            <img
                src="/images/operis-avatar-blue.png"
                alt="Operis CRM"
                class="size-8 object-contain"
            />
        </div>
    </template>

    <!-- Nome do sistema -->
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">
            {{ appName }}
        </span>
    </div>
</template>
