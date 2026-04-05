# MEMORY.md

> Read at the start of every session. Self-sufficient for 90% of tasks.
> Update only the sections that changed when closing a session.

---

## CURRENT STATE

**Last session:** 2026-04-05
**Next task:** Stage 5.x — Tasks and Outcomes (service, requests, policy, controller, routes, HTTP tests)

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
- [ ] **5.x — Tasks and Outcomes 🔴**
- [ ] 6–11 — Notifications · Events · Form · Calendar · Reports · LGPD

---

## ARCHITECTURE DECISIONS

| Date | Decision |
|---|---|
| 2026-04-01 | RabbitMQ removed — `QUEUE_CONNECTION=database` |
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
| 2026-04-03 | `Route::bind('school_uuid')` in `AppServiceProvider` — unknown UUID returns 404 |
| 2026-04-03 | `useCpfLookup` uses `fetch` — only authorized case outside `useForm` (GET lookup, pure JSON) |

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
| 2026-04-03 | `*Service.php` | `list()` passes explicit `$school` even with `TenantScope` active — ensures isolation without relying on context |
| 2026-04-03 | `OpportunityModelTest.php` | Pest 4: `toThrow(\Throwable::class)` fails — use manual try/catch with boolean flag |
| 2026-04-03 | `*.vue` (tenant) | Props receive `school` from controller — use `props.school.uuid` for Wayfinder; never `setUrlDefaults` |
| 2026-04-05 | \`*Service.php\` | Non-blocking warnings: add \`hasClosed*\` helper on service; controller checks after \`create()\` and flashes \`warning\` vs \`success\` — never block creation |\n| 2026-04-05 | \`*.vue\` (ui) | No \`Textarea\` component in \`ui/\` — use plain \`<textarea>\` with inline Tailwind classes matching shadcn Input style |

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
