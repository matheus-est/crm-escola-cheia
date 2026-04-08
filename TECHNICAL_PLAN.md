# TECHNICAL_PLAN.md
> Last updated: 2026-04-01

## FOLDER STRUCTURE — BACKEND

```
app/
├── Actions/Opportunity/ · Actions/Task/
├── Enums/               OpportunityStatus · TaskStatus · TaskType
├── Http/
│   ├── Controllers/
│   │   ├── Acl/         (boilerplate — do not modify)
│   │   ├── Admin/       SchoolController · SchoolUserController · DashboardController
│   │   ├── Public/      LeadCaptureController
│   │   ├── Settings/    (boilerplate — do not modify)
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
    ├── School/           SchoolService (attachUser/detachUser) · ViaCepService
    ├── Opportunity/      OpportunityService
    ├── Task/             TaskService · OutcomeProcessorService · RenitenteCycleService
    ├── Student/ · Guardian/ · Lead/ · LeadSource/ · SchoolYear/ · Event/
    └── (boilerplate: Acl/ · Menu/ · SettingService)
```

## FOLDER STRUCTURE — FRONTEND

```
resources/js/
├── composables/    useCepLookup · useCpfLookup · useNotifications (+ boilerplate)
├── components/     CpfField · NotificationBell · Task/TaskPanel · Task/OutcomeSelector
│                   Task/RefusalForm · Task/TaskCreateModal (+ boilerplate)
├── layouts/        PublicLayout (+ boilerplate)
├── pages/
│   ├── admin/Schools/    Index · Create · Edit (includes Users section)
│   ├── opportunities/    Index · Create · Show
│   ├── tenant-settings/  Grades · LeadSources · Rooms · SchoolYears
│   ├── events/           Index · Create · Edit
│   ├── calendar/         Index
│   ├── reports/          Index
│   └── public/           LeadCapture · LeadCaptureConfirmation
└── types/          crm.ts (School · SchoolUnit · SchoolUser · Opportunity · Task · etc.)
```

## DATABASE — CRM TABLES

| Table | Notes |
|---|---|
| `school_user` | Pivot `school_id + user_id + is_active` — no `role` column |
| `schools` | No `BelongsToTenant` — it is the root tenant |
| `school_units` | `BelongsToTenant` |
| `segments` | Global, no `BelongsToTenant` |
| `grades` | `BelongsToTenant` |
| `school_years` | `BelongsToTenant` |
| `lead_sources` | nullable `school_id` — system or per school |
| `students` · `guardians` | `BelongsToTenant` · index `(school_id, cpf)` |
| `opportunities` | `BelongsToTenant` · SoftDelete |
| `tasks` | `BelongsToTenant` · field `renitente_count` |
| `outcomes` · `outcome_actions` | Global, no `BelongsToTenant` |
| `lgpd_consents` | No `BelongsToTenant` · no auditing |

Boilerplate tables kept intact: `users · roles · permissions · role_permissions · modules · module_actions · menu_groups · term_versions · user_term_acceptances · system_settings · audit_logins · audits · sessions · cache · jobs`

## SEEDERS (execution order)
```
DatabaseSeeder → RoleSeeder → PermissionSeeder → ModuleSeeder → MenuGroupSeeder
              → SystemSettingSeeder → TermVersionSeeder
              → SegmentSeeder (6 segments) → OutcomeSeeder (31 normal + 10 refusals = 41)
```
First Master user created via Tinker — no UserSeeder.

## ROUTES

| File | Prefix | Middleware |
|---|---|---|
| `acl.php` | `/acl` | `auth, verified, gates` |
| `admin.php` | `/admin` | `auth, verified, role:master,admin,operacao` |
| `tenant.php` | `/t/{school_uuid}` | `auth, verified, tenant, tenant.access` |
| `settings.php` | `/settings` | `auth` / `auth, verified` |
| `web.php` | — | `web` |

Conventions: `snake_case` names with dot · UUIDs in params · `to_route()` always.

### Admin routes — Schools
| Verb | URL | Name |
|---|---|---|
| GET | `/admin/schools` | `schools.index` |
| GET | `/admin/schools/create` | `schools.create` |
| POST | `/admin/schools` | `schools.store` |
| GET | `/admin/schools/{uuid}/edit` | `schools.edit` |
| PUT | `/admin/schools/{uuid}` | `schools.update` |
| DELETE | `/admin/schools/{uuid}` | `schools.destroy` |
| POST | `/admin/schools/{uuid}/users` | `schools.users.store` |
| DELETE | `/admin/schools/{uuid}/users/{user_uuid}` | `schools.users.destroy` |
## INFRASTRUCTURE

### Queue workers
- Dev: `php artisan horizon` — replaces `php artisan queue:work`
- Production: Supervisor manages the `horizon` process (auto-scaling workers per environment config)
- Worker queues (priority order): `notifications`, `emails`, `default`

### Scheduled commands
| Command | Frequency | Purpose |
|---|---|---|
| `audit-logs:purge` | daily at 01:00 | Purge old audit log entries |
| `horizon:snapshot` | every 5 minutes | Capture metrics snapshot for Horizon dashboard graphs |
