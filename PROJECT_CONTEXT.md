# PROJECT_CONTEXT.md

> Contexto do domínio para agentes de IA. Garante consistência de código,
> arquitetura e convenções em todo o ciclo de desenvolvimento.
> Última atualização: 2026-03-31

---

## 1. IDENTIFICAÇÃO DO PROJETO

| Campo | Valor |
|---|---|
| **Nome** | _(preencher ao iniciar projeto)_ |
| **Descrição** | _(preencher ao iniciar projeto)_ |
| **Cliente / Dono** | _(preencher ao iniciar projeto)_ |
| **Repositório** | _(URL do repositório)_ |
| **Ambiente** | _(local / staging / produção)_ |
| **Stack base** | Laravel 12 + Inertia.js v2 + Vue.js 3 |

> Se algum campo estiver em branco, solicite ao desenvolvedor que preencha antes de prosseguir.

---

## 2. STACK

### Backend

| Pacote | Versão |
|---|---|
| PHP | ^8.2 |
| Laravel | ^12.0 |
| Inertia (Laravel) | ^2.0 |
| Laravel Fortify | ^1.30 |
| Laravel Wayfinder | ^0.1.9 |
| owen-it/laravel-auditing | ^14.0 |
| tightenco/ziggy | ^2.6 |
| jenssegers/agent | ^2.6 |

### Frontend

| Pacote | Versão |
|---|---|
| Vue.js | ^3.5 |
| Inertia (Vue) | ^2.3 |
| Tailwind CSS | ^4.1 |
| reka-ui | ^2.9 |
| laravel-vue-i18n | ^2.8 |
| @vueuse/core | ^12.8 |
| lucide-vue-next | ^0.468 |
| @tiptap/vue-3 | ^3.20 |
| sweetalert2 | ^11 |

### Banco de dados

- Driver padrão: SQLite (configurável para MySQL/MariaDB via `.env`)
- Seeders: `RoleSeeder`, `PermissionSeeder`, `ModuleSeeder`, `MenuGroupSeeder`,
  `SystemSettingSeeder`, `TermVersionSeeder`
- Não existe `UserSeeder` — o primeiro usuário Master é criado via Tinker por segurança

---

## 3. MODELS E BANCO DE DADOS

| Model | Tabela | Soft Delete | Relações principais |
|---|---|---|---|
| User | users | ✓ | belongsTo Role, hasMany UserTermAcceptance |
| Role | roles | — | belongsToMany Permission, hasMany User |
| Permission | permissions | — | belongsToMany Role |
| Module | modules | — | hasMany ModuleAction |
| ModuleAction | module_actions | — | belongsTo Module |
| MenuGroup | menu_groups | — | — |
| TermVersion | term_versions | ✓ | — |
| UserTermAcceptance | user_term_acceptances | — | belongsTo User, belongsTo TermVersion |
| SystemSetting | system_settings | — | — |
| AuditLogin | audit_logins | — | belongsTo User |

> Todos os models de domínio usam `owen-it/laravel-auditing`.
> Identificação pública sempre via UUID — nunca ID numérico em URLs.

---

## 4. AUTENTICAÇÃO E AUTORIZAÇÃO

- Autenticação: Laravel Fortify com Inertia.js
- Guard: `web`
- Middleware de proteção: `auth`, `verified`
- RBAC: `Role → Permission → Module` (pivot `role_permissions`)
- Verificação no backend: via `Gate` ou Service
- Verificação no frontend: `$page.props.auth.permissions` (array de strings)

**Rotas públicas:** `/login`, `/register`, `/forgot-password`, `/reset-password`,
`/email/verify`, `/terms/accept.form`, `/terms/{uuid}/accept`

**Rotas privadas:** todas sob `auth` + `verified` — `/dashboard`, `/acl/*`, `/settings/*`

---

## 5. COMPONENTES E COMPOSABLES DISPONÍVEIS

> Verificar estes antes de criar qualquer coisa nova.

### Composables (`resources/js/composables/`)

| Composable | Função |
|---|---|
| `useToast` | Notificações toast |
| `useTwoFactorAuth` | Fluxo de 2FA |
| `useInitials` | Gera iniciais de um nome |
| `useAppearance` | Tema claro/escuro |
| `useCurrentUrl` | URL atual |

### Layouts (`resources/js/layouts/`)

| Layout | Uso |
|---|---|
| `AppLayout.vue` | Wrapper principal (delega para Header ou Sidebar) |
| `app/AppHeaderLayout.vue` | Com topbar fixo |
| `app/AppSidebarLayout.vue` | Com sidebar e menu dinâmico |
| `AuthLayout.vue` | Wrapper de autenticação |
| `auth/AuthCardLayout.vue` | Auth em card |
| `auth/AuthSimpleLayout.vue` | Auth simples |
| `auth/AuthSplitLayout.vue` | Auth dividido (form + imagem) |
| `Settings/Layout.vue` | Páginas de configurações |

---

## 6. CONVENÇÕES OBRIGATÓRIAS

### PHP

- `declare(strict_types=1)` no topo de todo arquivo PHP
- Property promotion do PHP 8 nos construtores
- Return types explícitos em todos os métodos
- Método `casts()` nos models — nunca a propriedade `$casts`; visibilidade sempre `protected`
- `?->` para encadeamento seguro — nunca `isset()`
- `=== null` / `!== null` para verificações de null — nunca `is_null()`
- UUIDs em URLs públicas — nunca IDs numéricos expostos
- Form Request para toda validação — nunca inline no Controller
- `ValidationException` em fluxos Inertia — nunca `back()->withErrors()`
- `to_route()` para redirecionamentos — nunca `redirect()->route()`
- `attach()`/`detach()` com diff em pivots auditadas — nunca `sync()`
- `Str::after()` — nunca `explode()[n]`
- `LengthAwarePaginator` em listagens — nunca `->get()` sem paginação
- `owen-it/laravel-auditing` em todos os models de domínio
- Observers registrados via `#[ObservedBy(NomeObserver::class)]` no model — nunca `Model::observe()` no ServiceProvider
- Scopes Eloquent: parâmetro e retorno sempre tipados como `Builder`
- `config()` — nunca `env()` fora de arquivos de configuração
- `Model::query()` — nunca `DB::table()` para queries de dados de domínio (`DB::transaction()` é permitido)
- Comentários e PHPDoc em inglês

### Vue / TypeScript

- `<script setup lang="ts">` em todos os componentes — nunca Options API
- TypeScript estrito — nunca `any`
- Props sempre tipadas com `interface` + `defineProps<Props>()`
- Emits sempre declarados com `defineEmits`
- Componentes em PascalCase, composables com prefixo `use`
- i18n via `laravel-vue-i18n` é opcional — strings hardcoded são permitidas; `$t()` já existente pode ser mantido
- Navegação via Wayfinder (`@/routes/`, `@/actions/`) — nunca URLs hardcoded
- Formulários via `useForm` do Inertia — nunca `fetch()` direto

### Regras de menu lateral

- Itens sem grupo → primeiro (ordenados por `order`)
- Grupos normais → depois (ordenados por `order`)
- Grupo **"Configurações"** → sempre o último, ignora `order`

---

## 7. TESTES

- Framework: Pest v4
- Convenção: `it('should...')` e `test('...')`
- Factories disponíveis: `UserFactory`, `RoleFactory`, `PermissionFactory`, `ModuleFactory`,
  `MenuGroupFactory`, `TermFactory`, `SystemSettingFactory`, `AuditLoginFactory`
- Executar: `php artisan test --compact` (com `--filter=Nome` para isolar)

---

## 8. ESCOPO DO PROJETO ATUAL

> Preencher ao iniciar um novo projeto baseado neste boilerplate.

| Campo | Valor |
|---|---|
| **Objetivo** | _(descrição do sistema)_ |
| **Módulos planejados** | _(listar)_ |
| **Entidades do domínio** | _(listar)_ |
| **Integrações externas** | _(listar ou "nenhuma")_ |
| **Requisitos não-funcionais** | _(listar)_ |
| **Decisões que fogem ao boilerplate** | _(registrar aqui)_ |

---

_Atualizar este documento sempre que houver mudanças estruturais no projeto._

---

## 9. LICOES APRENDIDAS E ARMADILHAS COMUNS

### Backend

#### Model: lógica de negócio proibida
Models só devem conter relações Eloquent, accessors simples, fillable/casts/dates e scopes de query.
Qualquer método que implique regra de negócio, verificação de estado ou mutação deve ir para o Service.
Isso inclui métodos aparentemente simples como `isActive()`.

**Errado:**
```php
// app/Models/Role.php
public function givePermission(string $permission): void { ... }
public function revokePermission(string $permission): void { ... }
public function hasPermission(string $permission): bool { ... }

// app/Models/AuditLogin.php
public function isActive(): bool { return $this->status === 'active'; }
```

**Correto:** mover para `RoleService`, `UserService`, `AuditLoginService` etc.

#### Model: scopes devem tipar o parâmetro `Builder`
Todo scope local deve declarar `Builder` como tipo de parâmetro e retorno.

**Errado:**
```php
public function scopeActive($query) { return $query->where(...); }
```

**Correto:**
```php
use Illuminate\Database\Eloquent\Builder;

public function scopeActive(Builder $query): Builder
{
    return $query->where('status', 'active');
}
```

#### Model: `$casts` como propriedade em vez de método
Nunca declarar `protected $casts = [...]`. Sempre usar o método:
```php
protected function casts(): array { return [...]; }
```

#### `DB::` desnecessário em Services
O uso de `DB::transaction()` é permitido; o uso de `DB::table()` para queries
de dados de domínio é proibido. Use sempre `Model::query()`.

#### `PermissionFactory` com campo inexistente
A factory de Permission referencia o campo `module` (string livre), mas o model
usa `module_id` (FK). A factory deve referenciar um `ModuleFactory`.

#### Duplicate keys em componentes Vue
Ao combinar `defineProps<Props>()` com `usePage().props`, nunca redeclare
as mesmas variáveis locais com o mesmo nome das props. Causa `vue/no-dupe-keys`.

**Errado:**
```ts
const props = defineProps<{ roles: Role[] }>();
const roles = page.props.roles as Role[]; // duplicata!
```

**Correto:** usar apenas `props.roles` ou apenas `page.props.roles`, nunca os dois.

#### Imports não utilizados disparam lint bloqueante
Todo import que não é usado no template ou no script deve ser removido antes do commit.
Erros `@typescript-eslint/no-unused-vars` e `vue/no-dupe-keys` são bloqueantes no lint.

#### Hardcoded URLs no frontend (breadcrumbs)
Breadcrumbs com `href: '/acl/roles'` são URLs hardcoded. Usar sempre Wayfinder:
```ts
import { index } from '@/routes/roles';
{ title: 'Funções', href: index().url }
```

#### Service: infraestrutura HTTP não pertence ao Service
`Auth::logout()`, invalidação de sessão e manipulação de request/response são responsabilidades do Controller.
O Service deve operar apenas sobre dados e modelos — sem conhecimento de HTTP.

**Errado:**
```php
// ProfileService.php
public function deleteAccount(User $user): void {
    Auth::logout();           // infraestrutura HTTP — não pertence aqui
    $user->forceDelete();
}
```

**Correto:**
```php
// ProfileController.php
public function destroy(Request $request): RedirectResponse {
    Auth::logout();                              // HTTP — correto no Controller
    $this->profileService->deleteAccount($user); // dados — delega ao Service
    $request->session()->invalidate();
    return to_route('login');
}

// ProfileService.php
public function deleteAccount(User $user): void {
    $user->forceDelete();    // apenas lógica de dados
}
```

### Frontend

#### `usePage()` duplicando props já declaradas no `defineProps`
Em páginas Inertia, o `defineProps<Props>()` já recebe os dados do servidor.
Redeclará-los via `usePage().props` gera variáveis duplicadas e erros de lint.
Escolha um dos dois mecanismos e seja consistente.

#### `fetch()` direto proibido — usar `axios` ou `useForm`
`fetch()` nativo não carrega os headers do Inertia (X-Inertia, CSRF), quebrando autenticação e proteção CSRF.

**Errado:**
```ts
const res = await fetch('/api/check-email')
```

**Correto:**
- Submissões de formulário: `useForm` do Inertia
- Requisições assíncronas (ex: debounce/verificação): `axios.get(url)` — já configurado globalmente com CSRF e headers Inertia

#### `window.location.href` proibido para navegação interna
`window.location.href` força full-page reload, quebrando o SPA e perdendo o estado do Inertia.

**Errado:**
```ts
window.location.href = edit({ user_uuid: uuid }).url
```

**Correto:**
```ts
import { router } from '@inertiajs/vue3'
router.visit(edit({ user_uuid: uuid }).url)
```

#### Service: acessar relação inexistente no model via propriedade magic retorna null

`$user->permissions` não é uma relação declarada no model `User`. A cadeia correta é
`User → Role → Permission` (`User::role()` → `Role::permissions()`).
Acessar uma relação não declarada como propriedade magic retorna `null`, e qualquer
chamada de método encadeada (ex: `->pluck()`) lança `Call to a member function X() on null`.

Isso afeta qualquer usuário logado — inclusive o perfil master — porque o bug é
estrutural, não condicional ao perfil.

**Errado:**
```php
// MenuService.php
$permissionNames = $user->permissions->pluck('name'); // 'permissions' não existe em User
```

**Correto:**
```php
// MenuService.php
$permissionNames = $user->role?->permissions->pluck('name') ?? collect();
```

**Regra:** Antes de encadear `->método()` em uma relação navegada, verificar se
cada elo da cadeia está declarado no model correspondente. Usar `?->` em cada
ponto onde `null` é possível (relação BelongsTo não carregada) e fornecer fallback
com `?? collect()` quando o retorno esperado é uma Collection.

#### Configuração de sistema não verificada antes de ações destrutivas

Quando uma `SystemSetting` controla permissão para uma ação (ex: `allow_self_deletion`),
a verificação **obrigatoriamente** deve ocorrer no Service antes de executar a ação.
Não verificar é equivalente a ignorar completamente a configuração — o usuário consegue
excluir a própria conta independentemente do valor configurado.

Adicionalmente, o frontend deve receber a configuração como prop via `ProfileController::edit()`
(ou `HandleInertiaRequests`) para esconder a UI de deleção quando a feature está desabilitada.
Esconder no frontend sem bloquear no backend é insuficiente; bloquear no backend sem esconder
no frontend é UX ruim mas funcionalmente correto.

**Errado:**
```php
// ProfileService.php — ignora allow_self_deletion completamente
public function deleteAccount(User $user): void
{
    $user->forceDelete();
}
```

**Correto:**
```php
// ProfileService.php
public function deleteAccount(User $user): void
{
    $allowed = (bool) $this->settingService->get('allow_self_deletion', false);

    if (! $allowed) {
        throw ValidationException::withMessages([
            'password' => [__('Self account deletion is not allowed.')],
        ]);
    }

    $user->forceDelete();
}
```

**Regra:** Toda ação destrutiva ou sensível controlada por `SystemSetting` deve
ser validada no Service com `ValidationException` antes de qualquer mutação.
O frontend deve receber o flag como prop para condicionar a exibição da UI.

#### `window.location.href` em componente Vue após ação Inertia
Usar `window.location.href` dentro de um callback `onSuccess` do `router.post()` força
um full-page reload desnecessário — quebrando o SPA e perdendo o estado do Inertia.
O componente `ReactivateUserModal.vue` comete este erro ao redirecionar após restauração.

**Errado:**
```ts
// ReactivateUserModal.vue
router.post(url, {}, {
    onSuccess: () => {
        window.location.href = `/acl/users/${props.userData.uuid}/edit`; // full reload
    },
});
```

**Correto:**
```ts
// ReactivateUserModal.vue
import { router } from '@inertiajs/vue3';
import { edit } from '@/routes/users';

router.post(url, {}, {
    onSuccess: () => {
        router.visit(edit({ user_uuid: props.userData!.uuid }).url);
    },
});
```

#### URLs hardcoded em breadcrumbs e navegação de páginas Roles/Modules
Páginas como `Roles/Create.vue`, `Roles/Edit.vue`, `Modules/Edit.vue` e `Modules/Index.vue`
utilizam strings literais (`'/acl/roles'`, `'/acl/modules'`) em breadcrumbs e em chamadas
`router.visit()`. Wayfinder deve ser usado em todos os pontos de navegação.

**Errado:**
```ts
// Roles/Create.vue
const breadcrumbItems = [{ title: 'Funções', href: '/acl/roles' }];
router.visit('/acl/roles');
```

**Correto:**
```ts
import { index } from '@/routes/roles';
const breadcrumbItems = [{ title: 'Funções', href: index().url }];
router.visit(index().url);
```

#### `is_null()` proibido — usar `=== null`
A convenção do projeto exige `=== null` / `!== null` para verificações de nulidade.
`is_null()` não é permitido.

**Errado:**
```php
// UserService.php
if (is_null($user->password_changed_at)) { ... }
```

**Correto:**
```php
if ($user->password_changed_at === null) { ... }
```

#### `isset()` proibido para verificar presença de chave em array de dados
`isset()` sobre chaves de array de dados pode mascarar `null` explícito e diverge
das convenções do projeto. Usar `array_key_exists()` ou verificação explícita.

**Errado:**
```php
// RoleService.php / ModuleService.php
if (isset($data['permissions'])) { ... }
'is_active' => isset($data['is_active']),
```

**Correto:**
```php
if (array_key_exists('permissions', $data)) { ... }
'is_active' => array_key_exists('is_active', $data) && $data['is_active'],
```

#### IDs numéricos expostos via props Inertia junto com UUIDs
Controllers expõem `'id' => $model->id` (inteiro) nas props Inertia ao mesmo tempo
em que expõem `'uuid'`. IDs numéricos internos não devem ser trafegados para o frontend,
pois revelam informações sobre sequências do banco. Expor apenas UUID para referência pública;
ID numérico pode ser mantido internamente apenas quando estritamente necessário para
operações de pivot que não têm UUID (ex: `permissions.id` para attach/detach).

**Errado:**
```php
// RoleController.php — expõe 'id' desnecessariamente
'roles' => $paginator->through(fn ($role) => [
    'id' => $role->id,   // exposto ao frontend
    'uuid' => $role->uuid,
    ...
]),
```

**Correto:** Expor apenas `uuid` para referência no frontend; usar UUID nos parâmetros
de rota. O `id` só é aceitável em arrays de pivot (ex: `permission_ids`) onde o UUID
não é necessário para a operação (attach/detach por ID é a API do Eloquent).

#### `fetch()` nativo no composable `useTwoFactorAuth`
O composable usa `fetch()` nativo para buscar QR code, setup key e recovery codes.
`fetch()` não carrega os headers do Inertia (X-Inertia, CSRF token), quebrando a
proteção CSRF em ambientes que a exigem. Usar `axios` (já configurado globalmente).

**Errado:**
```ts
// useTwoFactorAuth.ts
const response = await fetch(url, { headers: { Accept: 'application/json' } });
```

**Correto:**
```ts
import axios from 'axios';
const { data } = await axios.get<T>(url);
```

**Exceção documentada:** As rotas do Fortify para 2FA (`/user/two-factor-qr-code`,
`/user/two-factor-secret-key`, `/user/two-factor-recovery-codes`) são GET e retornam
JSON puro, portanto o risco de CSRF é baixo neste caso específico. Ainda assim,
`axios` deve ser preferido por consistência com o restante do projeto.

#### `Auth::logout()` deve ser chamado antes de `forceDelete()` no controller de deleção de conta
O `Auth::logout()` do Laravel chama internamente `cycleRememberToken()`, que faz
`$user->save()` para rotacionar o token. Se `forceDelete()` for chamado antes do logout,
o usuário é removido do banco, mas o `save()` subsequente reinsere o registro com um
novo ID e dados diferentes — anulando silenciosamente o delete.

**Errado:**
```php
// ProfileController.php
public function destroy(ProfileDeleteRequest $request): RedirectResponse
{
    $user = $request->user();
    $this->profileService->deleteAccount($user); // forceDelete aqui
    Auth::logout();  // save() do token reinsere o usuário deletado
    ...
}
```

**Correto:**
```php
// ProfileController.php
public function destroy(ProfileDeleteRequest $request): RedirectResponse
{
    $user = $request->user();
    Auth::logout();                              // logout primeiro (save() seguro)
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    $this->profileService->deleteAccount($user); // forceDelete após o logout
    return to_route('login');
}
```

#### Layout split-panel de autenticação duplicado em páginas individuais
`Login.vue` e `ForgotPassword.vue` implementavam inline o painel esquerdo completo
(grid pattern, glows, logo dinâmico, headline, lista de features, footer) e a lógica
de detecção de dark mode via `MutationObserver`, duplicando literalmente o mesmo bloco.
O layout `AuthSplitLayout.vue` existia mas não era usado por nenhuma página.

A lógica de dark mode e todo o painel esquerdo devem residir exclusivamente em
`AuthSplitLayout.vue`. As páginas passam apenas as props tipadas (`headline`,
`subheadline`, `features`) e expõem o formulário via `<slot />`.

**Errado:**
```ts
// Login.vue (e ForgotPassword.vue — idêntico)
const isDarkMode = ref(false);
const detectDarkMode = () => { ... };
onMounted(() => {
    detectDarkMode();
    const observer = new MutationObserver(...);
    observer.observe(document.documentElement, { attributes: true });
    onBeforeUnmount(() => observer.disconnect()); // erro: onBeforeUnmount dentro de onMounted
});
// + bloco completo de template do painel esquerdo inline
```

**Correto:**
```ts
// AuthSplitLayout.vue — único local com MutationObserver e painel esquerdo
// Login.vue — apenas props + slot do formulário
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
// <AuthSplitLayout headline="..." :features="features">
//   <div class="w-full max-w-sm">...</div>
// </AuthSplitLayout>
```

**Observação adicional:** o padrão original chamava `onBeforeUnmount()` dentro do
callback de `onMounted()`, o que não funciona — o hook precisa ser registrado no
nível do `setup`. `AuthSplitLayout.vue` corrige isso declarando `observer` no escopo
do script e chamando `onBeforeUnmount()` no nível correto.

#### URL hardcoded `/email/verification-notification` em `VerifyEmail.vue`
A página usava `form.post('/email/verification-notification')` com URL literal,
ignorando que a rota Wayfinder `send` está disponível em `@/routes/verification`.

**Errado:**
```ts
const resend = () => {
    form.post('/email/verification-notification');
};
```

**Correto:**
```ts
import { send } from '@/routes/verification';
const resend = () => {
    form.post(send().url);
};
```
