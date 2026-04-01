# TECHNICAL_PLAN.md
> Última atualização: 2026-04-01

## ESTRUTURA DE PASTAS — BACKEND

```
app/
├── Actions/Opportunity/ · Actions/Task/
├── Enums/               OpportunityStatus · TaskStatus · TaskType
├── Http/
│   ├── Controllers/
│   │   ├── Acl/         (boilerplate — não modificar)
│   │   ├── Admin/       SchoolController · SchoolUserController · DashboardController
│   │   ├── Public/      LeadCaptureController
│   │   ├── Settings/    (boilerplate — não modificar)
│   │   └── Tenant/      OpportunityController · TaskController · StudentController
│   │                    GuardianController · EventController · RoomController
│   │                    CalendarController · ReportController
│   │                    Settings/LeadSourceController · Settings/SchoolYearController
│   ├── Middleware/       CheckRole · SetActiveTenant · EnsureTenantAccess (+ boilerplate)
│   └── Requests/         School/ · Opportunity/ · Task/ · Lead/
├── Models/Concerns/      BelongsToTenant.php
├── Models/               School · SchoolUnit · Segment · Grade · SchoolYear
│                         LeadSource · Student · Guardian · Opportunity
│                         Task · Outcome · OutcomeAction · Event · Room · LgpdConsent
│                         (+ boilerplate: User · Role · Permission · Module · etc.)
├── Observers/            SchoolObserver · OpportunityObserver
├── Policies/             SchoolPolicy · OpportunityPolicy · TaskPolicy · StudentPolicy · etc.
└── Services/
    ├── School/           SchoolService (inclui attachUser/detachUser) · ViaCepService
    ├── Opportunity/      OpportunityService
    ├── Task/             TaskService · OutcomeProcessorService · RenitenteCycleService
    ├── Student/ · Guardian/ · Lead/ · LeadSource/ · SchoolYear/ · Event/
    └── (boilerplate: Acl/ · Menu/ · SettingService)
```

## ESTRUTURA DE PASTAS — FRONTEND

```
resources/js/
├── composables/    useCepLookup · useCpfLookup · useNotifications (+ boilerplate)
├── components/     CpfField · NotificationBell · Task/TaskPanel · Task/OutcomeSelector
│                   Task/RefusalForm · Task/TaskCreateModal (+ boilerplate)
├── layouts/        PublicLayout (+ boilerplate)
├── pages/
│   ├── admin/Schools/    Index · Create · Edit (inclui seção Usuários da escola)
│   ├── opportunities/    Index · Create · Show
│   ├── tenant-settings/  Grades · LeadSources · Rooms · SchoolYears
│   ├── events/           Index · Create · Edit
│   ├── calendar/         Index
│   ├── reports/          Index
│   └── public/           LeadCapture · LeadCaptureConfirmation
└── types/          crm.ts (School · SchoolUnit · SchoolUser · Opportunity · Task · etc.)
```

## BANCO DE DADOS — TABELAS DO CRM

| Tabela | Observação |
|---|---|
| `school_user` | Pivot `school_id + user_id + is_active` — sem coluna `role` |
| `schools` | Sem `BelongsToTenant` — é o tenant raiz |
| `school_units` | `BelongsToTenant` |
| `segments` | Global, sem `BelongsToTenant` |
| `grades` | `BelongsToTenant` |
| `school_years` | `BelongsToTenant` |
| `lead_sources` | `school_id` nullable — sistema ou por escola |
| `students` · `guardians` | `BelongsToTenant` · índice `(school_id, cpf)` |
| `opportunities` | `BelongsToTenant` · SoftDelete |
| `tasks` | `BelongsToTenant` · campo `renitente_count` |
| `outcomes` · `outcome_actions` | Global, sem `BelongsToTenant` |
| `lgpd_consents` | Sem `BelongsToTenant` · sem auditing |

Tabelas do boilerplate mantidas intactas: `users · roles · permissions · role_permissions · modules · module_actions · menu_groups · term_versions · user_term_acceptances · system_settings · audit_logins · audits · sessions · cache · jobs`

## SEEDERS (ordem de execução)
```
DatabaseSeeder → RoleSeeder (master/admin/operacao/gestor/comercial)
              → PermissionSeeder (permissões do CRM por role)
              → ModuleSeeder → MenuGroupSeeder → SystemSettingSeeder → TermVersionSeeder
              → SegmentSeeder (6 segmentos) → OutcomeSeeder (tabulações + actions)
```
Primeiro usuário Master criado via Tinker — não existe UserSeeder.

## ROTAS

| Arquivo | Prefixo | Middleware |
|---|---|---|
| `acl.php` | `/acl` | `auth, verified, gates` |
| `admin.php` | `/admin` | `auth, verified, role:master,admin,operacao` |
| `tenant.php` | `/t/{school_uuid}` | `auth, verified, tenant, tenant.access` |
| `settings.php` | `/settings` | `auth` / `auth, verified` |
| `web.php` | — | `web` |

Convenções: nomes `snake_case` com ponto · UUIDs em parâmetros · `to_route()` sempre

### Rotas Admin — Schools
| Verbo | URL | Nome |
|---|---|---|
| GET/POST | `/admin/schools` | `schools.index` |
| GET | `/admin/schools/create` | `schools.create` |
| POST | `/admin/schools` | `schools.store` |
| GET | `/admin/schools/{uuid}/edit` | `schools.edit` |
| PUT | `/admin/schools/{uuid}` | `schools.update` |
| DELETE | `/admin/schools/{uuid}` | `schools.destroy` |
| POST | `/admin/schools/{uuid}/users` | `schools.users.store` |
| DELETE | `/admin/schools/{uuid}/users/{user_uuid}` | `schools.users.destroy` |