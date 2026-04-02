<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3'
import { AlertCircle, Trash2, ArrowLeft, Building2, Users } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import Heading from '@/components/Heading.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import AppLayout from '@/layouts/AppLayout.vue'
import { index, update } from '@/routes/admin/schools'
import { store as storeUser, destroy as destroyUser } from '@/routes/admin/schools/users'
import type { BreadcrumbItem } from '@/types'
import type { School, SchoolUser } from '@/types/crm'

const props = defineProps<{
    school: { data: School }
    schoolUsers: { data: SchoolUser[] }
    availableRoles: Array<{ id: number, name: string }>
    allUsers: Array<{ id: number, name: string }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
    { title: 'Editar Escola', href: '#' },
]

const activeTab = ref<'details' | 'users'>('details')

const form = useForm({
    cnpj: props.school.data.cnpj || '',
    razao_social: props.school.data.razao_social || '',
    status: props.school.data.status || 'active',
    slug: props.school.data.slug || '',
})

function submit() {
    form.put(update({ school: props.school.data.id }).url)
}

function cancel() {
    router.visit(index().url)
}

// User attachment
const userForm = useForm({
    user_id: '',
    role_id: ''
})

function attachUser() {
    userForm.post(storeUser({ school: props.school.data.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            userForm.reset()
        }
    })
}

function detachUser(userId: string) {
    if (confirm('Tem certeza que deseja remover este usuário da escola?')) {
        router.delete(destroyUser({ school: props.school.data.id, userUuid: userId }).url, {
            preserveScroll: true
        })
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Escola" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading title="Editar Escola" description="Alterar os dados da escola cadastrada." class="pt-8" />
            </div>

            <div class="rounded-md border">
                <div class="flex border-b">
                    <button
                        type="button"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'details'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'details'"
                    >
                        <Building2 class="h-4 w-4" />
                        Dados da Escola
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'users'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'users'"
                    >
                        <Users class="h-4 w-4" />
                        Usuários Vinculados
                    </button>
                </div>

                <form v-show="activeTab === 'details'" @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-6 p-6">

                    <!-- Alerta sobre alteração de slug -->
                    <Alert class="border-amber-500/50 text-amber-600 dark:border-amber-500/30 dark:text-amber-500 bg-amber-50/50 dark:bg-amber-500/10">
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>Atenção</AlertTitle>
                        <AlertDescription>
                            Alterar o slug atual <strong>quebrará automaticamente</strong> quaisquer links do formulário de captação público espalhados por anúncios ou pelo site da escola. Altere apenas se estritamente necessário.
                        </AlertDescription>
                    </Alert>

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

                        <div class="space-y-2">
                            <Label for="slug">Slug da Instância</Label>
                            <Input
                                id="slug"
                                v-model="form.slug"
                                placeholder="..."
                                readonly
                                class="bg-muted cursor-not-allowed"
                                :class="{ 'border-destructive focus-visible:ring-destructive': form.errors.slug }"
                            />
                            <p class="text-xs text-muted-foreground">O slug é usado nas URLs públicas e não pode ser editado pelo frontend.</p>
                            <p v-if="form.errors.slug" class="text-xs text-destructive">{{ form.errors.slug }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Selecione..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Ativo</SelectItem>
                                    <SelectItem value="inactive">Inativo</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                        </div>
                    </div>
                    </div>
                    
                    <div class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4">
                        <Button type="button" variant="outline" @click="cancel" :disabled="form.processing" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="bg-green-600 text-sm text-white hover:bg-green-700">
                            {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                        </Button>
                    </div>
                </form>

                <div v-show="activeTab === 'users'" class="space-y-6 p-6">

                <!-- Adicionar usuário -->
                <form @submit.prevent="attachUser" class="flex flex-wrap items-end gap-4 rounded-md bg-muted/50 p-4">
                    <div class="flex-1 space-y-2 min-w-[200px]">
                        <Label for="user_id">Usuário</Label>
                        <Select v-model="userForm.user_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecione um usuário..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="u in props.allUsers" :key="u.id" :value="u.id.toString()">
                                    {{ u.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="userForm.errors.user_id" class="text-xs text-destructive">{{ userForm.errors.user_id }}</p>
                    </div>
                    
                    <div class="w-48 space-y-2">
                        <Label for="role_id">Perfil na Escola</Label>
                        <Select v-model="userForm.role_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Perfil..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="r in props.availableRoles" :key="r.id" :value="r.id.toString()">
                                    {{ r.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="userForm.errors.role_id" class="text-xs text-destructive">{{ userForm.errors.role_id }}</p>
                    </div>

                    <Button type="submit" :disabled="userForm.processing || !userForm.user_id || !userForm.role_id">
                        Vincular
                    </Button>
                </form>

                <!-- Tabela de usuários vinculados -->
                <div class="rounded-md border">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nome</th>
                                <th class="px-4 py-3 font-medium">E-mail</th>
                                <th class="px-4 py-3 font-medium">Perfil</th>
                                <th class="px-4 py-3 font-medium text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="su in props.schoolUsers.data" :key="su.id">
                                <td class="px-4 py-3">{{ su.name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ su.email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ring-primary/20 bg-primary/10 text-primary">
                                        {{ su.role?.name || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button 
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Remover Vínculo"
                                        @click="detachUser(su.uuid)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="props.schoolUsers.data.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum usuário vinculado a esta escola ainda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    </AppLayout>
</template>
