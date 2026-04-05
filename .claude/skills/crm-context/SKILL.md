---
name: crm-context
description: Provides structured prompt examples for the CRM project. Auto-invoked when the prompt-engineer agent is generating a structured prompt and needs reference examples. Not a user-invocable command.
user-invocable: false
disable-model-invocation: false
allowed-tools: Read
---

# CRM Prompt Examples

Reference examples for the prompt-engineer agent when building structured prompts.
Do not copy verbatim — adapt scope, files, and rules to the actual task.

---

## Example 1 — New feature with kanban (→ /planner)

**Input:** "I want to create the opportunities module with the kanban"

**Reasoning:** new feature + multiple layers → `/planner`

```markdown
## Context
Opportunity is the central CRM entity. Schema in TECHNICAL_PLAN.md.
Tenant routes in routes/tenant.php. None of the layers exist yet.

## Goal
Plan all artifacts for the Opportunities module: migration, model, service,
controller, kanban, and filtered list.

## Scope — what to do

**Backend phase:**
1. Migration create_opportunities_table — schema per TECHNICAL_PLAN.md
2. app/Enums/OpportunityStatus.php — cadastro_inicial, agendamento, visita, matricula, recusado
3. app/Models/Opportunity.php — BelongsToTenant · #[ObservedBy] · auditing · Enum casts
4. app/Observers/OpportunityObserver.php — generates UUID on creating
5. app/Policies/OpportunityPolicy.php — Comercial sees only own · Gestor sees all tenant
6. app/Services/Opportunity/OpportunityService.php:
   - list(School, array $filters): LengthAwarePaginator
   - create(School, array $data): Opportunity
   - update(Opportunity, array $data): Opportunity
   - destroy(Opportunity): void
7. app/Http/Requests/Opportunity/OpportunityStoreRequest.php
8. app/Http/Requests/Opportunity/OpportunityUpdateRequest.php
9. app/Http/Controllers/Tenant/OpportunityController.php — delegates to Service
10. Routes in routes/tenant.php
11. OpportunityFactory
12. Tests: creation, filtered listing, comercial cannot see others' opportunities,
    forged school_id is ignored, terminal status cannot be changed

**Frontend phase (after backend review):**
13. resources/js/types/crm.ts — Opportunity, OpportunityFilters interfaces
14. Composable useOpportunityFilters.ts
15. KanbanBoard.vue → KanbanColumn.vue → OpportunityCard.vue
16. pages/opportunities/Index.vue — kanban (default) + list (alternative)
17. pages/opportunities/Create.vue — with student/guardian CPF lookup
18. pages/opportunities/Show.vue — details + task panel

## Scope — what NOT to do
- Do not implement tasks/outcomes in this phase — separate module
- Do not create full Student/Guardian CRUD — CPF lookup only
- Do not use sync() on any pivot
- Do not expose numeric school_id in Inertia props

## Layer contracts
**Props OpportunityController@index:**
```php
return Inertia::render('opportunities/Index', [
    'opportunities' => OpportunityResource::collection($paginator),
    'filters'       => $request->only(['status','user_id','segment_id','school_year_id']),
    'school_years'  => SchoolYear::query()->where('school_id', $school->id)->get(['id','uuid','nome']),
    'commercials'   => $school->users()->get(['users.uuid','users.name']),
    'segments'      => $school->segments()->get(['segments.id','segments.name']),
]);
```
**crm.ts types:**
```typescript
export interface Opportunity {
  uuid: string
  status: string
  student: Student
  guardian: Guardian
  user: User | null
  school_year: SchoolYear
  segment: Segment | null
  grade: Grade | null
  lead_source: LeadSource | null
  created_at: string
}
```

## Critical rules for this task
- school_id never from request — always from auth()->user()->currentSchool()
- Comercial sees only user_id = auth()->id() — filtered in Service, not Controller
- Terminal statuses immutable — no update on terminated opportunities
- LengthAwarePaginator in listing — never ->get()

## Dependencies
Migrations for schools, students, guardians, school_years, segments, grades, lead_sources.

## Validation on completion
```bash
php artisan test --compact --filter=Opportunity
vendor/bin/pint --dirty --format agent
npm run lint && npm run format
```
- [ ] Authenticated comercial sees only their opportunities
- [ ] Payload with forged school_id is ignored
- [ ] Terminal status cannot be changed via update
- [ ] Kanban renders columns by status with per-column scroll
- [ ] Kanban ↔ list toggle works without reload
```

**→ Send to `/planner`. Wait for approval before proceeding.**

---

## Example 2 — Task and outcome core (→ /backend)

**Input:** "the comercial submits a task outcome and the system must automatically create the next one"

**Reasoning:** system core — involves OutcomeProcessorService, RenitenteCycleService, Actions. Already planned. → `/backend`

```markdown
## Context
Task completion flow in MEMORY.md § CORE.
TaskService::complete() already exists and calls OutcomeProcessorService::process().
Full outcome map in PROJECT_CONTEXT.md § OUTCOME MAP.

## Goal
Implement OutcomeProcessorService, RenitenteCycleService, and atomic Actions.

## Scope — what to do

1. app/Actions/Task/CreateTaskAction.php:
   - Receives Opportunity, TaskType, array $options
   - Validates: Task::query()->where('opportunity_id')->where('status','open')->exists() → DomainException
   - Calls RenitenteCycleService when $options['renitente'] === true
   - Creates and returns Task

2. app/Services/Task/RenitenteCycleService.php:
   - resolveDelay(Opportunity): CarbonInterface
   - count 0 → +1h, increments to 1
   - count 1–5 → +3h, increments
   - count 6 → RenitenteLimitReachedException, resets to 0
   - Persists renitente_count inside the existing DB::transaction()

3. app/Actions/Task/CancelPendingTasksAction.php:
   - Receives Opportunity, string $type
   - Bulk update: status=cancelled, cancelled_at=now()

4. app/Actions/Opportunity/MoveOpportunityStatusAction.php:
   - Receives Opportunity, string $status
   - Validates it is not a terminal status → ValidationException if it is
   - $opp->update(['status' => $status])

5. app/Services/Task/OutcomeProcessorService.php:
   - process(Task, Outcome, array $payload = []): array
   - Iterates $outcome->actions (eager loaded, ordered by order)
   - Per action_type:
     - create_task → CreateTaskAction; catch RenitenteLimitReachedException
     - move_status → MoveOpportunityStatusAction
     - cancel_tasks → CancelPendingTasksAction
     - open_window → adds ['open_window' => $action->payload['window']] to return
   - Returns array with open_window (string|null)

6. Required Pest tests:
   - Renitente count 0 → due +1h
   - Renitente count 5 → due +3h, count becomes 6
   - Renitente count 6 → no new task, resets
   - CreateTaskAction throws DomainException if open task already exists
   - compareceu_agendamento → move visita + create provavel_matricula
   - refusal without category → ValidationException
   - transaction rolled back if any Action fails

## Scope — what NOT to do
- No if/switch by slug outside OutcomeProcessorService
- No task creation without checking open uniqueness

## Critical rules for this task
- OutcomeProcessorService sole tabulation point — zero $outcome->slug outside it
- RenitenteCycleService sole delay calculator
- Entire chain inside the existing DB::transaction() in TaskService
- open_window returned as array → forwarded via Inertia props to frontend

## Dependencies
TaskService::complete() already implemented. Task, Outcome, OutcomeAction models exist.

## Validation on completion
```bash
php artisan test --compact --filter=Outcome
php artisan test --compact --filter=Renitente
php artisan test --compact --filter=Task
vendor/bin/pint --dirty --format agent
```
- [ ] 7 Renitente scenarios covered
- [ ] Transaction rolled back on any Action error
- [ ] Correct open_window for each outcome that opens a modal
```

**→ Send to `/backend`. No planner needed — already planned.**

---

## Example 3 — Cross-tenant CRUD (→ /planner)

**Input:** "school registration with CNPJ validation"

**Reasoning:** new cross-tenant feature, controller in Admin/, route in admin.php → `/planner`

```markdown
## Context
School is the root tenant — no BelongsToTenant. Schema in TECHNICAL_PLAN.md.
Admin routes in routes/admin.php. Unique slug used in the public capture form URL.

## Goal
Implement School CRUD in the admin panel with CNPJ validation via BrasilAPI.

## Scope — what to do

**Backend phase:**
1. Migration create_schools_table — schema per TECHNICAL_PLAN.md
2. app/Models/School.php — no BelongsToTenant · auditing · #[ObservedBy]
3. app/Observers/SchoolObserver.php — UUID + slug on creating (numeric suffix on collision)
4. app/Services/School/SchoolService.php:
   - lookupCnpj(string): array — BrasilAPI, throws CnpjNotFoundException on 404
   - list(array $filters): LengthAwarePaginator
   - create(array): School
   - update(School, array): School — changed slug → warn frontend via flash
   - destroy(School): void — Master only
5. app/Policies/SchoolPolicy.php — destroy: Master only
6. Requests, Controller, Routes, Tests

**Frontend phase:**
7. pages/admin/Schools/Index.vue, Create.vue, Edit.vue
8. composables/useCepLookup.ts — 400ms debounce

## Scope — what NOT to do
- School does not use BelongsToTenant — it is the root tenant
- CNPJ lookup in Service — never in Controller
- Slug generated in Observer — never in Service or Controller

## Critical rules for this task
- Only Master can delete School — validate in Policy
- Slug change displays alert: "Changing the slug breaks the capture form link"

## Dependencies
None — School is the root entity.
```

**→ Send to `/planner`. Wait for approval before proceeding.**