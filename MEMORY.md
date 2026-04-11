# MEMORY.md

> Read at the start of every session. Self-sufficient for 90% of tasks.
> Update only the sections that changed when closing a session.

---

## CURRENT STATE

**Last session:** 2026-04-10
**Next task:** 6 — Notifications module (or continue 4.x improvements)
**Last rebrand:** 2026-04-10 — Identidade visual migrada para Operis CRM (azul `#2D3AE0`, Eurostile)

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
- [x] ADJ-FT — crm.ts: AvailableOpportunity interface added — 2026-04-09
- [x] ADJ-F2 — events/Create.vue + Edit.vue: ícones Info/DoorOpen/Users nas abas — 2026-04-09
- [x] ADJ-F3 — events/Create.vue + Edit.vue: campo Data sempre visível, :disabled=hasNoDate — 2026-04-09
- [x] ADJ-F4 — events/Edit.vue: available-opportunities table com fetch + router.post attach (removido attachForm/useForm) — 2026-04-09
- [x] ADJ-F5 — opportunities/Create.vue + Edit.vue: campo Unidade usa nome_fantasia ?? razao_social — 2026-04-09
- [x] ADJ-F6 — opportunities/Create.vue + Edit.vue: select Tarefa Vinculada funcional (task_type) — 2026-04-09
- [x] ADJ-F7 — opportunities/Create.vue: ícones ClipboardList/User/FileText nas abas; Edit.vue refatorado para botões manuais + ícones — 2026-04-09
- [x] ADJ-F8 — ModuleSeeder: lead_sources show_in_menu => false — 2026-04-09
- [x] 7.5F — EventType frontend: EventTypeFormDialog · EventTypeToggleDialog · EventTypes.vue settings page · crm.ts EventType interface · events/Create.vue+Edit.vue use event_type_uuid Select with dynamic EventType list — 2026-04-10
- [x] ADJ-B1 — RoomController: store/update/destroy use back() — dialog-safe redirect (from Events form) — 2026-04-09
- [x] ADJ-B2 — StoreEventRequest/UpdateEventRequest: prepareForValidation() nullifies event_date when has_no_date=true — 2026-04-09
- [x] ADJ-B3 — EventService::listUnlinkedOpportunities() · EventController::availableOpportunities() · route events.available_opportunities — 2026-04-09
- [x] ADJ-B4 — StoreOpportunityRequest: task_type rule · OpportunityService injects TaskService + creates task on opportunity create — 2026-04-09
- [x] ADJ-B5 — Masked fields stored as-is: migration fix_masked_field_columns (schools.cnpj varchar(18), school_units cep/logradouro/bairro/cidade nullable) · SchoolStoreRequest+UpdateRequest normalize CNPJ to masked format · OpportunityService remove CEP strip · CLAUDE.md rule added — 2026-04-09
- [x] ADJ-EN — Portuguese→English field rename cleanup: auth.ts · TenantSwitcher.vue · Schools/Index.vue · SchoolYears.vue · Grades.vue · SchoolObserver · WelcomeSchoolUserMail · StudentUpdateRequest · 15+ test files (razao_social→legal_name, nome→name, telefone→phone, cep→zip_code, logradouro→street, bairro→neighborhood, cidade→city, estado→state, numero→number) — 197 tests passing — 2026-04-09
- [x] FIX-A — SchoolYear inicio/fim→start/end: StoreRequest+UpdateRequest+tests+crm.ts SchoolYear interface · SchoolYearFactory created · guardians.cpf nullable migration added · boilerplate Settings tests deleted — 2026-04-10
- [x] 7.6B — EventType module backend: migration create_event_types + alter_events_event_type_to_fk · EventType model/observer/policy/service/requests/controller/resource/factory · EventTypeSeeder (Palestra/Workshop/Visita per tenant) · ModuleSeeder+PermissionSeeder+RoleSeeder updated · Event model event_type_id FK + eventType() relation · StoreEventRequest+UpdateEventRequest prepareForValidation UUID→ID — 2026-04-10
- [x] 7.6F — EventType module frontend: EventTypeFormDialog · EventTypeToggleDialog (password-confirmed) · EventTypes.vue settings page · crm.ts EventType interface + PaginatedEventTypes (with links) · events/Create.vue+Edit.vue event_type_uuid Select · EventController edit() loads current event_type even if inactive — 2026-04-10\n- [x] ADJ-SY — SchoolYears.vue: inline form replaced with SchoolYearFormDialog (dialog pattern); applyFilters preserveScroll→preserveUrl fix — 2026-04-10
- [x] ADJ-GR — Grades.vue: inline form replaced with GradeFormDialog (dialog pattern); applyFilters router.post→router.get with preserveUrl fix — 2026-04-10\n- [x] ADJ-F9 — events/Create.vue + Edit.vue: has_no_date checkbox hidden-input pattern fixed (v-if="hasNoDate" hidden value="1"); overlay divs removed; shadcn Input used for date field; Unidade field has bg-muted/50; dead code (Plus/Search/Card/filteredRooms/toggleRoom/isRoomSelected) removed from Create.vue — 2026-04-10
- [x] 5.4 — Reviewer pass on Stage 5: StoreTaskRequest prepareForValidation (assigned_user_uuid→id) · OutcomeProcessorService nested DB::transaction removed · Opportunity interface + renitente_count · Show.vue as any casts removed · DECISIONS_LOG entry — 2026-04-10
- [x] ADJ-KAN — KanbanColumn.vue: VueDraggable drag-and-drop between columns with PATCH update_status; terminal columns (matricula/recusado) locked (pull/put=false) — 2026-04-10
- [x] ADJ-BIRTH — Student interface: data_nascimento→birth_date; Edit.vue student_birth_date default-value fixed — 2026-04-10
- [x] ADJ-CEP — Create.vue + Edit.vue: submit disabled on cepError/isLoadingCep; handleZipCodeInput clears cepError on input — 2026-04-10
- [x] 4.9B — Opportunity status view backend: migration school_unit_id · Opportunity model schoolUnit relation · OpportunityService list() new filters (lead_source, registration_type, segment, school_unit, date_from/to) + listByStatus() kanban · OpportunityController index() kanban/list view · OpportunityResource · opportunities.index route GET-only — 22 tests — 2026-04-10
- [x] 4.9F — Opportunity Index kanban/list view frontend: lib/opportunityStatus.ts · KanbanColumn/KanbanBoard/OpportunityCard components · Index.vue view toggle + new filters (lead_source, segment, school_unit, registration_type, date_from/to) + applyFilters router.get preserveUrl + destroy no school_uuid · SchoolUnit uuid + Opportunity school_unit + KanbanColumns types in crm.ts — 2026-04-10
- [x] ADJ-OPP — Opportunity adjustments: vue-draggable-plus kanban drag (PATCH status endpoint + VueDraggable @add handler) · student birth_date fix (resource + UpdateRequest + service + Edit.vue data_nascimento→birth_date) · CEP inválido blocks submit · Student.guardian? in crm.ts · Create.vue selects uuid fix — 2026-04-10
- [x] ADJ-CPFF — Opportunity filter: student_cpf + guardian_cpf filters added to OpportunityService::list() (whereHas) · OpportunityController::index() builds $filters array + passes as 'filters' prop · Index.vue: filters prop type + localFilters initialised from props.filters + formatCpf inline + two masked CPF inputs (DOM-mutation pattern) · currentFilters/hasActiveFilter/filterCount/applyFilters updated — 2026-04-10
- [x] ADJ-CPF — Opportunity form CPF improvements: mod-11 client-side validation in useCpfLookup (no network call on invalid CPF); StudentController::lookup() returns guardian in JSON; guardian CPF field uses second useCpfLookup instance (type: guardian) replacing manual fetch/blur pattern; student onFound fills guardian fields from student.guardian — 2026-04-10
- [x] ADJ-UX1 — Opportunity UX fixes: Edit.vue 5 readonly fields (history/student_name/student_cpf/guardian_name/guardian_cpf) with bg-muted/50 cursor-not-allowed, removed useCpfLookup + dead vars; OpportunityCard.vue + tasks/Index.vue link to show instead of edit; Index.vue Nova Oportunidade button moved into header flex row — 2026-04-10
- [x] ADJ-SHOW — Show.vue redesenho: pipeline header (funnel_stages + days_in_stage) · bloco lead (guardian+student side-by-side) · sidebar 3 abas (Histórico/Próximas Tarefas/Mais Informações) · timeline teal · cards Endereço+Indicação · TaskType.label() · TaskResource task_type · OpportunityController::show() schoolUnit+funnel_stages+days_in_stage · FunnelStage in crm.ts — 2026-04-10
- [x] ADJ-SHOW2 — Show.vue layout adjustments: days_in_stage uses created_at; responsible_user name in pipeline header; Edit button moved into pipeline card below progress bar; guardian/student names shown to the RIGHT of contact/data info with User icon and border-l separator; usePage/authUser/Auth imports removed — 2026-04-10
- [x] REBRAND — Operis visual rebrand: Eurostile fonts · blue brand palette (hsl 234) · favicon.svg 8-petal wheel · AppLogoIcon.vue · AppLogo.vue PNG fallback · AuthSplitLayout.vue slate/blue overlay · Login.vue new headline+Target icon — 2026-04-10
- [x] REBRAND — Identidade visual Operis CRM: paleta azul `#2D3AE0`, Eurostile, logos/favicon nativos, login redesenhado — 2026-04-10
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
| 2026-04-10 | Brand identity: Operis blue `#2D3AE0`, font Eurostile LT Pro Unicode — assets in `public/images/operis-avatar-*.png`, `public/fonts/`, background `public/images/auth-bg.jpg`; semantic amber (warnings, status badges) preserved untouched |

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
| 2026-04-09 | `opportunities/Create.vue` | `Guardian` interface uses English field names (`phone`, `zip_code`, `street`, `number`, `neighborhood`, `city`, `state`) — use these in `fillGuardianFields()`, never the old Portuguese names |
| 2026-04-09 | `EventTest.php` | 419 CSRF failures on POST/PUT/DELETE are pre-existing throughout the test suite — GET tests pass; the 419 pattern is systemic, not module-specific |
| 2026-04-09 | `*.vue` (forms) | Boolean checkbox in Inertia `<Form>`: add `<input type="hidden" name="field" value="0" />` BEFORE the Checkbox — unchecked sends 0, checked overrides with 1 |
| 2026-04-09 | `RoomController.php` | `back()` em store/update/destroy — RoomFormDialog é usado em múltiplos contextos (Rooms settings + Events form); `to_route()` causaria redirect indesejado |
| 2026-04-09 | `OpportunityService.php` | Ao injetar novo serviço no construtor, verificar se `syncWithoutDetaching()` pré-existente viola CLAUDE.md — substituir por `exists() + attach()` |
| 2026-04-09 | `opportunities/Edit.vue` | Estava usando shadcn `<Tabs>` (único arquivo); padronizado para botões manuais + `v-show` igual ao restante do projeto |
| 2026-04-10 | `EventTypeToggleDialog.vue` | Password-confirmation toggle uses `router.post` with `onError: (errors: Record<string, string>)` — no `any`; mirrors `ConfirmRestoreModal` pattern |
| 2026-04-10 | `ActiveSchoolControllerTest.php` | `SetActiveTenant` global middleware aborts 403 for non-cross-tenant users with no school in session — use `$this->withoutMiddleware(SetActiveTenant::class)` when testing validation that lives past the middleware |
| 2026-04-10 | `SchoolFactory`, all test helpers | `schools.slug` is NOT NULL — `SchoolFactory` and every `School::create()` in tests must include `'slug'`; observer no longer auto-generates it |
| 2026-04-09 | `SchoolStoreRequest`, `SchoolUpdateRequest`, `OpportunityService` | Masked fields (CEP, CNPJ, CPF, phone) must be stored with display mask — never strip non-digits before persisting; strip only for API calls or mod-11 validation |
| 2026-04-10 | `SchoolYearStoreRequest`, tests | Column rename (inicio→start, fim→end) must be applied consistently to requests, tests, TS interfaces AND a nullable migration must be created when docs say "nullable migration" — check all four touch points |
| 2026-04-10 | `OpportunityService::list()` | Use is_numeric() to detect already-resolved IDs vs UUID strings — avoids double DB lookup in listByStatus() loop |
| 2026-04-10 | `KanbanColumn.vue` | IntersectionObserver sentinel `ref` must be declared as `ref<HTMLDivElement | null>(null)` and checked in onMounted — router.reload only:kanban_columns, append items via watch on current_page |
| 2026-04-10 | `Student` model | DB column is `date_of_birth` (cast to `date`) — API exposes it as `birth_date`; `UpdateOpportunityRequest` uses `student_birth_date`, service maps to `date_of_birth` on update |
| 2026-04-10 | `useCpfLookup.ts`, `Create.vue`, `Edit.vue` | Guardian CPF handled via second `useCpfLookup` instance (type: `guardian`) — never via manual `fetch`/`@blur`; mod-11 validation runs client-side before any network call |
| 2026-04-10 | `opportunities/Edit.vue` | Student/guardian name+CPF fields are always `readonly` (native `<input>` with `:value`) — `useCpfLookup` must NOT be used in Edit; CPF lookup only belongs in Create |

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
