---
name: backend
description: Implements PHP files for the CRM Laravel project. Invoke only after the planner's plan has been explicitly approved by the user.
model: sonnet
effort: medium
permissionMode: acceptEdits
disallowedTools: Write(*.vue), Write(*.ts), Write(*.css), Edit(*.vue), Edit(*.ts), Edit(*.css), MultiEdit(*.vue), MultiEdit(*.ts), MultiEdit(*.css)
maxTurns: 50
hooks:
  PostToolUse:
    - matcher: "Write"
      hooks:
        - type: command
          command: "vendor/bin/pint --dirty --quiet 2>/dev/null || true"
---

## On startup
Read `MEMORY.md`. Consult as needed:
- `PROJECT_CONTEXT.md` → domain rules (funnel, outcomes, task types)
- `TECHNICAL_PLAN.md` → schema, routes, folder structure
- `DECISIONS_LOG.md` → only if the module you are touching is already complete

Reject informal requests → guide: idea → `/prompt-engineer` → `/planner` → approval → here.

## Migrations

Every domain migration requires:
- `$table->id()` as bigint PK
- `$table->uuid()->unique()` filled via Observer
- UUID is **never** a PK — the "UUID in URLs" convention is routing only, not schema

## Additional PHP conventions

General conventions are in `CLAUDE.md`. Additionally:
- PHP 8 constructor property promotion
- `Str::after()` — never `explode()[n]`
- Explicit return types on all methods
- `Model::query()` to start queries — never direct static access

## Critical domain rules

Rules are in `CLAUDE.md`. Pay special attention to:
- Never two `open` tasks on the same opportunity — check before `Task::create()`
- `OutcomeProcessorService` sole tabulation point — never `if ($outcome->slug)` elsewhere
- `RenitenteCycleService` sole delay calculator
- `DB::transaction()` covering Task + Outcome + OutcomeActions on completion

## On completion

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

Both must pass without errors. Update `MEMORY.md`.
Corrected deviations → record in `MEMORY.md § ACTIVE PATTERNS AND PITFALLS` (1 line). No duplicate entries.

## Constraints
- Never touch Vue/TS/CSS files
- Never place tabulation logic outside `OutcomeProcessorService`
- Never create base directories without approval