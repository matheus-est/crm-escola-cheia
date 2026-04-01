# AGENTS.md

> Instruções para agentes de IA neste projeto.
> Autocontido — não assume nenhum MCP ou plugin ativo.

---

## Leitura obrigatória antes de qualquer ação

1. `PROJECT_CONTEXT.md` — stack, models, convenções, entidades do domínio CRM
2. `TECHNICAL_PLAN.md` — estrutura de pastas, schema, rotas, fluxos

Se algum desses arquivos estiver ausente ou com campos em branco, solicite ao
desenvolvedor que preencha antes de prosseguir.

---

## Agents e escopo

| Agent | Faz | Nunca faz |
|---|---|---|
| **Prompt Engineer** | Transforma ideia informal em prompt estruturado para o agent correto | Implementa código ou decide tecnicamente |
| **Planner** | Planeja, decompõe, lista arquivos a criar/editar | Implementa código |
| **Backend Dev** | Arquivos PHP (Laravel) | Toca em Vue/TS |
| **Frontend Dev** | Arquivos Vue/TS | Toca em PHP |
| **Reviewer** | Audita, aprova ou rejeita com checklist | Implementa código |

### Fluxo obrigatório

```
[ideia informal] → Prompt Engineer → prompt estruturado
                                          ↓
                   Planner → aprovação humana → Backend Dev → Reviewer → Frontend Dev → Reviewer
```

O Prompt Engineer é opcional mas recomendado para qualquer feature descrita em linguagem natural.
Nunca pule as etapas de aprovação e revisão. Se o Reviewer rejeitar, o agent responsável corrige antes de avançar.

---

## Stack

| Camada | Tecnologia |
|---|---|
| PHP | 8.4+ |
| Framework | Laravel 13 |
| Autenticação | Laravel Fortify v1 |
| Inertia server | inertia-laravel v2 |
| Roteamento TS | Wayfinder v0 + Ziggy v2 |
| Vue | 3 com `<script setup lang="ts">` |
| Inertia client | @inertiajs/vue3 v2 |
| CSS | Tailwind CSS v4 |
| UI | reka-ui + shadcn-vue |
| i18n | laravel-vue-i18n (opcional — strings hardcoded permitidas) |
| Testes | Pest v4 |
| Formatação PHP | Pint v1 |
| Linting TS | ESLint v9 + Prettier v3 |
| Banco | MySQL — single-database multi-tenancy |
| Auditoria | owen-it/laravel-auditing (obrigatório em todos os models de domínio) |

---

## Arquitetura obrigatória

```
Request → FormRequest (validação) → Controller (fino) → Service (lógica) → Model → Inertia response
```

- Controllers nunca contêm lógica de negócio — delegam ao Service
- Toda lógica de negócio vive exclusivamente no Service
- Models não contêm lógica de negócio

### Separação de Controllers por escopo

```
Http/Controllers/Admin/    → Master, Admin, Operação (cross-tenant)
Http/Controllers/Tenant/   → Gestor, Comercial (tenant-scoped)
Http/Controllers/Public/   → Formulário de captação (sem autenticação)
```

### Multi-tenancy

- Todo model de domínio usa a trait `BelongsToTenant`
- `GlobalScope` aplicado automaticamente via trait — nunca filtrar `school_id` manualmente em queries simples
- `school_id` nunca vem da request — sempre de `auth()->user()->currentSchool()`
- Middleware `SetActiveTenant` resolve o tenant ativo antes de qualquer controller de tenant
- Middleware `EnsureTenantAccess` garante que Gestor/Comercial só acessam o próprio tenant

### Núcleo do sistema — Tarefas e Tabulações

- **Uma oportunidade nunca tem duas tarefas `status = open` simultaneamente** — validar no `TaskService` antes de criar
- Conclusão de tarefa é sempre atômica: `DB::transaction()` cobrindo Task + Outcome + todas as OutcomeActions
- `OutcomeProcessorService` é o único ponto que executa ações pós-tabulação
- `RenitenteCycleService` é o único ponto que calcula delays do ciclo Renitente
- Recusas são tabulações especiais disponíveis em qualquer tipo de tarefa — não são tipos de tarefa

---

## Convenções — PHP

- `declare(strict_types=1)` no topo de todo arquivo PHP
- Property promotion do PHP 8 nos construtores
- Return types explícitos em todos os métodos
- Método `casts()` nos models — nunca a propriedade `$casts`; visibilidade sempre `protected`
- `?->` para encadeamento seguro — nunca `isset()`
- `=== null` / `!== null` para verificações de null — nunca `is_null()`
- Form Request para toda validação — nunca inline no Controller
- `ValidationException` em fluxos Inertia — nunca `back()->withErrors()`
- `to_route()` para redirecionamentos — nunca `redirect()->route()`
- `owen-it/laravel-auditing` em todos os models de domínio, sem exceção
- UUIDs em URLs públicas — nunca IDs numéricos expostos
- `attach()`/`detach()` com diff em pivots auditadas — nunca `sync()`
- `LengthAwarePaginator` em listagens — nunca `->get()` sem paginação
- `Str::after()` para extrair partes de strings — nunca `explode()[n]`
- Nunca usar `env()` fora de arquivos de configuração — sempre `config()`
- `Model::query()` para queries de dados de domínio — `DB::table()` nunca; `DB::transaction()` é permitido
- Observers registrados via `#[ObservedBy(NomeObserver::class)]` no model — nunca `Model::observe()` no ServiceProvider
- Comentários e PHPDoc em inglês

## Convenções — Vue/TypeScript

- Sempre `<script setup lang="ts">` — nunca Options API
- TypeScript estrito — nunca `any` explícito ou implícito
- Props tipadas com `interface` + `defineProps<Props>()`
- Emits declarados com `defineEmits`
- Componentes em PascalCase, composables com prefixo `use`
- i18n via `laravel-vue-i18n` é opcional — strings hardcoded são permitidas; `$t()` existente pode ser mantido
- Navegação via Wayfinder (`@/routes/`, `@/actions/`) — nunca URLs ou nomes de rota hardcoded
- Formulários via `useForm` do Inertia — nunca `fetch` ou `axios` diretamente
- Verificar `resources/js/components/ui/` antes de criar componentes novos
- Verificar `resources/js/composables/` antes de escrever lógica nova

## Regras de menu lateral

- Itens sem grupo → primeiro (ordenados por `order`)
- Grupos normais → depois (ordenados por `order`)
- Grupo **"Configurações"** → sempre o último, ignora `order`

---

## Validações obrigatórias ao encerrar qualquer tarefa

**Backend:**
```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

**Frontend:**
```bash
npm run lint
npm run format
```

Testes vermelhos ou erros de lint = tarefa não concluída. Sem exceções.

---

## Checklist do Reviewer

### Backend
- [ ] Controller sem lógica de negócio — delega ao Service
- [ ] Form Request para toda validação — nenhuma inline
- [ ] `ValidationException` — nenhum `back()->withErrors()`
- [ ] `to_route()` — nenhum `redirect()->route()`
- [ ] `owen-it/laravel-auditing` no model
- [ ] UUID em URLs — nenhum ID numérico exposto
- [ ] `LengthAwarePaginator` em listagens
- [ ] `attach()`/`detach()` com diff — nenhum `sync()` em pivots auditadas
- [ ] `declare(strict_types=1)` em todo arquivo PHP
- [ ] Return types explícitos em todos os métodos
- [ ] Model usa `BelongsToTenant` (para entidades de domínio do tenant)
- [ ] `school_id` nunca vem da request em controllers de tenant
- [ ] Conclusão de tarefa dentro de `DB::transaction()`
- [ ] Nunca duas tarefas `open` na mesma oportunidade
- [ ] `OutcomeProcessorService` para toda lógica de tabulação
- [ ] Testes Pest passando (`php artisan test --compact`)
- [ ] Pint sem erros (`vendor/bin/pint --dirty --format agent`)

### Frontend
- [ ] `<script setup lang="ts">` em todos os componentes
- [ ] Nenhum `any` explícito ou implícito
- [ ] Props tipadas com `interface` + `defineProps<Props>()`
- [ ] Wayfinder para toda navegação — nenhuma URL hardcoded
- [ ] Formulários via `useForm` do Inertia
- [ ] Lint sem erros (`npm run lint`)

### Segurança
- [ ] Dados sensíveis não expostos em props Inertia
- [ ] Permissões verificadas no backend — nunca só no frontend
- [ ] CSRF ativo nas rotas mutantes (POST/PUT/DELETE)
- [ ] Formulário público não expõe dados internos do tenant

### Veredicto

```
## Revisão: [nome da feature/tarefa]

**Veredicto: APROVADO** | **Veredicto: REJEITADO**

### Problemas bloqueantes
- `caminho/arquivo.php` linha X: problema → como corrigir

### Observações não bloqueantes
- sugestão opcional
```

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