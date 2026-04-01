<script setup lang="ts">
import CharacterCount from '@tiptap/extension-character-count';
import Highlight from '@tiptap/extension-highlight';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import {
    Bold,
    Italic,
    Underline as UnderlineIcon,
    Strikethrough,
    Heading1,
    Heading2,
    Heading3,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    List,
    ListOrdered,
    Quote,
    Minus,
    Highlighter,
    Undo,
    Redo,
    Type,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount } from 'vue';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
        }),
        Underline,
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Highlight.configure({ multicolor: false }),
        CharacterCount,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm dark:prose-invert max-w-none min-h-[320px] px-5 py-4 focus:outline-none',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

const wordCount = computed(() => {
    const text = editor.value?.getText() ?? '';
    return text.trim() ? text.trim().split(/\s+/).length : 0;
});

const charCount = computed(
    () => editor.value?.storage.characterCount.characters() ?? 0,
);

type ToolbarGroup = {
    label: string;
    items: ToolbarItem[];
};

type ToolbarItem = {
    icon: unknown;
    label: string;
    action: () => void;
    isActive?: () => boolean;
    disabled?: () => boolean;
};

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const toolbarGroups = computed<ToolbarGroup[]>(() => [
    {
        label: 'Histórico',
        items: [
            {
                icon: Undo,
                label: 'Desfazer',
                action: () => editor.value?.chain().focus().undo().run(),
                disabled: () => !editor.value?.can().undo(),
            },
            {
                icon: Redo,
                label: 'Refazer',
                action: () => editor.value?.chain().focus().redo().run(),
                disabled: () => !editor.value?.can().redo(),
            },
        ],
    },
    {
        label: 'Títulos',
        items: [
            {
                icon: Heading1,
                label: 'Título grande',
                action: () =>
                    editor.value
                        ?.chain()
                        .focus()
                        .toggleHeading({ level: 1 })
                        .run(),
                isActive: () =>
                    editor.value?.isActive('heading', { level: 1 }) ?? false,
            },
            {
                icon: Heading2,
                label: 'Título médio',
                action: () =>
                    editor.value
                        ?.chain()
                        .focus()
                        .toggleHeading({ level: 2 })
                        .run(),
                isActive: () =>
                    editor.value?.isActive('heading', { level: 2 }) ?? false,
            },
            {
                icon: Heading3,
                label: 'Título pequeno',
                action: () =>
                    editor.value
                        ?.chain()
                        .focus()
                        .toggleHeading({ level: 3 })
                        .run(),
                isActive: () =>
                    editor.value?.isActive('heading', { level: 3 }) ?? false,
            },
            {
                icon: Type,
                label: 'Parágrafo',
                action: () =>
                    editor.value?.chain().focus().setParagraph().run(),
                isActive: () => editor.value?.isActive('paragraph') ?? false,
            },
        ],
    },
    {
        label: 'Formatação',
        items: [
            {
                icon: Bold,
                label: 'Negrito',
                action: () => editor.value?.chain().focus().toggleBold().run(),
                isActive: () => editor.value?.isActive('bold') ?? false,
            },
            {
                icon: Italic,
                label: 'Itálico',
                action: () =>
                    editor.value?.chain().focus().toggleItalic().run(),
                isActive: () => editor.value?.isActive('italic') ?? false,
            },
            {
                icon: UnderlineIcon,
                label: 'Sublinhado',
                action: () =>
                    editor.value?.chain().focus().toggleUnderline().run(),
                isActive: () => editor.value?.isActive('underline') ?? false,
            },
            {
                icon: Strikethrough,
                label: 'Tachado',
                action: () =>
                    editor.value?.chain().focus().toggleStrike().run(),
                isActive: () => editor.value?.isActive('strike') ?? false,
            },
            {
                icon: Highlighter,
                label: 'Destaque',
                action: () =>
                    editor.value?.chain().focus().toggleHighlight().run(),
                isActive: () => editor.value?.isActive('highlight') ?? false,
            },
        ],
    },
    {
        label: 'Alinhamento',
        items: [
            {
                icon: AlignLeft,
                label: 'Alinhar à esquerda',
                action: () =>
                    editor.value?.chain().focus().setTextAlign('left').run(),
                isActive: () =>
                    editor.value?.isActive({ textAlign: 'left' }) ?? false,
            },
            {
                icon: AlignCenter,
                label: 'Centralizar',
                action: () =>
                    editor.value?.chain().focus().setTextAlign('center').run(),
                isActive: () =>
                    editor.value?.isActive({ textAlign: 'center' }) ?? false,
            },
            {
                icon: AlignRight,
                label: 'Alinhar à direita',
                action: () =>
                    editor.value?.chain().focus().setTextAlign('right').run(),
                isActive: () =>
                    editor.value?.isActive({ textAlign: 'right' }) ?? false,
            },
            {
                icon: AlignJustify,
                label: 'Justificar',
                action: () =>
                    editor.value?.chain().focus().setTextAlign('justify').run(),
                isActive: () =>
                    editor.value?.isActive({ textAlign: 'justify' }) ?? false,
            },
        ],
    },
    {
        label: 'Listas e blocos',
        items: [
            {
                icon: List,
                label: 'Lista com marcadores',
                action: () =>
                    editor.value?.chain().focus().toggleBulletList().run(),
                isActive: () => editor.value?.isActive('bulletList') ?? false,
            },
            {
                icon: ListOrdered,
                label: 'Lista numerada',
                action: () =>
                    editor.value?.chain().focus().toggleOrderedList().run(),
                isActive: () => editor.value?.isActive('orderedList') ?? false,
            },
            {
                icon: Quote,
                label: 'Citação',
                action: () =>
                    editor.value?.chain().focus().toggleBlockquote().run(),
                isActive: () => editor.value?.isActive('blockquote') ?? false,
            },
            {
                icon: Minus,
                label: 'Linha divisória',
                action: () =>
                    editor.value?.chain().focus().setHorizontalRule().run(),
            },
        ],
    },
]);
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
        <!-- Toolbar -->
        <div
            class="flex flex-wrap items-center gap-0.5 border-b bg-muted/30 px-2 py-1.5"
        >
            <template v-for="(group, gi) in toolbarGroups" :key="gi">
                <!-- Separador entre grupos -->
                <div v-if="gi > 0" class="mx-1 h-5 w-px bg-border" />

                <button
                    v-for="item in group.items"
                    :key="item.label"
                    type="button"
                    :title="item.label"
                    :disabled="item.disabled?.() ?? false"
                    class="flex h-8 w-8 items-center justify-center rounded-md transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                    :class="
                        item.isActive?.()
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="item.action()"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                </button>
            </template>
        </div>

        <!-- Área de edição -->
        <EditorContent :editor="editor" />

        <!-- Rodapé com contador -->
        <div
            class="flex items-center justify-end gap-4 border-t bg-muted/20 px-4 py-2 text-xs text-muted-foreground"
        >
            <span
                >{{ wordCount }}
                {{ wordCount === 1 ? 'palavra' : 'palavras' }}</span
            >
            <span
                >{{ charCount }}
                {{ charCount === 1 ? 'caractere' : 'caracteres' }}</span
            >
        </div>
    </div>
</template>
