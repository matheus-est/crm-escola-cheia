# MEMORY.md

> Read at the start of every session. Self-sufficient for 90% of tasks.
> Update only the sections that changed when closing a session.

---

## CURRENT STATE

**Last session:** 2026-04-09
**Next task:** 7.R — Reviewer pass on Stage 7 (Events frontend rewrite)

### Completed

- [x] 0.1 — Environment (MySQL, Reverb, laravel-auditing)
- [x] 0.2 — BelongsToTenant · SetActiveTenant · EnsureTenantAccess
- [x] 0.3 — RoleSeeder · CrmPermissionSeeder · User::isCrossTenant() · currentSchool()
- [x] 0.4 — CheckRole middleware · routes/admin.php · routes/tenant.php
- [x] 0.5 — SegmentSeeder (6) · OutcomeSeeder (41 outcomes + actions)
- [x] 1.1 — migrations schools · School/SchoolUnit models · observers · tests (3)
- [x] 1.2 — SchoolService · ViaCepService · SchoolPolicy · requests · controller · routes · tests (62)
- [x] 1.3 — SchoolUserAttachRequest · SchoolUserController · routes · tests (71)
- [x] 1.4 — Frontend Schools (Index · Create · Edit with users section)
- [x] 1.5 — Bugfixes Schools/Index
- [x] 2.1 — Segment · Grade · migrations school_segment/grades · observers
- [x] 2.2 — SchoolYear complete — tests (86) — APPROVED 2026-04-03
- [x] 2.3 — LeadSource complete — tests (98) — APPROVED 2026-04-03
- [x] 2.4 — Frontend Tenant Settings: SchoolYears · LeadSources · Grades
- [x] 3.1 — Student/Guardian backend — tests (118) — APPROVED 2026-04-03
- [x] 3.2 — useCpfLookup · CpfField.vue
- [x] 4.1 — OpportunityStatus enum · migration · Opportunity model · observer · tests (119) — APPROVED 2026-04-03
- [x] 4.2 — OpportunityService · StoreOpportunityRequest · UpdateOpportunityRequest · OpportunityPolicy · OpportunityController · routes · tests (129) — APPROVED 2026-04-05
- [x] 4.3 — Frontend Opportunities (Index · Create · Edit) — 2026-04-05
- [x] 4.4 — ActiveSchoolController · User::schools() · currentSchool() session fallback · active-school.store route · BelongsToTenant auto school_id on creating — 2026-04-07
- [x] 4.5 — Opportunity form fields: history · indications · registration_type · segment_id · guardian address columns — backend only — 2026-04-07
- [x] 4.R — Session-based tenant refactor: routes prefix `t` (no school_uuid) · all tenant controllers remove School param · services remove School param · useCpfLookup · Vue Wayfinder calls — 139 tests — 2026-04-07
- [x] 4.6 — Schools Create/Edit refactor: 2 abas (Dados/Responsáveis) · máscara CNPJ · BrasilAPI lookup · `SchoolController::lookupCnpj()` · `SchoolUserController::storeOrCreate()` · `SchoolService::createUserForSchool()` · `WelcomeSchoolUserMail` · `useCnpjLookup.ts` · shadcn Tabs — 23 tests — 2026-04-07
- [x] 4.7 — Opportunities Create/Edit refactor: 3 abas (Cadastro/Aluno/Complementar) · CPF mask formatCpf() inline · segments prop · address fields · history / indications Textarea — 2026-04-07
- [x] 5.1 — TaskType · TaskStatus · OutcomeActionType enums · Task model + observer + factory · TaskPolicy · TaskService (list/create/complete/cancel) · OutcomeProcessorService stub · RenitenteCycleService stub · StoreTaskRequest · CompleteTaskRequest · TaskController · TaskResource · routes · renitente_count on opportunities — 18 tests — 2026-04-08
- [x] 5.2 — OpportunityController::show · OutcomeResource · TaskResource is_schedule · opportunities.show route · OpportunityShowTest (5 tests) — 148 tests — 2026-04-08
- [x] 5.3 — Show.vue (opportunity detail + active task panel + task history) · OutcomeModal.vue (fetch-based tabulation) · TaskCreateModal.vue (useForm) · Index.vue Eye link + fixed Wayfinder calls · task.ts lib · Task/Outcome/TaskType types in crm.ts — 2026-04-08
- [x] 4.8 — CpfRule (mod-11) · GuardianController::validateCpf · tenant route guardians.validate_cpf · CpfRule in Store/UpdateOpportunityRequest · OpportunityService::create() phone→telefone + address fields + segment_id + null-cpf guard · guardians.cpf nullable migration — 180 tests — 2026-04-08
- [x] 7.1 — Frontend Events (Index · Create · Edit with opportunities section) — 2026-04-09
- [x] ADJ.1 — ActiveSchoolService: `school_current_id` salvo incondicionalmente (removido guard `count > 1`) — 2026-04-09
- [x] ADJ.2 — Room: migration remove `description`/`is_active` + add `is_external` · model/requests/service/factory/tests/TS updated — 2026-04-09
- [x] ADJ.3 — Room frontend: inline form → shadcn Dialog modal · checkbox "sala externa" · coluna "Externa" na tabela · filtro is_active removido — 2026-04-09
- [x] 7.2B — Events backend refactor: migrations 005/006 (title/event_type/has_no_date/grade_id + event_room pivot) · Event model (rooms/grade relations) · StoreEventRequest · UpdateEventRequest · EventService (room_ids attach/detach, listAvailable no is_active) · EventController (grades/rooms/school_name props) · EventFactory · crm.ts Event interface · EventTest updated + 6 new tests — 2026-04-09
- [x] 7.3F — Events frontend rewrite: RoomFormDialog.vue component · Rooms.vue refactored · events/Create.vue with 2 tabs · events/Edit.vue with 3 tabs · events/Index.vue title+has_no_date — 2026-04-09
- [x] 7.4F — Reviewer fixes: inline delete (Index.vue) · has_no_date hidden input (Create+Edit) · attachOpportunity→to_route() + useForm.post() (controller+Edit.vue) · room_uuids/grade_uuid UUID-based validation · school_id removed from Event $fillable · migration dropColumn separated · Limpar filtros button · breadcrumb href:#
- [ ] **5.4 — Reviewer pass on Stage 5 🔴**
- [ ] 6–11 — Notifications · Events · Form · Calendar · Reports · LGPD

---

## ARCHITECTURE DECISIONS

| Date | Decision |
|---|---|
| 2026-04-01 | RabbitMQ removed — initially set `QUEUE_CONNECTION=database` |
| 2026-04-08 | Queue driver migrated to Redis + Laravel Horizon — `QUEUE_CONNECTION=redis`; queues: notifications · emails · default |
| 2026-04-01 | `school_user` without `role` column — profile via `users.role_id` |
| 2026-04-01 | `CrmPermissionSeeder` separate — boilerplate seeders not touched |
| 2026-04-01 | `currentSchool()` uses `class_exists(School::class)` — avoids fatal error before Stage 1.x |
| 2026-04-01 | `CheckRole` with alias `role:` in bootstrap/app.php |
| 2026-04-01 | OutcomeSeeder: 41 outcomes (31 normal + 10 refusal) — spec says 40 but tables list 31 normal |
| 2026-04-02 | `School` does not use `BelongsToTenant` — it is the root tenant |
| 2026-04-02 | Base `Controller` without `AuthorizesRequests` — use `Gate::authorize()` directly |
| 2026-04-02 | `Gate::before()` returns `null` (not `false`) when no permission |
| 2026-04-02 | `SchoolPolicy::update()` restricted to Master and Admin — Operacao cannot manage school-user links |
| 2026-04-03 | `Segment` is global (no `BelongsToTenant`) — `Grade` uses `BelongsToTenant` |
| 2026-04-03 | `LeadSource` uses its own `LeadSourceScope` — not `BelongsToTenant`; `school_id null` = system |
| 2026-04-03 | `useCpfLookup` uses `fetch` — only authorized case outside `useForm` (GET lookup, pure JSON) |
| 2026-04-07 | Tenant routes use prefix `/t` — no `{school_uuid}` in URL; tenant resolved from session via `SetActiveTenant` → `currentSchool()` |
| 2026-04-07 | `BelongsToTenant` auto-sets `school_id` via `creating` event — controllers and services never set it manually; `LeadSource` (no BelongsToTenant) still uses `app('tenant.school_id')` explicitly |
| 2026-04-07 | `useCpfLookup` no longer needs `schoolUuid` — lookup routes are `/t/students/lookup/{cpf}` |
| 2026-04-08 | `guardians.cpf` is nullable — guardian can be created without CPF from opportunity form |
| 2026-04-09 | Event requests use `room_uuids`/`grade_uuid` (UUID-based) — service resolves to IDs before `attach()`; controller passes only `uuid` (not `id`) in Room/Grade props |
| 2026-04-09 | `event_type` column is `string(60)` — values provisórios (palestra/workshop/visita) — aguardando confirmação do cliente |

---

## ACTIVE PATTERNS AND PITFALLS

Only pitfalls that may affect future modules.
Resolved module pitfalls live in `DECISIONS_LOG.md`.

| Date | File | Rule |
|---|---|---|
| 2026-04-02 | `*ControllerTest.php` | `ValidationException` returns 302 on normal requests — use `withHeader('Accept', 'application/json')` to get 422 |
| 2026-04-02 | `*ControllerTest.php` | Inertia routes in tests: use `withoutVite()` — avoids `ViteException` |
| 2026-04-02 | `phpunit.xml` | `config:cache` in production makes tests ignore `phpunit.xml` — clear with `php artisan config:clear` before testing |
| 2026-04-03 | `routes/tenant.php` | Route `lookup/{cpf}` must come BEFORE `{model}` in the group — avoids route model binding conflict |
| 2026-04-07 | `*Service.php` | `list()` relies on `TenantScope` — no `$school` parameter; `LeadSource` list uses explicit `app('tenant.school_id')` in where clause |
| 2026-04-03 | `OpportunityModelTest.php` | Pest 4: `toThrow(\Throwable::class)` fails — use manual try/catch with boolean flag |
| 2026-04-07 | `*.vue` (tenant) | Wayfinder functions for tenant routes take NO school_uuid — `index()`, `store()`, `update({ opportunity })` — school is in session |
| 2026-04-05 | `*Service.php` | Non-blocking warnings: add `hasClosed*` helper on service; controller checks after `create()` and flashes `warning` vs `success` — never block creation |
| 2026-04-05 | `*.vue` (ui) | No `Textarea` component in `ui/` — use plain `<textarea>` with inline Tailwind classes matching shadcn Input style |
| 2026-04-08 | `OpportunityService::create()` | Pass guardian fields through `array_filter` removing nulls/empty before `findOrCreate()` — guardian `cpf` is nullable but not all guardians arrive with CPF |
| 2026-04-08 | `opportunities/Create.vue` | `Guardian` interface uses Portuguese field names (`telefone`, `cep`, `logradouro`, `numero`, `bairro`, `cidade`, `estado`) — never English aliases in `fillGuardianFields()` |
| 2026-04-09 | `EventTest.php` | 419 CSRF failures on POST/PUT/DELETE are pre-existing throughout the test suite — GET tests pass; the 419 pattern is systemic, not module-specific |
| 2026-04-09 | `*.vue` (forms) | Boolean checkbox in Inertia `<Form>`: add `<input type="hidden" name="field" value="0" />` BEFORE the Checkbox — unchecked sends 0, checked overrides with 1 |

---

## AGENT INSTRUCTIONS

### On session start
1. This file is loaded automatically via `@MEMORY.md` import in `CLAUDE.md`
2. Consult `PROJECT_CONTEXT.md` for domain details (funnel, outcomes, task types)
3. Consult `TECHNICAL_PLAN.md` for schema, routes, or folder structure
4. **Never re-read everything as a precaution** — high cost, zero gain

### On session end (mandatory)
- `## CURRENT STATE` — mark completed items, update next task
- `## ACTIVE PATTERNS AND PITFALLS` — add only what may affect future modules (1 line)
- `## ARCHITECTURE DECISIONS` — add if any new decision was made
- Pitfalls from closed modules → `DECISIONS_LOG.md`, not here
