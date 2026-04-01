<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        Ícone Escola Cheia: círculo escuro + anel laranja/âmbar + livro aberto + lâmpada.
        Tamanho controlado externamente via classes Tailwind (size-*, h-*, w-*).
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 100 100"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <defs>
            <linearGradient
                id="ec-ring-grad"
                x1="0%"
                y1="0%"
                x2="100%"
                y2="100%"
            >
                <stop offset="0%" stop-color="#F5C610" />
                <stop offset="60%" stop-color="#F0A020" />
                <stop offset="100%" stop-color="#E87C1A" />
            </linearGradient>
            <clipPath id="ec-circle-clip">
                <circle cx="50" cy="50" r="50" />
            </clipPath>
        </defs>

        <!-- Fundo circular grafite -->
        <circle cx="50" cy="50" r="50" fill="#1A1A1A" />

        <!-- Anel gradiente âmbar → laranja (arco espesso com gap nas pontas) -->
        <circle
            cx="50"
            cy="50"
            r="43"
            fill="none"
            stroke="url(#ec-ring-grad)"
            stroke-width="10"
            stroke-linecap="round"
            stroke-dasharray="220 50"
            stroke-dashoffset="-12"
        />

        <!-- Livro aberto (duas páginas abertas em V) -->
        <!-- Página esquerda -->
        <path
            d="M50 62 L30 55 L30 36 L50 44 Z"
            fill="none"
            stroke="#F5C610"
            stroke-width="2.8"
            stroke-linejoin="round"
            stroke-linecap="round"
        />
        <!-- Página direita -->
        <path
            d="M50 62 L70 55 L70 36 L50 44 Z"
            fill="none"
            stroke="#F5C610"
            stroke-width="2.8"
            stroke-linejoin="round"
            stroke-linecap="round"
        />
        <!-- Lombada central -->
        <line
            x1="50"
            y1="44"
            x2="50"
            y2="62"
            stroke="#F5C610"
            stroke-width="2.2"
            stroke-linecap="round"
        />

        <!-- Lâmpada -->
        <circle
            cx="50"
            cy="26"
            r="7"
            fill="none"
            stroke="#F5C610"
            stroke-width="2.5"
        />
        <!-- Base da lâmpada -->
        <line
            x1="46.5"
            y1="33"
            x2="53.5"
            y2="33"
            stroke="#F5C610"
            stroke-width="2.2"
            stroke-linecap="round"
        />
        <line
            x1="47.5"
            y1="36.5"
            x2="52.5"
            y2="36.5"
            stroke="#F5C610"
            stroke-width="1.8"
            stroke-linecap="round"
        />

        <!-- Raios de luz -->
        <line
            x1="50"
            y1="15"
            x2="50"
            y2="18"
            stroke="#F5C610"
            stroke-width="2"
            stroke-linecap="round"
        />
        <line
            x1="59.5"
            y1="17.5"
            x2="57.5"
            y2="20"
            stroke="#F5C610"
            stroke-width="2"
            stroke-linecap="round"
        />
        <line
            x1="40.5"
            y1="17.5"
            x2="42.5"
            y2="20"
            stroke="#F5C610"
            stroke-width="2"
            stroke-linecap="round"
        />
        <line
            x1="64.5"
            y1="26"
            x2="61"
            y2="26"
            stroke="#F5C610"
            stroke-width="2"
            stroke-linecap="round"
        />
        <line
            x1="35.5"
            y1="26"
            x2="39"
            y2="26"
            stroke="#F5C610"
            stroke-width="2"
            stroke-linecap="round"
        />
    </svg>
</template>
