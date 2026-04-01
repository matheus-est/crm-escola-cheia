# MEMORY.md
> Memória incremental do projeto. Atualizada automaticamente pelos agents após cada sessão.
> Leia **apenas este arquivo** ao retomar trabalho. Consulte PROJECT_CONTEXT.md e TECHNICAL_PLAN.md só se precisar de detalhe específico.

---

## ESTADO ATUAL

**Última sessão:** 2026-04-01
**Próxima tarefa:** Etapa 1.x — School (tenant)

### O que está pronto
- [x] Etapa 0.1 — Ambiente (MySQL, Reverb, laravel-auditing)
- [x] Etapa 0.2 — Trait BelongsToTenant · Middlewares SetActiveTenant e EnsureTenantAccess
- [x] Etapa 0.3 — RoleSeeder (5 roles) · CrmPermissionSeeder (20 permissions, 4 módulos) · User::isCrossTenant() · User::isMaster() · User::currentSchool()
- [x] Etapa 0.4 — CheckRole middleware · alias `role:` · routes/admin.php · routes/tenant.php
- [x] Etapa 0.5 — Segment · Outcome · OutcomeAction (migrations + models + seeders)

### O que está pendente (por ordem de execução)
- [ ] 1.x — School (tenant)
- [ ] 2.x — Entidades de suporte
- [ ] 3.x em diante — ver DEVELOPMENT_PLAN.md

---

## DECISÕES TOMADAS

| Data | Decisão |
|---|---|
| 2026-04-01 | RabbitMQ removido — sem driver compatível com Laravel 13. `QUEUE_CONNECTION=database` |
| 2026-04-01 | Perfis Gestor/Comercial são roles normais do ACL — sem ENUM em `users`. Vínculo via `school_user` |
| 2026-04-01 | `school_user` pivot sem coluna `role` — perfil definido por `users.role_id` |
| 2026-04-01 | CrmPermissionSeeder separado do PermissionSeeder — seeders do boilerplate não tocados |
| 2026-04-01 | Roles identificados por `name` (sem slug) — `Role::updateOrCreate(['name' => ...])` |
| 2026-04-01 | `currentSchool()` usa `class_exists(School::class)` — retorna null até Etapa 1.x sem lançar exception |
| 2026-04-01 | Teste TenantScopeTest atualizado de `assertStatus(503)` para `assertStatus(403)` — comportamento correto pós-Etapa 0.3 (método existe, mas School não existe ainda) |
| 2026-04-01 | `CheckRole` criado com alias `role:` em bootstrap/app.php — rotas admin.php e tenant.php carregadas via `then:` no `withRouting()` |
| 2026-04-01 | Testes de tenant routing limitados até Etapa 1.x — School não existe, todos authenticated requests → 403 via SetActiveTenant |
| 2026-04-01 | OutcomeSeeder resulta em 41 outcomes (não 30+10=40) — o spec diz "30 normais" mas lista 31 entradas nas tabelas; implementação segue as tabelas do spec |

---

## PADRÕES DESCOBERTOS EM SESSÕES ANTERIORES

> Erros que já aconteceram e foram corrigidos. Agents: consultem antes de implementar.

| Data | Arquivo | Regra em 1 linha |
|---|---|---|
| 2026-04-01 | User.php | `currentSchool()` deve usar `class_exists()` para guardar referência a model não-existente — evita fatal error até Etapa 1.x |
| 2026-04-01 | RoleSeeder.php | Roles do boilerplate usam `sync()` — dívida técnica não corrigida nesta etapa; novos roles CRM não usam `sync()` |
| 2026-04-01 | CheckRole.php | Middleware parametrizado via `string ...$roles` — alias `role:` registrado em bootstrap/app.php; comparação por `$user->role?->name` (string capitalizada) |
| 2026-04-01 | TenantRoutingTest.php | Testes de tenant são pragmáticos até Etapa 1.x — School não existe, `currentSchool()` retorna null, SetActiveTenant aborta 403 antes de registrar `tenant.school_id` |
| 2026-04-01 | OutcomeSeeder.php | `->delete()` + `->create()` nas actions de cada outcome — nunca `sync()` — garante idempotência sem duplicatas |

---

## INSTRUÇÕES PARA AGENTS

### Ao iniciar uma sessão
1. Leia este arquivo (`MEMORY.md`) — é suficiente para 90% das retomadas
2. Se precisar de detalhes de um model, rota ou convenção → leia `PROJECT_CONTEXT.md` ou `TECHNICAL_PLAN.md`
3. Nunca releia tudo por precaução — custo alto, ganho zero

### Ao encerrar uma sessão (obrigatório)
Atualize as seções deste arquivo:
- **ESTADO ATUAL**: marque o que foi concluído, atualize "Próxima tarefa"
- **PADRÕES DESCOBERTOS**: adicione erros encontrados e corrigidos (1 linha cada, sem exemplos de código)
- **DECISÕES TOMADAS**: adicione se alguma decisão de arquitetura foi tomada

### Formato de atualização de padrões
```
| DATA | ARQUIVO | Regra em 1 linha |
```
Exemplo:
```
| 2026-04-01 | User.php | Auth::logout() antes de forceDelete() — save() do token reinsere o usuário |
```
