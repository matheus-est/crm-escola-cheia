# TECHNICAL_PLAN.md

> Estrutura técnica do boilerplate. Lido por agentes junto com `PROJECT_CONTEXT.md`.
> Última atualização: 2026-03-22

---

## 1. ESTRUTURA DE PASTAS

### 1.1 Backend (`app/`)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Acl/
│   │   │   ├── MenuGroupController.php
│   │   │   ├── ModuleController.php
│   │   │   ├── PermissionController.php
│   │   │   ├── RoleController.php
│   │   │   ├── TermController.php
│   │   │   └── UserController.php
│   │   └── Settings/
│   │       ├── LoginHistoryController.php
│   │       ├── PasswordChangeController.php
│   │       ├── PasswordController.php
│   │       ├── ProfileController.php
│   │       ├── SystemSettingController.php
│   │       └── TwoFactorAuthenticationController.php
│   ├── Middleware/
│   │   ├── AccessControl.php          # Verifica permissão via gate
│   │   ├── EnforceSingleSession.php
│   │   ├── ForcePasswordChange.php    # Redireciona para troca obrigatória
│   │   ├── HandleAppearance.php
│   │   ├── HandleInertiaRequests.php  # Shared data (auth, menu, locale, etc.)
│   │   ├── SetLocale.php
│   │   └── TermAcceptanceRequired.php # Força aceite de termos pendentes
│   └── Requests/
│       ├── ForcePasswordChangeRequest.php
│       ├── MenuGroupRequest.php
│       ├── PermissionStoreRequest.php
│       ├── PermissionUpdateRequest.php
│       ├── RoleStoreRequest.php
│       ├── RoleUpdateRequest.php
│       ├── TermStoreRequest.php
│       ├── TermUpdateRequest.php
│       ├── UserStoreRequest.php
│       ├── UserUpdateRequest.php
│       └── Settings/
├── Listeners/
│   ├── LogSuccessfulLogin.php
│   ├── LogFailedLogin.php
│   └── LogLogout.php
├── Mail/
│   ├── WelcomeUserMail.php
│   └── UserReactivatedMail.php
├── Models/
│   ├── AuditLogin.php
│   ├── MenuGroup.php
│   ├── Module.php
│   ├── ModuleAction.php
│   ├── Permission.php
│   ├── Role.php
│   ├── SystemSetting.php
│   ├── TermVersion.php
│   ├── User.php
│   └── UserTermAcceptance.php
├── Providers/
│   ├── AclServiceProvider.php         # Registra Gates de permissão
│   ├── AppServiceProvider.php         # Listeners de login/logout, regras de senha
│   └── FortifyServiceProvider.php     # Configura Fortify (login, 2FA, etc.)
└── Services/
    ├── Acl/
    │   ├── AuditLoginService.php
    │   ├── ModuleService.php
    │   ├── PermissionService.php
    │   ├── RoleService.php
    │   ├── TermService.php
    │   └── UserService.php
    ├── Menu/
    │   ├── MenuGroupService.php
    │   └── MenuService.php            # Menu dinâmico por role (com cache)
    └── SettingService.php
```

### 1.2 Frontend (`resources/js/`)

```
resources/js/
├── app.ts
├── ssr.ts
├── i18n.js
├── actions/                           # Wayfinder — gerado automaticamente
├── routes/                            # Wayfinder — gerado automaticamente
├── wayfinder/
├── composables/
│   ├── useAppearance.ts
│   ├── useCurrentUrl.ts
│   ├── useInitials.ts
│   ├── useToast.ts
│   └── useTwoFactorAuth.ts
├── components/
│   ├── ui/                            # Primitivos reka-ui
│   ├── AppContent.vue
│   ├── AppHeader.vue
│   ├── AppLogo.vue / AppLogoIcon.vue
│   ├── AppShell.vue
│   ├── AppSidebar.vue                 # Menu dinâmico via page.props.menu
│   ├── AppSidebarHeader.vue
│   ├── Breadcrumbs.vue
│   ├── ConfirmDeleteModal.vue
│   ├── ConfirmRestoreModal.vue
│   ├── LoginHistoryTable.vue
│   ├── NavMain.vue
│   ├── NavUser.vue
│   ├── PasswordRequirements.vue
│   ├── PerPageSelect.vue
│   ├── ReactivateUserModal.vue
│   ├── RolePermissions.vue
│   ├── TablePagination.vue
│   ├── TermsModal.vue
│   ├── TiptapEditor.vue
│   ├── TwoFactorRecoveryCodes.vue
│   ├── TwoFactorSetupModal.vue
│   └── UserMenuContent.vue
├── layouts/
│   ├── AppLayout.vue
│   ├── AuthLayout.vue
│   ├── app/
│   │   ├── AppHeaderLayout.vue
│   │   └── AppSidebarLayout.vue
│   └── auth/
│       ├── AuthCardLayout.vue
│       ├── AuthSimpleLayout.vue
│       └── AuthSplitLayout.vue
├── pages/
│   ├── Dashboard.vue
│   ├── Welcome.vue
│   ├── acl/
│   │   ├── MenuGroups/
│   │   ├── Modules/
│   │   ├── Roles/
│   │   ├── Terms/
│   │   └── Users/
│   ├── auth/
│   └── settings/
│       ├── Appearance.vue
│       ├── ForcePasswordChange.vue
│       ├── LoginHistory.vue
│       ├── Password.vue
│       ├── Profile.vue
│       ├── SystemSetting.vue
│       ├── TermAccept.vue
│       └── TwoFactor.vue
└── types/
    ├── auth.ts
    ├── globals.d.ts
    ├── index.ts
    ├── navigation.ts
    ├── ui.ts
    └── vue-shims.d.ts
```

---

## 2. BANCO DE DADOS

### 2.1 Tabelas e responsabilidades

| Tabela | Model | Propósito |
|---|---|---|
| `users` | `User` | Usuários; FK `role_id`; soft deletes |
| `roles` | `Role` | Perfis de acesso |
| `permissions` | `Permission` | Permissões atômicas; FK `module_id` |
| `role_permissions` | pivot | N:N entre roles e permissions |
| `modules` | `Module` | Módulos do sistema; FK `menu_group_id` |
| `module_actions` | `ModuleAction` | Ações de um módulo |
| `menu_groups` | `MenuGroup` | Agrupadores de módulos no menu |
| `audit_logins` | `AuditLogin` | Histórico de login/logout |
| `term_versions` | `TermVersion` | Versões de termos; soft deletes |
| `user_term_acceptances` | `UserTermAcceptance` | Aceites de termos por usuário |
| `system_settings` | `SystemSetting` | Configurações globais (chave/valor) |
| `audits` | *(owen-it)* | Log de alterações em models auditáveis |
| `sessions` / `cache` / `jobs` | *(framework)* | Sessões, cache e filas |

### 2.2 Seeders (ordem de execução)

```
DatabaseSeeder
├── RoleSeeder
├── PermissionSeeder
├── ModuleSeeder
├── MenuGroupSeeder
├── SystemSettingSeeder
└── TermVersionSeeder
```

> Não existe `UserSeeder` — o primeiro usuário Master é criado via Tinker por segurança.

---

## 3. ROTAS

### 3.1 Arquivos de rotas

| Arquivo | Prefixo | Middleware | Conteúdo |
|---|---|---|---|
| `web.php` | — | `web` | Home, dashboard, troca de senha, i18n |
| `acl.php` | `/acl` | `auth`, `verified`, gates | Users, Roles, Modules, MenuGroups, Terms |
| `settings.php` | `/settings` | `auth` / `auth+verified` | Profile, Password, 2FA, SystemSettings |

### 3.2 Convenções de rotas

- Nomeação: `snake_case` com ponto → `users.index`, `roles.permissions.update`
- Parâmetros públicos: sempre UUID → `{user_uuid}`, `{role_uuid}`
- Redirecionamento: sempre `to_route('nome.rota')`
- Proteção: gates via `middleware('can:permissao_action')`

### 3.3 Mapa de rotas — ACL (`/acl`)

| Método | URL | Nome | Gate |
|---|---|---|---|
| GET+POST | `/acl/users` | `users.index` | `users_list` |
| GET | `/acl/users/create` | `users.create` | `users_add` |
| POST | `/acl/users/store` | `users.store` | `users_add` |
| GET | `/acl/users/{uuid}/edit` | `users.edit` | `users_edit` |
| PUT | `/acl/users/{uuid}` | `users.update` | `users_edit` |
| DELETE | `/acl/users/{uuid}` | `users.destroy` | `users_delete` |
| POST | `/acl/users/{uuid}/restore` | `users.restore` | `users_restore` |
| GET | `/acl/users/{uuid}/export` | `users.export` | `users_export_data` |
| GET+POST | `/acl/roles` | `roles.index` | `roles_list` |
| GET | `/acl/roles/{uuid}/permissions` | `roles.permissions` | `permissions_edit` |
| PUT | `/acl/roles/{uuid}/permissions` | `roles.permissions.update` | `permissions_edit` |
| GET+POST | `/acl/modules` | `modules.index` | `modules_list` |
| GET+POST | `/acl/menu-groups` | `menu-groups.index` | `menu_groups_list` |
| GET+POST | `/acl/terms` | `terms.index` | `terms_list` |
| GET | `/terms/accept` | `terms.accept.form` | — |
| POST | `/terms/{uuid}/accept` | `terms.accept` | — |

### 3.4 Mapa de rotas — Settings (`/settings`)

| Método | URL | Nome | Guard |
|---|---|---|---|
| GET | `/settings/profile` | `profile.edit` | `auth` |
| PATCH | `/settings/profile` | `profile.update` | `auth` |
| GET | `/settings/profile/export` | `profile.export` | `auth` |
| DELETE | `/settings/profile` | `profile.destroy` | `auth+verified` |
| GET | `/settings/password` | `user-password.edit` | `auth+verified` |
| PUT | `/settings/password` | `user-password.update` | `auth+verified` |
| GET | `/settings/two-factor` | `two-factor.show` | `auth+verified` |
| GET+POST | `/settings/login-history` | `login-history.edit` | `auth+verified` |
| GET | `/settings/system` | `settings.system.index` | `settings_manage` |
| PUT | `/settings/system/identity` | `settings.system.identity.update` | `settings_manage` |
| PUT | `/settings/system/security` | `settings.system.security.update` | `settings_manage` |
| PUT | `/settings/system/email` | `settings.system.email.update` | `settings_manage` |
| PUT | `/settings/system/lgpd` | `settings.system.lgpd.update` | `settings_manage` |

---

## 4. FLUXOS PRINCIPAIS

### 4.1 Autenticação (Fortify)

```
POST /login
  → FortifyServiceProvider (pipeline de login)
  → LogSuccessfulLogin / LogFailedLogin (Listeners)
  → AuditLoginService::recordLogin()
  → Redirecionamento para /dashboard

Middlewares em toda sessão autenticada:
  ForcePasswordChange → TermAcceptanceRequired → (rota protegida)
```

### 4.2 Montagem do Menu

```
HandleInertiaRequests::share()
  → MenuService::getMenuForUser(User)
  → cache()->remember('user_menu_{role_id}', 3600, fn => buildMenu())
  → buildMenu():
      1. Carrega permissions do usuário via role
      2. Filtra modules com show_in_menu=true e is_active=true
      3. Aplica filtro de permissão
      4. groupModules():
           - Dashboard → primeiro (item simples)
           - Itens sem menu_group_id → após dashboard
           - Grupos com módulos visíveis → após itens simples
           - Grupo "configuration" → último (PHP_INT_MAX)
  → Serializado como array em page.props.menu

Invalidação de cache:
  - Editar permissões de role → MenuService::clearMenuCacheForRole(int $roleId)
  - Editar módulo → MenuService::clearAllMenuCache()
```

### 4.3 Controle de Permissões (RBAC)

```
AclServiceProvider::registerGates()
  → Gate::before(): hasPermission($ability)
  → Gate::define('access-module')

User::hasPermission(string $permission): bool
  → $this->role->hasPermission($permission)

Middleware nas rotas: middleware('can:users_list')
Frontend: page.props.auth.permissions (array de strings)
```

### 4.4 Shared Data (Inertia)

```
Shared em toda page.props via HandleInertiaRequests:
  ├── auth.user           → id, uuid, name, email, role
  ├── auth.permissions    → array de strings
  ├── menu                → MenuItem[]
  ├── locale              → pt_BR | en | es
  ├── translations        → objeto i18n
  ├── name                → app_name
  ├── logo_light / logo_dark / company_name / dpo_email
  ├── appearance          → 'light' | 'dark' | 'system'
  ├── currentTerm         → TermVersion | null
  ├── userAcceptance      → UserTermAcceptance | null
  └── needsTermAcceptance → boolean
```

---

## 5. NOMENCLATURA

| Artefato | Padrão | Exemplo |
|---|---|---|
| Controller | PascalCase, singular, sufixo `Controller` | `UserController` |
| Service | PascalCase, singular, sufixo `Service` | `UserService` |
| Model | PascalCase, singular | `User` |
| Form Request | PascalCase, descritivo | `UserStoreRequest` |
| Migration | snake_case com timestamp | `2026_03_11_create_roles_table` |
| Rota (nome) | snake_case com pontos | `users.index` |
| URL da rota | kebab-case | `/acl/menu-groups` |
| Componente Vue | PascalCase | `NavMain.vue` |
| Composable | camelCase com `use` | `useToast.ts` |
| Página Inertia | PascalCase, pasta por módulo | `acl/Users/Index.vue` |
