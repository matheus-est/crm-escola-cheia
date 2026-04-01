<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/menu-groups';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Grupo Menu', href: index().url },
    { title: 'Novo Grupo Menu', href: '#' },
];

const form = useForm({
    name: '',
    slug: '',
    icon: '',
    order: 0,
    is_active: true,
});

function generateSlug() {
    if (!form.name) return;
    form.slug = form.name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

function handleSubmit() {
    form.post(store().url, {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('menu_groups.create')" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading :title="$t('menu_groups.create')" />
            </div>

            <div class="rounded-md border">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div class="space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="grid gap-2">
                                <Label for="name">{{
                                    $t('menu_groups.form.name')
                                }}</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :placeholder="
                                        $t('menu_groups.form.name.placeholder')
                                    "
                                    required
                                    @blur="generateSlug"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="slug">{{
                                    $t('menu_groups.form.slug')
                                }}</Label>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    :placeholder="
                                        $t('menu_groups.form.slug.placeholder')
                                    "
                                    required
                                />
                                <InputError :message="form.errors.slug" />
                            </div>

                            <div class="mt-6 grid gap-2">
                                <Label for="icon">{{
                                    $t('menu_groups.form.icon')
                                }}</Label>
                                <Input
                                    id="icon"
                                    v-model="form.icon"
                                    :placeholder="
                                        $t('menu_groups.form.icon.placeholder')
                                    "
                                />
                                <InputError :message="form.errors.icon" />
                                <p class="text-xs text-muted-foreground">
                                    {{ $t('menu_groups.form.icon.help') }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <div class="flex items-center gap-4">
                                    <div class="grid gap-2">
                                        <Label for="order">{{
                                            $t('menu_groups.form.order')
                                        }}</Label>
                                        <Input
                                            id="order"
                                            v-model.number="form.order"
                                            type="number"
                                            min="0"
                                            class="max-w-24"
                                        />
                                        <InputError
                                            :message="form.errors.order"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2 pt-6">
                                        <Switch
                                            id="is_active"
                                            v-model="form.is_active"
                                        />
                                        <Label for="is_active">{{
                                            $t('menu_groups.form.active')
                                        }}</Label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.is_active" />
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            :disabled="form.processing"
                            @click="router.visit(index().url)"
                        >
                            {{ $t('menu_groups.form.cancel') }}
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            {{
                                form.processing
                                    ? $t('menu_groups.form.saving')
                                    : $t('menu_groups.form.submit')
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
