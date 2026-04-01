# CLAUDE.md
> Instruções operacionais para Claude Code. Convenções PHP/Vue/Laravel injetadas pelo Boost MCP.

## Leitura ao iniciar
1. `MEMORY.md` — estado atual, próxima tarefa, padrões descobertos
2. `PROJECT_CONTEXT.md` — se precisar de detalhe sobre domínio ou convenção
3. `TECHNICAL_PLAN.md` — se precisar de schema, rota ou estrutura de pasta

## Agents (`.claude/agents/`)
| Agent | Faz | Nunca faz |
|---|---|---|
| `prompt-engineer` | Transforma ideia em prompt estruturado | Implementa código |
| `planner` | Decompõe em subtasks com arquivos e critérios | Implementa código |
| `backend-developer` | Arquivos PHP | Toca em Vue/TS |
| `frontend-developer` | Arquivos Vue/TS | Toca em PHP |
| `reviewer` | Audita, aprova ou rejeita | Implementa código |

Fluxo: `ideia → prompt-engineer → planner → [aprovação] → backend → reviewer → frontend → reviewer`

## Regras críticas deste projeto (delta do boilerplate)

**ACL:** sistema do boilerplate intacto — sem ENUM em `users` — roles via `RoleSeeder` — gestor/comercial vinculados via `school_user`

**Tenant:** `BelongsToTenant` em todo model de domínio — `school_id` nunca da request — `SchoolService::attachUser()` único ponto de insert em `school_user`

**Tarefas:** nunca duas `open` na mesma oportunidade — conclusão sempre em `DB::transaction()` — `OutcomeProcessorService` único ponto de tabulação

## Ao encerrar qualquer tarefa
1. `php artisan test --compact` + `vendor/bin/pint --dirty --format agent`
2. `npm run lint && npm run format`
3. Atualizar `MEMORY.md` (estado + padrões descobertos)

## Retomada após interrupção
```
Leia MEMORY.md. Me informe: (1) o que está pronto, (2) o que falta, (3) por onde retomar.
Aguarde confirmação antes de continuar.
```