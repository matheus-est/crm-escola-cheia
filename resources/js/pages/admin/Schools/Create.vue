<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCepLookup } from '@/composables/useCepLookup';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/admin/schools';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
    { title: 'Nova Escola', href: '#' },
];

const toast = useToast();

const {
    isLoading: isLoadingCep,
    error: cepError,
    lookup: lookupCep,
} = useCepLookup();

// Local state for address auto-fill via CEP lookup
const cepValue = ref('');
const logradouroValue = ref('');
const bairroValue = ref('');
const cidadeValue = ref('');
const estadoValue = ref('');

let isAutoFillingCep = false;

watch(cepValue, (newCep) => {
    if (isAutoFillingCep) return;
    lookupCep(newCep, (data) => {
        isAutoFillingCep = true;
        logradouroValue.value = data.logradouro;
        bairroValue.value = data.bairro;
        cidadeValue.value = data.cidade;
        estadoValue.value = data.estado;
        setTimeout(() => (isAutoFillingCep = false), 100);
    });
});

const handleSuccess = () => {
    toast.success('Escola criada com sucesso.');
    router.visit(index().url);
};

const handleError = () => {
    toast.error('Erro ao criar escola. Verifique os campos.');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nova Escola" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading
                    title="Nova Escola"
                    description="Cadastre uma nova escola e sua unidade matriz no sistema."
                    class="pt-8"
                />
            </div>

            <div class="rounded-md border">
                <Form
                    method="post"
                    :action="store().url"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                    @success="handleSuccess"
                    @error="handleError"
                >
                    <div class="space-y-6 p-6">
                        <!-- Dados Gerais -->
                        <div
                            class="space-y-4 rounded-lg border bg-card p-6 shadow-sm"
                        >
                            <h3 class="text-lg font-medium">Dados Gerais</h3>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="cnpj">CNPJ</Label>
                                    <Input
                                        id="cnpj"
                                        name="cnpj"
                                        placeholder="00.000.000/0000-00"
                                    />
                                    <InputError :message="errors.cnpj" />
                                </div>

                                <div class="space-y-2">
                                    <Label for="razao_social"
                                        >Razão Social</Label
                                    >
                                    <Input
                                        id="razao_social"
                                        name="razao_social"
                                        placeholder="Escola Exemplo"
                                    />
                                    <InputError
                                        :message="errors.razao_social"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Unidade Matriz -->
                        <div class="border-t pt-4">
                            <h3 class="mb-4 text-lg font-medium">
                                Endereço da Unidade Matriz
                            </h3>

                            <div class="grid gap-4 sm:grid-cols-6">
                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="cep">CEP</Label>
                                    <div class="relative">
                                        <Input
                                            id="cep"
                                            name="unit.cep"
                                            placeholder="00000-000"
                                            v-model="cepValue"
                                        />
                                        <span
                                            v-if="isLoadingCep"
                                            class="absolute top-2.5 right-3 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                                        ></span>
                                    </div>
                                    <InputError :message="errors['unit.cep']" />
                                    <p
                                        v-if="cepError"
                                        class="text-xs text-destructive"
                                    >
                                        {{ cepError }}
                                    </p>
                                </div>

                                <div class="space-y-2 sm:col-span-4">
                                    <Label for="logradouro">Logradouro</Label>
                                    <Input
                                        id="logradouro"
                                        name="unit.logradouro"
                                        placeholder="Rua, Avenida, etc."
                                        v-model="logradouroValue"
                                    />
                                    <InputError
                                        :message="errors['unit.logradouro']"
                                    />
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="numero">Número</Label>
                                    <Input
                                        id="numero"
                                        name="unit.numero"
                                        placeholder="123"
                                    />
                                    <InputError
                                        :message="errors['unit.numero']"
                                    />
                                </div>

                                <div class="space-y-2 sm:col-span-4">
                                    <Label for="complemento">Complemento</Label>
                                    <Input
                                        id="complemento"
                                        name="unit.complemento"
                                        placeholder="Sala, Andar, etc."
                                    />
                                    <InputError
                                        :message="errors['unit.complemento']"
                                    />
                                </div>

                                <div class="space-y-2 sm:col-span-2">
                                    <Label for="bairro">Bairro</Label>
                                    <Input
                                        id="bairro"
                                        name="unit.bairro"
                                        placeholder="Bairro"
                                        v-model="bairroValue"
                                    />
                                    <InputError
                                        :message="errors['unit.bairro']"
                                    />
                                </div>

                                <div class="space-y-2 sm:col-span-3">
                                    <Label for="cidade">Cidade</Label>
                                    <Input
                                        id="cidade"
                                        name="unit.cidade"
                                        placeholder="Cidade"
                                        v-model="cidadeValue"
                                    />
                                    <InputError
                                        :message="errors['unit.cidade']"
                                    />
                                </div>

                                <div class="space-y-2 sm:col-span-1">
                                    <Label for="estado">UF</Label>
                                    <Input
                                        id="estado"
                                        name="unit.estado"
                                        placeholder="UF"
                                        maxlength="2"
                                        v-model="estadoValue"
                                    />
                                    <InputError
                                        :message="errors['unit.estado']"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                @click="() => router.visit(index().url)"
                                :disabled="processing"
                                class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            >
                                Cancelar
                            </Button>

                            <Button
                                type="submit"
                                :disabled="processing"
                                class="bg-green-600 text-sm text-white hover:bg-green-700"
                            >
                                {{
                                    processing ? 'Salvando...' : 'Salvar Escola'
                                }}
                            </Button>
                        </div>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
