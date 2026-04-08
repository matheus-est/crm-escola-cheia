# DECISIONS_LOG.md

> History of corrected pitfalls from completed modules.
> **Not read automatically.** Consult only when reopening a specific module.
> Agents: if you are touching a module listed below, read its section before implementing.

---

## Module 1 — Schools / SchoolUsers

| Date | File | Rule |
|---|---|---|
| 2026-04-02 | `SchoolObserver.php` | Slug generated on `creating` with `while` loop for incremental suffix; check `=== null` |
| 2026-04-02 | `SchoolObserver.php` | Historical bug: observer set `$school->id = UUID` instead of `$school->uuid` — already fixed |
| 2026-04-02 | `School.php` | `getRouteKeyName()` returns `uuid`; `id => string` cast removed (was a bug workaround) |
| 2026-04-02 | `SchoolPolicy.php` | Laravel 13 autodiscovery works — no manual policy registration needed |
| 2026-04-02 | `SchoolUserController.php` | `User` has no `getRouteKeyName()` — destroy resolves via `User::query()->where('uuid', ...)` |
| 2026-04-02 | `SchoolService.php` | `->get()` without pagination on `per_page=all` is a violation — always use `LengthAwarePaginator` |
| 2026-04-02 | `Create.vue` (Schools) | `Form({...})` created but never bound — dead code; correct pattern: `<Form v-bind="store.form()">` |
| 2026-04-02 | `Schools/Index.vue` | `router.get()` with `preserveState:true` breaks reka-ui Accordion — use `router.post(index.post().url)` without `preserveState` |
| 2026-04-02 | `Schools/Index.vue` | Accordion with `defaultValue="closed"` mandatory for reka-ui to close correctly |
| 2026-04-02 | `routes/admin.php` | `Route::resource()` does not accept POST on index — use explicit routes; `clearFilters` and `create` before `{school}` |
| 2026-04-02 | `AclServiceProvider.php` | `Gate::before()` returns `null` (not `false`) — policies evaluated normally in sequence |
| 2026-04-02 | `SchoolController.php` | `index()` must pass `isMaster` as Inertia prop — without it the Delete and New School buttons never appear |

---

## Module 2 — SchoolYear / LeadSource / Grade / Segment

| Date | File | Rule |
|---|---|---|
| 2026-04-03 | `SchoolYearService.php` | `list()` uses explicit `where('school_id', $school->id)` — `TenantScope` already filters but `$school` ensures isolation |
| 2026-04-03 | `Migrations` | `->enum()` never — use `->string(length)` with PHP backed enum cast on model |
| 2026-04-03 | `AppServiceProvider.php` | `Route::bind('school_uuid')` via `registerRouteBindings()` — unknown UUID returns 404 |
| 2026-04-03 | `TenantRoutingTest.php` | `/t/some-uuid/dashboard` returns 404 (not 302/403) after `Route::bind` — unknown UUID blocked by binding |
| 2026-04-03 | `tenant-settings/*.vue` | `props.school.uuid` for Wayfinder — never `setUrlDefaults` |

---

## Module 3 — Students / Guardians

| Date | File | Rule |
|---|---|---|
| 2026-04-03 | `StudentService.php` | CPF uniqueness per tenant validated in `create()` via `ValidationException`; `lookup()` uses `withoutTenantScope()` |
| 2026-04-03 | `useCpfLookup.ts` | `fetch` authorized here — GET lookup returns pure JSON, not Inertia; `Student`/`Guardian` interfaces in `crm.ts` |

---

## Module 4 — Opportunity (base)

| Date | File | Rule |
|---|---|---|
| 2026-04-03 | `OpportunityModelTest.php` | Pest 4: `toThrow(\Throwable::class)` fails — use manual try/catch with boolean flag |

---

## Global Seeders

| Date | File | Rule |
|---|---|---|
| 2026-04-01 | `SegmentSeeder.php` | `firstOrCreate()` — never `updateOrCreate()` with UUID as second argument (overwrites UUID on every run) |
| 2026-04-01 | `OutcomeSeeder.php` | `->delete()` + `->create()` on actions — never `sync()` |
| 2026-04-01 | `RoleSeeder.php` | Boilerplate uses `sync()` — known debt, do not fix to avoid changing code outside scope |
| 2026-04-01 | `User.php` | `currentSchool()` uses `class_exists(School::class)` — avoids fatal error before Stage 1.x |
---

## Infrastructure — Queue / Horizon (2026-04-08)

| Date | Decision |
|---|---|
| 2026-04-08 | Horizon chosen over database queue to support real-time job monitoring (dashboard at `/horizon`), auto-scaling workers per environment (`local: 3`, `staging: 2`, `production: 5`), and to prepare for notifications/events modules (stages 6–7). `QUEUE_CONNECTION` changed from `database` to `redis`. Queues defined: `notifications · emails · default`. Gate restricts Horizon access to `master` and `admin` roles via `$user->role?->slug`. |
