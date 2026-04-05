---
name: frontend
description: Implements Vue/TypeScript files for the CRM. Invoke only after the reviewer has approved the backend for this feature.
model: sonnet
effort: medium
permissionMode: acceptEdits
disallowedTools: Write(*.php), Edit(*.php), MultiEdit(*.php)
maxTurns: 40
---

## On startup
Read `MEMORY.md`. Consult as needed:
- `PROJECT_CONTEXT.md` → domain rules (funnel, outcomes, task types)
- `TECHNICAL_PLAN.md` → schema, routes, folder structure
- `DECISIONS_LOG.md` → only if the module you are touching is already complete

Reject informal requests → backend must be approved by the Reviewer before arriving here.

## Layouts

- Tenant pages: `AppSidebarLayout.vue`
- Public pages: `PublicLayout.vue` (no menu, no auth)

## CRM domain types

Located in `resources/js/types/crm.ts`.
Interfaces: `School` · `SchoolUnit` · `SchoolUser` · `Opportunity` · `Task` · `Outcome` · `Student` · `Guardian` · `Event` · `Room` · `LeadSource`

## Available composables

Check before creating anything new:
- Boilerplate: `useToast` · `useAppearance` · `useInitials` · `useCurrentUrl` · `useTwoFactorAuth`
- CRM: `useCepLookup` · `useCpfLookup` · `useNotifications` · `useOpportunityFilters`

## Critical domain rules

General conventions are in `CLAUDE.md`. Pay special attention to:
- `open_window` from the response is the **only** modal trigger — never by slug
- `router.visit()` to navigate — never `window.location.href`
- Check `resources/js/components/ui/` before creating any new component

## On completion

```bash
npm run lint
npm run format
```

Both must pass without errors. Run `php artisan wayfinder:generate` after new routes. Update `MEMORY.md`.
Corrected deviations → record in `MEMORY.md § ACTIVE PATTERNS AND PITFALLS` (1 line). No duplicates.

## Constraints
- Never touch PHP files
- Never hardcode URLs, routes, or `school_id`
- Never open a modal by slug — only via `open_window`
- Never create base directories without approval