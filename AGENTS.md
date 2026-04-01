# AGENTS.md
> Instruções para agentes. Leia MEMORY.md ao iniciar — não releia tudo por precaução.

## Stack
PHP 8.4 · Laravel 13 · Fortify · Inertia v2 · Vue 3 · Tailwind v4 · reka-ui · Pest v4 · Pint · ESLint+Prettier · MySQL

## Arquitetura
```
Request → FormRequest → Controller (fino) → Service (lógica) → Model → Inertia response
Admin/    cross-tenant  |  Tenant/  tenant-scoped  |  Public/  sem auth
```

## Regras críticas
- ACL: `roles → permissions → Gate` intacto — sem ENUM em `users` — gestor/comercial via `school_user`
- `SchoolService::attachUser()` — único insert em `school_user` — só aceita role gestor/comercial
- `BelongsToTenant` em todo model de domínio — `school_id` nunca da request
- Nunca duas tarefas `open` na mesma oportunidade — conclusão em `DB::transaction()`
- `OutcomeProcessorService` único ponto de tabulação — `RenitenteCycleService` único ponto de delay

## Convenções (resumo)
`declare(strict_types=1)` · `casts()` método nunca propriedade · `=== null` · `array_key_exists()` · `attach()`/`detach()` nunca `sync()` · `LengthAwarePaginator` · `to_route()` · `ValidationException` · UUID em URLs · `#[ObservedBy]` · `config()` nunca `env()` · `<script setup lang="ts">` · Wayfinder · `useForm` Inertia

## Checklist Reviewer — Backend
- [ ] Controller delega ao Service — sem lógica inline
- [ ] FormRequest para toda validação
- [ ] `ValidationException` — sem `back()->withErrors()`
- [ ] `to_route()` — sem `redirect()->route()`
- [ ] `BelongsToTenant` no model (se domínio do tenant)
- [ ] `school_id` não vem da request
- [ ] `#[ObservedBy]` no model — sem `observe()` no SP
- [ ] UUID em URLs — sem ID numérico exposto
- [ ] `LengthAwarePaginator` em listagens
- [ ] `attach()`/`detach()` — sem `sync()`
- [ ] `declare(strict_types=1)` em todo arquivo PHP
- [ ] `owen-it/laravel-auditing` no model
- [ ] `DB::transaction()` na conclusão de tarefa
- [ ] Sem duas tarefas `open` na mesma oportunidade
- [ ] `php artisan test --compact` — zero falhas
- [ ] `vendor/bin/pint --dirty` — zero erros

## Checklist Reviewer — Frontend
- [ ] `<script setup lang="ts">` — sem Options API
- [ ] Sem `any` explícito ou implícito
- [ ] Wayfinder em toda navegação — sem URL hardcoded
- [ ] `useForm` Inertia — sem `fetch`/`axios`
- [ ] Permissões via `$page.props.auth.permissions`
- [ ] `npm run lint && npm run format` — zero erros

## Ao encerrar sessão
Atualizar `MEMORY.md`: marcar concluídos · adicionar padrões descobertos · atualizar próxima tarefa.