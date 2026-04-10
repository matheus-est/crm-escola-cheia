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
        Ícone Operis: círculo azul + roda de 8 pétalas brancas.
        Tamanho controlado externamente via classes Tailwind (size-*, h-*, w-*).
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 100 100"
        :class="className"
        v-bind="$attrs"
        aria-hidden="true"
    >
        <!-- Fundo: círculo azul Operis -->
        <circle cx="50" cy="50" r="50" fill="#2D3AE0" />

        <!-- Roda de 8 pétalas brancas — cada uma rotacionada 45° em torno do centro -->
        <g transform="rotate(0, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>
        <g transform="rotate(45, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>
        <g transform="rotate(90, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>
        <g transform="rotate(135, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>
        <g transform="rotate(180, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>
        <g transform="rotate(225, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>
        <g transform="rotate(270, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>
        <g transform="rotate(315, 50, 50)">
            <path
                d="M50,50 C47,42 44,34 50,22 C56,34 53,42 50,50 Z"
                fill="white"
            />
        </g>

        <!-- Hub central -->
        <circle cx="50" cy="50" r="7" fill="#2D3AE0" />
    </svg>
</template>
