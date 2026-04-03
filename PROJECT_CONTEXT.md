# PROJECT_CONTEXT.md
> Última atualização: 2026-04-01

## PROJETO
CRM multi-tenant de matrículas escolares. Equipe interna (Master/Admin/Operação) gerencia múltiplas escolas. Cada escola tem Gestor/Comercial acessando apenas seu tenant.

Stack: PHP 8.4 · Laravel 13 · Inertia v2 · Vue 3 · Tailwind v4 · MySQL · Pest v4

## PERFIS DE ACESSO
O sistema de ACL do boilerplate (`roles → permissions → Gate`) é mantido **integralmente**.
Não existe coluna `role` ENUM em `users`. Perfil sempre via `users.role_id → roles.id`.

| Role slug | Tipo | Acessa |
|---|---|---|
| `master` | cross-tenant | Tudo, único que exclui School |
| `admin` | cross-tenant | Cria/edita Schools, gerencia usuários |
| `operacao` | cross-tenant | Read/write em tenants, sem exclusão |
| `gestor` | tenant-scoped | Tudo no próprio tenant |
| `comercial` | tenant-scoped | Operacional no próprio tenant, sem config |

`User::isCrossTenant(): bool` — verifica se slug é `master`/`admin`/`operacao`.

## MULTI-TENANCY
- `BelongsToTenant` trait com `GlobalScope` por `school_id` — obrigatório em todo model de domínio
- `school_id` nunca vem da request — sempre de `auth()->user()->currentSchool()`
- Pivot `school_user` (`school_id`, `user_id`, `is_active`) — vincula gestor/comercial a tenants
- Cross-tenant não precisa de `school_user` — resolve tenant via parâmetro de rota `{school_uuid}`
- `SetActiveTenant`: gestor/comercial → via `school_user`; cross-tenant → via `{school_uuid}`
- `EnsureTenantAccess`: aborta 403 se gestor/comercial sem vínculo em `school_user`

## VÍNCULO DE USUÁRIO A TENANT
Feito na tela de edição da School. Admin/Master seleciona usuário + role (`gestor` ou `comercial`).
`SchoolService::attachUser(School, User, Role)` — único ponto que insere em `school_user`.
Endpoint rejeita qualquer role fora de `gestor`/`comercial` com `ValidationException`.

## NÚCLEO: TAREFAS E TABULAÇÕES
- Uma oportunidade nunca tem duas tarefas `open` simultaneamente — validar em `TaskService`
- Conclusão de tarefa: `DB::transaction()` cobrindo Task + Outcome + OutcomeActions
- `OutcomeProcessorService` — único ponto de execução de ações de tabulação
- `RenitenteCycleService` — único ponto de cálculo de delays (count 0→+1h; 1-5→+3h; 6→exception+reset)

## CONVENÇÕES INVIOLÁVEIS
PHP: `declare(strict_types=1)` · método `casts()` nunca propriedade `$casts` · `=== null` nunca `is_null()` · `array_key_exists()` nunca `isset()` em arrays · `attach()`/`detach()` nunca `sync()` · `LengthAwarePaginator` nunca `->get()` · `to_route()` nunca `redirect()->route()` · `ValidationException` nunca `back()->withErrors()` · UUID em URLs nunca ID numérico · `owen-it/laravel-auditing` em todo model de domínio · `#[ObservedBy]` no model nunca `Model::observe()` no SP · `config()` nunca `env()` fora de config · migrations usam `->string()` nunca `->enum()` — cast para PHP backed enum no model

Vue/TS: `<script setup lang="ts">` · TypeScript strict sem `any` · Wayfinder para toda navegação · `useForm` do Inertia nunca `fetch`/`axios`

## PADRÃO DE UI — LISTAGENS E FORMULÁRIOS

Referência obrigatória: `resources/js/pages/acl/Users/` (Index · Create · Edit).
Todo novo módulo deve seguir exatamente essa estrutura. **Desvio exige declaração prévia do desenvolvedor antes da implementação.**

| Contexto | Regra |
|---|---|
| Accordion de filtros | `<Accordion class="w-72">` + `<AccordionContent class="pt-2">` — nunca `absolute`, `relative` ou `overflow-visible` |
| router.reload | `preserveUrl: true` — nunca `preserveScroll` |
| Form de criação | `<Form method="post" :action="store().url">` — nunca `v-bind="store.form()"` (`.form()` não existe em objetos Wayfinder) |
| Form de edição | `<Form method="put" :action="update({ uuid: props.model.uuid }).url">` + `:default-value` nos inputs — nunca `useForm({...})` desconectado |
| Parâmetros de rota | Sempre `uuid` — nunca `id` numérico exposto na URL |

## DECISÕES DE ARQUITETURA
- `Controllers/Admin/` cross-tenant · `Controllers/Tenant/` tenant-scoped · `Controllers/Public/` sem auth
- `Auth::logout()` **antes** de `forceDelete()` no destroy do perfil
- RabbitMQ removido — sem driver compatível com Laravel 13 · `QUEUE_CONNECTION=database`
- Verificar `resources/js/components/ui/` e `composables/` antes de criar qualquer coisa nova
## 14 — DESVIOS CORRIGIDOS DURANTE IMPLEMENTAÇÃO

#### currentSchool() sem model School disponível
O método `User::currentSchool()` foi implementado na Etapa 0.3, mas o model `School` só existe a partir da Etapa 1.x. Referenciar `School::find()` diretamente causaria fatal error.

**Errado:**
```php
public function currentSchool(): ?\App\Models\School
{
    if (app()->bound('tenant.school_id')) {
        return \App\Models\School::find(app('tenant.school_id'));
    }
    return null;
}
```

**Correto:**
```php
public function currentSchool(): ?object
{
    if (! class_exists(\App\Models\School::class)) {
        return null;
    }
    if (app()->bound('tenant.school_id')) {
        return \App\Models\School::find(app('tenant.school_id'));
    }
    return null;
}
```

#### Teste de middleware esperava 503 antes da implementação de currentSchool()
`TenantScopeTest` foi escrito com `assertStatus(503)` para o cenário pré-Etapa 0.3 (quando `currentSchool()` não existia). Após a implementação do método, o middleware passa para a verificação de escola e retorna 403 (escola não encontrada). O teste foi atualizado para `assertStatus(403)`.

#### Dívida técnica: RoleSeeder do boilerplate usa sync()
Os blocos `Master`, `Admin` e `User` do `RoleSeeder.php` original usam `permissions()->sync()`, violando a convenção `attach()`/`detach()`. Não foi corrigido nesta etapa para não alterar código do boilerplate fora do escopo.

#### OutcomeSeeder: spec descreve "30 normais" mas tabelas listam 31
O enunciado da Etapa 0.5 diz "30 outcomes normais + 10 de recusa = 40 totais", porém as tabelas do spec listam exatamente 31 outcomes normais (retorno_ligacao:3, agendamento:2, lembrete_agenda:3, reagendamento:4, double_check:4, provavel_matricula:3, evento:3, lembrete_evento:3, reagendamento_evento:3, double_check_evento:3). A implementação segue as tabelas (31+10=41), que representam o comportamento correto do domínio.

#### SegmentSeeder: UUID sobrescrito a cada execução (não é idempotente para UUID)
Em `updateOrCreate(['name' => $name], ['uuid' => (string) Str::uuid()])`, o segundo argumento é sempre aplicado — inclusive em updates. Isso faz com que o UUID do segmento seja substituído por um novo valor a cada execução do seeder, quebrando referências de FK e pivot que dependem do registro original.

**Errado:**
```php
Segment::updateOrCreate(
    ['name' => $name],
    ['uuid' => (string) Str::uuid()],
);
```

**Correto:**
```php
Segment::firstOrCreate(
    ['name' => $name],
    ['uuid' => (string) Str::uuid()],
);
```

Ou, usando `updateOrCreate` com UUID somente na criação:
```php
$segment = Segment::where('name', $name)->first();
if ($segment === null) {
    Segment::create(['name' => $name, 'uuid' => (string) Str::uuid()]);
}
```

#### Ausência de testes para Etapa 0.5 (Seeders globais)
O DEVELOPMENT_PLAN.md exige `🧪 Teste: OutcomeSeeder produz exatamente N outcomes e M actions esperados` na Etapa 0.5. Nenhum arquivo de teste foi criado para validar a contagem de outcomes, recusas ou actions gerados pelo seeder. O checklist do Reviewer exige que testes Pest existam e passem — a ausência é bloqueante.

## FUNIL DE STATUS

| # | Status | Tipo | Regra |
|---|---|---|---|
| 1 | `cadastro_inicial` | Entrada | Primeiro status. Formulário público ou criação manual. |
| 2 | `agendamento` | Intermediário | Gerado por tabulação de Retorno de Ligação. |
| 3 | `visita` | Intermediário | Gerado quando comparecimento é confirmado. |
| 4 | `matricula` | Terminal ✓ | Gerado por Pré-Matrícula. Não pode ser reaberto. |
| 5 | `recusado` | Terminal ✗ | Recusa em qualquer tarefa. Exige categoria + detalhamento. |

Status terminais são definitivos — nenhum perfil pode reabrir.
Recusa exige: categoria (`fatores_externos` / `fatores_internos` / `pedagogicos` / `administrativos`) + detalhamento textual obrigatório.

## TIPOS DE TAREFA

| Enum | Schedule? | Geração |
|---|---|---|
| `retorno_ligacao` | Não | Primeira tarefa — criada manualmente ao criar a oportunidade |
| `agendamento` | Sim ★ | Gerada via tabulação do Retorno de Ligação |
| `lembrete_agenda` | Sim ★ | Gerada automaticamente (1 dia / meio período antes) |
| `reagendamento` | Não | Gerada por Não Compareceu ou Novo Reagendamento |
| `double_check` | Não | Gerada para confirmar presença antes da visita |
| `provavel_matricula` | Não | Gerada automaticamente quando visita é confirmada |
| `evento` | Não | Gerada ao vincular oportunidade a um Evento |
| `lembrete_evento` | Sim ★ | Gerada automaticamente antes do evento |
| `reagendamento_evento` | Não | Gerada por Não Compareceu Evento |
| `double_check_evento` | Não | Gerada para confirmação pré-evento |

★ = `is_schedule = true` — aparecem no calendário.

## CICLO RENITENTE

count 0 → +1h · count 1–5 → +3h cada · count 6 → `RenitenteLimitReachedException` + reset para 0
`renitente_count` fica em `opportunities`. `RenitenteCycleService` é o único que calcula e persiste.

## RECUSAS

Disponíveis em qualquer tipo de tarefa. Movem para `recusado` (terminal).
`is_refusal = true` no outcome exige validação de `refusal_category` + `refusal_detail` no `TaskCompleteRequest`.

## MAPA TABULAÇÕES → AÇÕES

> `open_window` na response é o **único** gatilho para modal no frontend. Nunca por slug.

**Retorno de Ligação**
| Tabulação | Ações |
|---|---|
| Renitente | `create_task(retorno_ligacao, renitente: true)` |
| Agendamento | `open_window(agendamento)` |
| Novo Retorno de Ligação | `open_window(retorno_ligacao)` |

**Agendamento**
| Tabulação | Ações |
|---|---|
| Compareceu Agendamento | `move_status(visita)` + `create_task(provavel_matricula)` |
| Não Compareceu Agendamento | `create_task(reagendamento, +2 dias)` |

**Lembrete de Agenda ★**
| Tabulação | Ações |
|---|---|
| Vai Comparecer Agenda | `create_task(lembrete_agenda)` |
| Não Vai Comparecer — Reagendou | `cancel_tasks(lembrete_agenda)` + `open_window(agendamento)` |
| Renitente | `create_task(lembrete_agenda, renitente: true)` |

**Reagendamento**
| Tabulação | Ações |
|---|---|
| Reagendado | `cancel_tasks(agendamento)` + `open_window(agendamento)` |
| Visita Realizada | `move_status(visita)` + `create_task(provavel_matricula)` |
| Novo Reagendamento | `open_window(reagendamento)` |
| Renitente | `create_task(reagendamento, renitente: true)` |

**Double Check**
| Tabulação | Ações |
|---|---|
| Visitou | `move_status(visita)` + `create_task(provavel_matricula)` + `cancel_tasks(agendamento)` |
| Não Visitou | `cancel_tasks(agendamento)` + `open_window(agendamento)` |
| Novo Double Check | `open_window(double_check)` |
| Renitente | `create_task(double_check, renitente: true)` |

**Provável Matrícula**
| Tabulação | Ações |
|---|---|
| Pré-Matrícula | `move_status(matricula)` |
| Em Andamento com Data Futura | `open_window(provavel_matricula)` |
| Provável | `create_task(provavel_matricula, +4 dias)` |

**Evento**
| Tabulação | Ações |
|---|---|
| Compareceu Evento | `move_status(visita)` + `create_task(provavel_matricula)` |
| Não Compareceu Evento | `create_task(reagendamento_evento, +2 dias)` |
| Não Compareceu sem Data | `cancel_tasks(evento)` |

**Lembrete de Evento ★**
| Tabulação | Ações |
|---|---|
| Vai Comparecer Evento | `create_task(lembrete_evento)` |
| Não Vai Comparecer — Reagendou | `cancel_tasks(lembrete_evento)` + `open_window(evento)` |
| Renitente | `create_task(lembrete_evento, renitente: true)` |

**Reagendamento de Evento**
| Tabulação | Ações |
|---|---|
| Reagendado Evento | `cancel_tasks(evento)` + `open_window(evento)` |
| Compareceu no Evento | `move_status(visita)` + `create_task(provavel_matricula)` |
| Renitente | `create_task(reagendamento_evento, renitente: true)` |

**Double Check de Evento**
| Tabulação | Ações |
|---|---|
| Visitou Evento | `move_status(visita)` + `create_task(provavel_matricula)` + `cancel_tasks(evento)` |
| Não Visitou Evento | `cancel_tasks(evento)` + `open_window(evento)` |
| Renitente | `create_task(double_check_evento, renitente: true)` |