# CLAUDE.md

> Instruções operacionais para o Claude Code neste projeto.
> Regras de PHP, Laravel, Pest, Pint, Inertia, Wayfinder e Tailwind são injetadas
> automaticamente pelo Laravel Boost MCP. Este arquivo declara apenas o delta do projeto.

---

## Leitura obrigatória antes de qualquer ação

1. `PROJECT_CONTEXT.md` — stack, models, convenções, entidades do domínio CRM
2. `TECHNICAL_PLAN.md` — estrutura de pastas, schema, rotas, fluxos

Se algum desses arquivos estiver ausente ou com campos em branco, solicite ao
desenvolvedor que preencha antes de prosseguir.

---

## Agents

Os agents vivem em `.claude/agents/`. Cada um tem escopo estrito.

| Agent | Arquivo | Faz | Nunca faz |
|---|---|---|---|
| Prompt Engineer | `prompt-engineer.md` | Transforma ideia informal em prompt estruturado | Implementa código ou toma decisões técnicas |
| Planner | `planner.md` | Planeja, decompõe, lista arquivos a criar/editar | Implementa código |
| Backend Dev | `backend-developer.md` | Arquivos PHP | Toca em Vue/TS |
| Frontend Dev | `frontend-developer.md` | Arquivos Vue/TS | Toca em PHP |
| Reviewer | `reviewer.md` | Audita, aprova ou rejeita com checklist | Implementa código |

### Fluxo obrigatório

```
[ideia informal] → Prompt Engineer → prompt estruturado
                                          ↓
                   Planner → aprovação humana → Backend Dev → Reviewer → Frontend Dev → Reviewer
```

O Prompt Engineer é opcional mas recomendado para qualquer solicitação descrita em linguagem natural.
Nunca pule as etapas de aprovação e revisão. Se o Reviewer rejeitar, o agent responsável corrige antes de avançar.

---

## Regras de arquitetura específicas deste projeto

As convenções completas estão em `PROJECT_CONTEXT.md` (seção 6) e `TECHNICAL_PLAN.md` (seção 5).
Os itens abaixo são os desvios mais críticos — revisados pelo Reviewer em toda auditoria.

### Multi-tenancy
- Todo model de domínio usa a trait `BelongsToTenant` — nunca query sem escopo de tenant
- O `GlobalScope` de tenant é aplicado automaticamente — nunca filtre `school_id` manualmente em queries simples
- Controllers de tenant nunca recebem `school_id` via request — sempre via `auth()->user()->currentSchool()`
- Controllers de Admin/Operação (cross-tenant) vivem em `Http/Controllers/Admin/`
- Controllers de tenant vivem em `Http/Controllers/Tenant/`

### Tarefas e tabulações (núcleo do sistema)
- `OutcomeProcessorService` é o único ponto que executa ações de tabulação — nunca dispersar essa lógica
- `RenitenteCycleService` é o único ponto que calcula delays do ciclo Renitente
- Ao concluir uma tarefa: sempre dentro de `DB::transaction()` — Task + Outcome + ações são atômicos
- Uma oportunidade nunca pode ter duas tarefas `status = open` simultaneamente — validar no Service

### Arquitetura geral
- Controllers nunca contêm lógica de negócio — delegam ao Service
- `owen-it/laravel-auditing` obrigatório em todos os models de domínio, sem exceção
- UUIDs em URLs públicas — nunca IDs numéricos expostos
- `attach()`/`detach()` com diff em pivots auditadas — nunca `sync()`
- `LengthAwarePaginator` em listagens — nunca `->get()` sem paginação
- `to_route()` para redirecionamentos — nunca `redirect()->route()`
- `ValidationException` em fluxos Inertia — nunca `back()->withErrors()`
- i18n via `laravel-vue-i18n` é opcional — strings hardcoded são permitidas; Wayfinder é obrigatório
- Verificar `resources/js/components/ui/` e `resources/js/composables/` antes de criar novos

---

## Mapeamento obrigatório de erros

Todo erro encontrado — pelo Reviewer, Backend ou Frontend Developer — deve ser documentado
em `PROJECT_CONTEXT.md` (seção 14) automaticamente, sem que o desenvolvedor precise solicitar.

- O Reviewer documenta ao auditar; os Developers documentam ao corrigir desvios ainda não listados
- Nunca duplicar entradas existentes — verificar antes de escrever
- Formato padrão: título + descrição + exemplo errado + exemplo correto

---

## Validações obrigatórias ao encerrar qualquer tarefa

```bash
# Backend
php artisan test --compact
vendor/bin/pint --dirty --format agent

# Frontend
npm run lint
npm run format
```

Testes vermelhos ou erros de lint = tarefa não concluída. Sem exceções.

---

## Retomada após interrupção

```
Leia PROJECT_CONTEXT.md e TECHNICAL_PLAN.md.
Leia os arquivos existentes em app/, database/ e resources/js/.
Me informe:
  1. O que já foi implementado
  2. O que ainda falta
  3. Por onde retomar

Aguarde minha confirmação antes de continuar.
```