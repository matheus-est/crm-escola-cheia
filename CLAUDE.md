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

## Multi-tenancy — critical rules

- Every domain model uses `BelongsToTenant` (GlobalScope by `school_id`)
- `school_id` **never** from request — always from `auth()->user()->currentSchool()`
- `SchoolService::attachUser()` is the **only** point that inserts into `school_user`
- Cross-tenant resolves tenant via `{school_uuid}` in the route
- `EnsureTenantAccess` aborts 403 if gestor/comercial has no link in `school_user`

## Critical business rules

- An opportunity never has two `open` tasks simultaneously — validate in `TaskService`
- Task completion: `DB::transaction()` covering Task + Outcome + OutcomeActions
- `OutcomeProcessorService` — **only** executor of tabulation actions
- `RenitenteCycleService` — **only** renitente delay calculator
- `open_window` in the response is the **only** modal trigger on frontend, never by slug
- Terminal statuses (`matricula`, `recusado`) are definitive — no profile can reopen them

## Controller structure

- `Controllers/Admin/` → cross-tenant
- `Controllers/Tenant/` → tenant-scoped (`/t/{school_uuid}`)
- `Controllers/Public/` → no authentication