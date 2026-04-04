# MEMORY.md

> Único arquivo lido ao iniciar toda sessão. Autossuficiente para 90% das tarefas.
> Atualizado obrigatoriamente ao encerrar cada sessão.

---

## ESTADO ATUAL

**Última sessão:** 2026-04-03
**Próxima tarefa:** Etapa 4.x — Oportunidades (service, requests, policy, controller, rotas, testes HTTP)

### Concluído

- [x] 0.1 — Ambiente (MySQL, Reverb, laravel-auditing)
- [x] 0.2 — BelongsToTenant · SetActiveTenant · EnsureTenantAccess
- [x] 0.3 — RoleSeeder (5 roles) · CrmPermissionSeeder · User::isCrossTenant() · currentSchool()
- [x] 0.4 — CheckRole middleware · routes/admin.php · routes/tenant.php
- [x] 0.5 — SegmentSeeder (6) · OutcomeSeeder (41 outcomes + actions)
- [x] 1.1 — Migrations schools/school_units/school_user · SchoolStatus enum · School e SchoolUnit models · SchoolObserver e SchoolUnitObserver · Testes Pest (3 passando)
- [x] 1.2 — SchoolService · ViaCepService · SchoolPolicy · SchoolStoreRequest · SchoolUpdateRequest · SchoolController · Rotas admin · Testes (62 passando)
- [x] 1.3 — SchoolUserAttachRequest · SchoolUserController · Rotas admin (store/destroy) · Testes Pest (71 passando)
- [x] 1.4 — Frontend Schools (Index · Create · Edit com seção usuários)
- [x] 1.5 — Bugfixes Schools/Index: accordion defaultValue+overflow-visible, ConfirmDeleteModal com senha, rota confirmDelete backend

### Pendente (ordem de execução)

- [x] **2.1** — Segment (auditoria + relações schools/grades) · Grade (BelongsToTenant + auditoria) · migrations school_segment + grades · observers SegmentObserver + GradeObserver
- [x] **2.2** — SchoolYear: migration (string status) · enum SchoolYearStatus · observer · model · service · requests · controller · rotas · SchoolYearPolicy · testes HTTP (86 passando) — APROVADO em 2026-04-03
- [x] **2.3** — LeadSource: migration · LeadSourceScope · model · observer · service · requests · policy · controller · rotas · testes (98 passando) — APROVADO em 2026-04-03
- [x] **2.4** — Frontend Configurações Tenant: SchoolYears.vue · LeadSources.vue · Grades.vue (placeholder) — concluído em 2026-04-03
- [x] **3.1** — Alunos e Responsáveis (backend): migrations · models · observers · services · requests · policies · controllers · rotas · testes (118 passando) — APROVADO em 2026-04-03
- [x] **3.2** — Frontend CPF Lookup: `useCpfLookup` composable + `CpfField.vue` componente — concluído em 2026-04-03
- [x] **4.1** — Oportunidades (backend base): OpportunityStatus enum · migration · Opportunity model · OpportunityObserver · teste modelo (119 passando) — APROVADO em 2026-04-03
- [ ] **4.x** — Oportunidades (restante) 🔴
- [ ] **5.x** — Tarefas e Tabulações 🔴
- [ ] **6–11** — Notificações · Eventos · Formulário · Calendário · Relatórios · LGPD

---

## PROJETO

CRM multi-tenant de matrículas escolares.
Stack: PHP 8.4 · Laravel 13 · Inertia v2 · Vue 3 · Tailwind v4 · MySQL · Pest v4

### Perfis

| Role slug   | Tipo          | Acessa                                |
| ----------- | ------------- | ------------------------------------- |
| `master`    | cross-tenant  | Tudo, único que exclui School         |
| `admin`     | cross-tenant  | Cria/edita Schools, gerencia usuários |
| `operacao`  | cross-tenant  | Read/write em tenants, sem exclusão   |
| `gestor`    | tenant-scoped | Tudo no próprio tenant                |
| `comercial` | tenant-scoped | Operacional, sem config               |

`User::isCrossTenant()` — retorna true se role é master/admin/operacao.

### Multi-tenancy

- `BelongsToTenant` trait + `GlobalScope` por `school_id` — obrigatório em todo model de domínio
- `school_id` **nunca** vem da request — sempre de `auth()->user()->currentSchool()`
- Pivot `school_user` (`school_id`, `user_id`, `is_active`) — vincula gestor/comercial a tenants
- `SetActiveTenant`: gestor/comercial → via `school_user` · cross-tenant → via `{school_uuid}`
- `EnsureTenantAccess`: aborta 403 se gestor/comercial sem vínculo em `school_user`
- `SchoolService::attachUser(School, User, Role)` — **único** ponto de insert em `school_user`
- Endpoint rejeita role fora de gestor/comercial com `ValidationException`

### ACL

Sistema do boilerplate (`roles → permissions → Gate`) mantido intacto.
Sem coluna `role` ENUM em `users`. Perfil sempre via `users.role_id → roles.id`.

---

## NÚCLEO: TAREFAS E TABULAÇÕES

- Uma oportunidade **nunca** tem duas tarefas `open` simultaneamente — validar em `TaskService`
- Conclusão de tarefa: `DB::transaction()` cobrindo Task + Outcome + OutcomeActions
- `OutcomeProcessorService` — **único** ponto de execução de ações de tabulação
- `RenitenteCycleService` — **único** ponto de cálculo de delays
- `open_window` na response é o **único** gatilho para modal no frontend — nunca por slug

### Ciclo Renitente

count 0 → +1h · count 1–5 → +3h cada · count 6 → `RenitenteLimitReachedException` + reset 0
`renitente_count` fica em `opportunities`.

### Funil de status

`cadastro_inicial` → `agendamento` → `visita` → `matricula` ✓ | `recusado` ✗
Terminais são definitivos — nenhum perfil reabre.
Recusa exige: `refusal_category` (fatores_externos/fatores_internos/pedagogicos/administrativos) + `refusal_detail`.

### Tipos de tarefa

`retorno_ligacao` · `agendamento`★ · `lembrete_agenda`★ · `reagendamento` · `double_check`
`provavel_matricula` · `evento` · `lembrete_evento`★ · `reagendamento_evento` · `double_check_evento`
★ = `is_schedule = true` — aparecem no calendário.

### Mapa tabulações → ações (resumo)

| Tarefa               | Tabulação              | Ação                                                                                    |
| -------------------- | ---------------------- | --------------------------------------------------------------------------------------- |
| retorno_ligacao      | Renitente              | `create_task(retorno_ligacao, renitente:true)`                                          |
| retorno_ligacao      | Agendamento            | `open_window(agendamento)`                                                              |
| retorno_ligacao      | Novo Retorno           | `open_window(retorno_ligacao)`                                                          |
| agendamento          | Compareceu             | `move_status(visita)` + `create_task(provavel_matricula)`                               |
| agendamento          | Não Compareceu         | `create_task(reagendamento, +2d)`                                                       |
| lembrete_agenda      | Vai Comparecer         | `create_task(lembrete_agenda)`                                                          |
| lembrete_agenda      | Não Vai / Reagendou    | `cancel_tasks(lembrete_agenda)` + `open_window(agendamento)`                            |
| lembrete_agenda      | Renitente              | `create_task(lembrete_agenda, renitente:true)`                                          |
| reagendamento        | Reagendado             | `cancel_tasks(agendamento)` + `open_window(agendamento)`                                |
| reagendamento        | Visita Realizada       | `move_status(visita)` + `create_task(provavel_matricula)`                               |
| reagendamento        | Novo Reagendamento     | `open_window(reagendamento)`                                                            |
| reagendamento        | Renitente              | `create_task(reagendamento, renitente:true)`                                            |
| double_check         | Visitou                | `move_status(visita)` + `create_task(provavel_matricula)` + `cancel_tasks(agendamento)` |
| double_check         | Não Visitou            | `cancel_tasks(agendamento)` + `open_window(agendamento)`                                |
| double_check         | Novo Double Check      | `open_window(double_check)`                                                             |
| double_check         | Renitente              | `create_task(double_check, renitente:true)`                                             |
| provavel_matricula   | Pré-Matrícula          | `move_status(matricula)`                                                                |
| provavel_matricula   | Em Andamento c/ Data   | `open_window(provavel_matricula)`                                                       |
| provavel_matricula   | Provável               | `create_task(provavel_matricula, +4d)`                                                  |
| evento               | Compareceu             | `move_status(visita)` + `create_task(provavel_matricula)`                               |
| evento               | Não Compareceu         | `create_task(reagendamento_evento, +2d)`                                                |
| evento               | Não Compareceu s/ Data | `cancel_tasks(evento)`                                                                  |
| lembrete_evento      | Vai Comparecer         | `create_task(lembrete_evento)`                                                          |
| lembrete_evento      | Não Vai / Reagendou    | `cancel_tasks(lembrete_evento)` + `open_window(evento)`                                 |
| lembrete_evento      | Renitente              | `create_task(lembrete_evento, renitente:true)`                                          |
| reagendamento_evento | Reagendado             | `cancel_tasks(evento)` + `open_window(evento)`                                          |
| reagendamento_evento | Compareceu             | `move_status(visita)` + `create_task(provavel_matricula)`                               |
| reagendamento_evento | Renitente              | `create_task(reagendamento_evento, renitente:true)`                                     |
| double_check_evento  | Visitou                | `move_status(visita)` + `create_task(provavel_matricula)` + `cancel_tasks(evento)`      |
| double_check_evento  | Não Visitou            | `cancel_tasks(evento)` + `open_window(evento)`                                          |
| double_check_evento  | Renitente              | `create_task(double_check_evento, renitente:true)`                                      |
| qualquer             | Recusa                 | `move_status(recusado)` — exige categoria + detalhe                                     |

---

## CONVENÇÕES

### PHP (invioláveis)

```
$table->id() + $table->uuid()->unique()   padrão de toda migration de domínio — uuid é preenchido via Observer, nunca é PK;
declare(strict_types=1)     em todo arquivo PHP
casts()                     método protegido — nunca propriedade $casts
=== null / !== null         nunca is_null()
array_key_exists()          nunca isset() para verificar chave em array
attach() / detach()         nunca sync() em pivots auditadas
LengthAwarePaginator        nunca ->get() sem paginação
to_route()                  nunca redirect()->route()
ValidationException         nunca back()->withErrors() em fluxos Inertia
UUID em URLs                nunca ID numérico exposto
#[ObservedBy]               no model — nunca Model::observe() no ServiceProvider
config()                    nunca env() fora de arquivos de config
Model::query()              nunca DB::table() para dados de domínio
```

### Vue/TypeScript (invioláveis)

```
<script setup lang="ts">    sempre — nunca Options API
sem any                     TypeScript strict
Wayfinder                   toda navegação — nunca URL hardcoded
useForm Inertia             nunca fetch() ou axios direto
router.visit()              nunca window.location.href
```

### Arquitetura

```
Controller → Service → Model   controller nunca tem lógica de negócio
Admin/      cross-tenant
Tenant/     tenant-scoped
Public/     sem auth
```

---

## DECISÕES DE ARQUITETURA

| Data       | Decisão                                                                                          |
| ---------- | ------------------------------------------------------------------------------------------------ |
| 2026-04-01 | RabbitMQ removido — sem driver Laravel 13. `QUEUE_CONNECTION=database`                           |
| 2026-04-01 | Gestor/Comercial são roles normais do ACL — sem ENUM em `users`. Vínculo via `school_user`       |
| 2026-04-01 | `school_user` sem coluna `role` — perfil definido por `users.role_id`                            |
| 2026-04-01 | `CrmPermissionSeeder` separado — seeders do boilerplate não tocados                              |
| 2026-04-01 | Roles identificados por `name` — `Role::updateOrCreate(['name' => ...])`                         |
| 2026-04-01 | `currentSchool()` usa `class_exists(School::class)` — retorna null até Etapa 1.x sem fatal error |
| 2026-04-01 | `CheckRole` com alias `role:` em bootstrap/app.php — rotas via `then:` no `withRouting()`        |
| 2026-04-01 | OutcomeSeeder resulta em 41 outcomes — spec diz 40 mas tabelas listam 31 normais + 10 recusa     |

---

## PADRÕES E ARMADILHAS

| Data       | Arquivo                      | Regra                                                                                                                                    |
| ---------- | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-04-01 | User.php                     | `currentSchool()` usa `class_exists()` — evita fatal error até Etapa 1.x                                                                 |
| 2026-04-01 | RoleSeeder.php               | Boilerplate usa `sync()` — dívida não corrigida; novos seeders CRM não usam                                                              |
| 2026-04-01 | CheckRole.php                | Comparação por `$user->role?->name` (string capitalizada)                                                                                |
| 2026-04-01 | OutcomeSeeder.php            | `->delete()` + `->create()` nas actions — nunca `sync()`                                                                                 |
| 2026-04-01 | SegmentSeeder.php            | Usar `firstOrCreate()` — nunca `updateOrCreate()` com UUID no segundo argumento                                                          |
| 2026-04-01 | TenantScopeTest.php          | Até Etapa 1.x: School não existe → `currentSchool()` null → SetActiveTenant → 403                                                        |
| 2026-04-02 | School.php                   | School não usa BelongsToTenant — é o tenant raiz; primary key é UUID (não campo separado uuid)                                           |
| 2026-04-02 | SchoolObserver.php           | Slug gerado no creating com loop while para sufixo incremental; usar === null para checar id                                             |
| 2026-04-02 | Controller.php               | Base Controller sem AuthorizesRequests — usar Gate::authorize() diretamente em vez de $this->authorize()                                 |
| 2026-04-02 | SchoolPolicy.php             | Autodiscovery do Laravel 13 funciona para App\Policies\{Model}Policy — sem necessidade de registro manual                                |
| 2026-04-02 | AclServiceProvider.php       | Gate::before() retorna null (não false) quando sem permissão — policies são avaliadas normalmente na sequência                           |
| 2026-04-02 | SchoolUserControllerTest.php | ValidationException retorna 302 (redirect) em requests normais — usar withHeader('Accept', 'application/json') para obter 422 nos testes |
| 2026-04-02 | SchoolUserController.php     | User não tem getRouteKeyName() — destroy recebe string $userUuid e resolve manualmente via User::query()->where('uuid', ...)             |
| 2026-04-02 | SchoolPolicy.php             | update() restrito a Master e Admin — Operacao não pode gerenciar vínculos de usuário-escola                                              |
| 2026-04-02 | SchoolObserver.php           | Bug: observer setava `$school->id = UUID` em vez de `$school->uuid` — corrigido para `$school->uuid === null` check                     |
| 2026-04-02 | School.php                   | `getRouteKeyName()` retorna `uuid` — rotas usam UUID, não ID numérico; cast `id => string` removido (era workaround do bug do observer)  |
| 2026-04-02 | Migrations                   | Toda migration de domínio precisa de `declare(strict_types=1)` — padrão PHP inviolável                                                  |
| 2026-04-02 | SchoolService.php            | `->get()` sem paginação era retornado quando `per_page=all` — violação de LengthAwarePaginator; corrigido para sempre paginar            |
| 2026-04-02 | Create.vue (Schools)         | `Form({...})` criado mas nunca vinculado ao template — código morto; padrão correto é `<Form v-bind="store.form()">` sem criar variável  |
| 2026-04-02 | Schools/Index.vue            | `router.get()` com `preserveState:true` trava Accordion reka-ui e bloqueia navegação Inertia — corrigido para `router.post(index.post().url)` sem preserveState; Accordion com `defaultValue="closed"` estático |
| 2026-04-02 | routes/admin.php             | `Route::resource()` não gera rota `clearFilters` nem aceita POST no index — substituir por rotas explícitas com `clearFilters` e `create` antes de `{school}` |
| 2026-04-02 | phpunit.xml / config cache   | `config:cache` em produção faz testes ignorarem `phpunit.xml` (SESSION_DRIVER=array) e usarem SESSION_DRIVER=database, causando CSRF 419 — limpar com `php artisan config:clear` antes de testar |
| 2026-04-02 | Schools/Index.vue            | Accordion dentro de `flex justify-between` precisa de `overflow-visible` + `AccordionContent` com `absolute z-10` para não bloquear cliques na página; `defaultValue="closed"` obrigatório para reka-ui fechar corretamente |
| 2026-04-02 | SchoolController.php         | `index()` deve passar `isMaster` como prop Inertia — sem ele o botão "Excluir" e "Nova Escola" nunca aparecem no frontend |
| 2026-04-03 | Segment.php                  | Segment é global (sem BelongsToTenant) — é entidade de configuração compartilhada; Grade usa BelongsToTenant pois pertence ao tenant |
| 2026-04-03 | SchoolYearService.php        | `list()` usa `where('school_id', $school->id)` explícito — TenantScope já filtra mas o `$school` passado garante isolamento correto sem depender do contexto ativo |
| 2026-04-03 | Migrations                   | Migrations nunca usam `->enum()` — usar `->string(length)` com cast PHP backed enum no model; colunas enum já em prod precisam de migration `->change()` para string |
| 2026-04-03 | AppServiceProvider.php       | `Route::bind('school_uuid', ...)` registrado via `registerRouteBindings()` — resolve School pelo uuid da URL; `some-uuid` inexistente retorna 404 (firstOrFail) |
| 2026-04-03 | TenantRoutingTest.php        | Testes com `/t/some-uuid/dashboard` retornam 404 (não 302/403) após Route::bind — UUID inexistente não chega ao middleware, é barrado pelo binding |
| 2026-04-03 | SchoolYearControllerTest.php | Testes de GET em rotas Inertia usam `withoutVite()` para evitar ViteException; testes de erros de validação usam `withHeader('Accept', 'application/json')` para 422 |
| 2026-04-03 | LeadSource.php | LeadSource não usa BelongsToTenant — usa LeadSourceScope próprio que retorna `school_id = tenant OR is_system = true`; registros de sistema têm school_id null e são visíveis em todos os tenants |
| 2026-04-03 | tenant-settings/*.vue | Páginas tenant recebem `school` como prop Inertia (controller passa `'school' => $school`); usar `props.school.uuid` para montar URLs Wayfinder — nunca depender de `setUrlDefaults` |
| 2026-04-03 | StudentService.php | Unicidade de CPF por tenant validada no `create()` via `ValidationException` — `lookup()` usa `withoutTenantScope()` para busca cross-scope segura |
| 2026-04-03 | routes/tenant.php | Rota `lookup/{cpf}` deve vir ANTES de `{student}` no grupo — evita conflito de route model binding |
| 2026-04-03 | useCpfLookup.ts | `fetch` é o único caso autorizado fora de `useForm` — endpoints GET de lookup retornam JSON puro, não Inertia; interfaces Student/Guardian adicionadas a `crm.ts` |
| 2026-04-03 | OpportunityModelTest.php | Pest 4 `toThrow(\Throwable::class)` falha com mensagem "To contain: Throwable" em vez de checar instância — usar bloco try/catch manual com flag booleana como workaround |

---

## INSTRUÇÕES PARA AGENTS

### Ao iniciar sessão

1. Leia este arquivo (`MEMORY.md`) — suficiente para 90% das retomadas
2. Consulte `PROJECT_CONTEXT.md` apenas para detalhes de domínio não cobertos aqui
3. Consulte `TECHNICAL_PLAN.md` apenas para schema, rotas ou estrutura de pasta específica
4. **Nunca releia tudo por precaução** — custo alto, ganho zero

### Ao encerrar sessão (obrigatório)

Atualize **apenas** as seções que mudaram:

- `## ESTADO ATUAL` — marque concluídos, atualize próxima tarefa
- `## PADRÕES E ARMADILHAS` — adicione erros encontrados e corrigidos (1 linha)
- `## DECISÕES DE ARQUITETURA` — adicione se alguma decisão foi tomada

### Formato padrão para padrões

```
| DATA | Arquivo.php | Regra em 1 linha sem exemplo de código |
```
