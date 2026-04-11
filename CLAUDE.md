# CLAUDE.md

@MEMORY.md

## Agent flow (`.claude/agents/`)
```
idea → /prompt-engineer → /planner → [approval] → /backend → /reviewer → /frontend → /reviewer
```

## On completing any task
```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run lint && npm run format
```
Update `MEMORY.md` — mandatory.

## Session resume
Read `MEMORY.md`. Report: (1) what is done (2) what is pending (3) where to resume.
Wait for confirmation before continuing.

## Reference files — consult as needed
- `PROJECT_CONTEXT.md` → domain rules (funnel, outcomes, task types)
- `TECHNICAL_PLAN.md` → folder structure, database, routes
- `DECISIONS_LOG.md` → only when reopening a completed module (pitfall history)

---

## PHP conventions — inviolable

- `declare(strict_types=1)` in every file
- `casts()` as method, never `$casts` property
- `=== null` never `is_null()` · `array_key_exists()` never `isset()` on arrays
- `attach()`/`detach()` never `sync()`
- `LengthAwarePaginator` in listings, never `->get()`
- `to_route()` never `redirect()->route()`
- `ValidationException` never `back()->withErrors()`
- UUID in URLs, never numeric ID
- `#[ObservedBy]` on model, never `Model::observe()` in ServiceProvider
- `config()` never `env()` outside config files
- Migrations: `->string()` never `->enum()` — cast to PHP backed enum on model
- `owen-it/laravel-auditing` on every domain model

## Database safety — inviolable

- **Agents must NEVER autonomously run any destructive database command** — this restriction applies to all agents in the pipeline (planner, backend, frontend, reviewer) without exception, even when the command appears to be "safe" in a test context
- **Never** run `php artisan migrate:fresh`, `migrate:refresh`, `migrate:reset`, or `db:wipe` without explicit written confirmation from the developer — these commands destroy real data
- **Never** run `php artisan db:seed` or `php artisan migrate --seed` on the development database without explicit written confirmation
- Tests use SQLite `:memory:` (see `phpunit.xml`) — `RefreshDatabase` is correct and **must not** be replaced with `DatabaseTransactions`
- `phpunit.xml` `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:` must never be changed to point at the real MySQL database
- When a new migration is needed during development, run only `php artisan migrate` (forward-only) — never reset and replay
- Masked fields (CEP, CNPJ, CPF, telefone/celular) are **always stored with their display mask** — never strip non-digits before persisting; strip only when needed for API calls or mod-11 validation logic

## Vue/TS conventions — inviolable

- `<script setup lang="ts">` · TypeScript strict, no `any`
- Wayfinder for all navigation
- Inertia `useForm`, never `fetch`/`axios`
- Mandatory UI reference: `resources/js/pages/acl/Users/` (Index · Create · Edit)
- Every new module follows this structure. Deviation requires prior declaration.

## UI rules

| Context | Rule |
|---|---|
| Filter accordion | `<Accordion class="w-72">` + `<AccordionContent class="pt-2">` — never `absolute`, `relative`, or `overflow-visible` |
| `router.reload` | `preserveUrl: true` — never `preserveScroll` |
| Create form | `<Form method="post" :action="store().url">` |
| Edit form | `<Form method="put" :action="update({ uuid: props.model.uuid }).url">` + `:default-value` on inputs |
| Masked inputs (CNPJ, CPF, phone) | Handle `@input` with DOM-only mutation (`input.value = masked`) — **never** `:value` + `ref` + `watch`; the reactive binding causes Vue to re-render on every keystroke, producing visible typing delay and cursor jump |
| `SelectItem` | **Never** use `value=""` — reka-ui treats empty string as no-value; omit the empty option and rely on `SelectValue placeholder` for optional selects |

## Multi-tenancy — critical rules

- Every domain model uses `BelongsToTenant` (GlobalScope by `school_id`)
- `school_id` **never** from request — always auto-set by `BelongsToTenant` `creating` event from `app('tenant.school_id')`
- `SchoolService::attachUser()` is the **only** point that inserts into `school_user`
- Tenant resolved exclusively from session (`active_school_uuid`) via `SetActiveTenant` → `User::currentSchool()`
- Tenant routes use prefix `/t` — **no** `{school_uuid}` in URL
- Tenant controllers **never** receive `School $school` as route parameter — use `auth()->user()->currentSchool()` when school object is needed
- Tenant services **never** receive `$school` as parameter — tenant is resolved globally via `BelongsToTenant`
- In services, always use `Model::create([...])` — **never** `$model->school_id = ...; $model->save()` — `BelongsToTenant` sets `school_id` automatically on the `creating` event
- `EnsureTenantAccess` aborts 403 if user has no valid school in session

## Critical business rules

- An opportunity never has two `open` tasks simultaneously — validate in `TaskService`
- Task completion: `DB::transaction()` covering Task + Outcome + OutcomeActions
- `OutcomeProcessorService` — **only** executor of tabulation actions
- `RenitenteCycleService` — **only** renitente delay calculator
- `open_window` in the response is the **only** modal trigger on frontend, never by slug
- Terminal statuses (`matricula`, `recusado`) are definitive — no profile can reopen them

## Controller structure

- `Controllers/Admin/` → cross-tenant
- `Controllers/Tenant/` → tenant-scoped (`/t`)
- `Controllers/Public/` → no authentication