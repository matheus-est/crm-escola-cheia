<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { watch } from 'vue'
import { ArrowLeft } from 'lucide-vue-next'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useCepLookup } from '@/composables/useCepLookup'
import AppLayout from '@/layouts/AppLayout.vue'
import { index, store } from '@/routes/admin/schools'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
    { title: 'Nova Escola', href: '#' },
]

const form = useForm({
    cnpj: '',
    razao_social: '',
    status: 'active' as 'active' | 'inactive',
    unit: {
        nome: 'Matriz',
        cep: '',
        logradouro: '',
        numero: '',
        complemento: '',
        bairro: '',
        cidade: '',
        estado: '',
    }
})

const { isLoading: isLoadingCep, error: cepError, lookup: lookupCep } = useCepLookup()

let isAutoFillingCep = false

watch(() => form.unit.cep, (newCep) => {
    if (isAutoFillingCep) return
    lookupCep(newCep, (data) => {
        isAutoFillingCep = true
        form.unit.logradouro = data.logradouro
        form.unit.bairro = data.bairro
        form.unit.cidade = data.cidade
        form.unit.estado = data.estado
        setTimeout(() => isAutoFillingCep = false, 100)
    })
})

function submit() {
    form.post(store().url)
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nova Escola" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading title="Nova Escola" description="Cadastre uma nova escola e sua unidade matriz no sistema." class="pt-8" />
            </div>

            <div class="rounded-md border">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-6 p-6">
                        <!-- Dados Gerais -->
                        <div class="space-y-4 rounded-lg border bg-card p-6 shadow-sm">
                            <h3 class="text-lg font-medium">Dados Gerais</h3>
                    
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="cnpj">CNPJ</Label>
                            <Input
                                id="cnpj"
                                v-model="form.cnpj"
                                placeholder="00.000.000/0000-00"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.cnpj }"
                            />
                            <p v-if="form.errors.cnpj" class="text-xs text-destructive">{{ form.errors.cnpj }}</p>
                        </div>
                        
                        <div class="space-y-2">
                            <Label for="razao_social">Razão Social</Label>
                            <Input
                                id="razao_social"
                                v-model="form.razao_social"
                                placeholder="Escola Exemplo"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.razao_social }"
                            />
                            <p v-if="form.errors.razao_social" class="text-xs text-destructive">{{ form.errors.razao_social }}</p>
                        </div>
                    </div>
                </div>

                <!-- Unidade Matriz -->
                <div class="pt-4 border-t">
                            <h3 class="text-lg font-medium mb-4">Endereço da Unidade Matriz</h3>
                            
                            <div class="grid gap-4 sm:grid-cols-6">
                        <div class="space-y-2 sm:col-span-2">
                            <Label for="cep">CEP</Label>
                            <div class="relative">
                                <Input
                                    id="cep"
                                    v-model="form.unit.cep"
                                    placeholder="00000-000"
                                    :class="{ 'border-destructive focus-visible:ring-destructive': form.errors['unit.cep'] }"
                                />
                                <span v-if="isLoadingCep" class="absolute right-3 top-2.5 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"></span>
                            </div>
                            <p v-if="cepError" class="text-xs text-destructive">{{ cepError }}</p>
                            <p v-if="form.errors['unit.cep']" class="text-xs text-destructive">{{ form.errors['unit.cep'] }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-4">
                            <Label for="logradouro">Logradouro</Label>
                            <Input
                                id="logradouro"
                                v-model="form.unit.logradouro"
                                placeholder="Rua, Avenida, etc."
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors['unit.logradouro'] }"
                            />
                            <p v-if="form.errors['unit.logradouro']" class="text-xs text-destructive">{{ form.errors['unit.logradouro'] }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="numero">Número</Label>
                            <Input
                                id="numero"
                                v-model="form.unit.numero"
                                placeholder="123"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors['unit.numero'] }"
                            />
                            <p v-if="form.errors['unit.numero']" class="text-xs text-destructive">{{ form.errors['unit.numero'] }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-4">
                            <Label for="complemento">Complemento</Label>
                            <Input
                                id="complemento"
                                v-model="form.unit.complemento"
                                placeholder="Sala, Andar, etc."
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors['unit.complemento'] }"
                            />
                            <p v-if="form.errors['unit.complemento']" class="text-xs text-destructive">{{ form.errors['unit.complemento'] }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="bairro">Bairro</Label>
                            <Input
                                id="bairro"
                                v-model="form.unit.bairro"
                                placeholder="Bairro"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors['unit.bairro'] }"
                            />
                            <p v-if="form.errors['unit.bairro']" class="text-xs text-destructive">{{ form.errors['unit.bairro'] }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-3">
                            <Label for="cidade">Cidade</Label>
                            <Input
                                id="cidade"
                                v-model="form.unit.cidade"
                                placeholder="Cidade"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors['unit.cidade'] }"
                            />
                            <p v-if="form.errors['unit.cidade']" class="text-xs text-destructive">{{ form.errors['unit.cidade'] }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-1">
                            <Label for="estado">UF</Label>
                            <Input
                                id="estado"
                                v-model="form.unit.estado"
                                placeholder="UF"
                                maxlength="2"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors['unit.estado'] }"
                            />
                            <p v-if="form.errors['unit.estado']" class="text-xs text-destructive">{{ form.errors['unit.estado'] }}</p>
                        </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4">
                        <Button type="button" variant="outline" @click="() => router.visit(index().url)" :disabled="form.processing" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="bg-green-600 text-sm text-white hover:bg-green-700">
                            {{ form.processing ? 'Salvando...' : 'Salvar Escola' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
