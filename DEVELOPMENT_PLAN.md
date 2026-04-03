# DEVELOPMENT_PLAN.md
# CRM Escola Cheia — Plano de Desenvolvimento por Etapas

> **Como usar este documento**
> Cada etapa é um bloco de desenvolvimento. Cada tarefa dentro da etapa é **micro** — um artefato por vez.
> Marque `[x]` ao concluir. Nunca inicie a próxima etapa com tarefas bloqueantes abertas na atual.
> Etapas com `🔴 BLOQUEANTE` precisam estar 100% aprovadas pelo Reviewer antes de avançar.

---

## LEGENDA

| Símbolo | Significado |
|---|---|
| `[ ]` | Pendente |
| `[x]` | Concluído e aprovado pelo Reviewer |
| `🔴` | Bloqueante para próxima etapa |
| `⚙️` | Backend |
| `🖥️` | Frontend |
| `🧪` | Testes |
| `📋` | Revisão (Reviewer) |
| `🌱` | Seeder / dados iniciais |

---

## ETAPA 0 — Fundação e Boilerplate 🔴 BLOQUEANTE

> Adaptar o boilerplate existente ao contexto multi-tenant do CRM.
> Nenhum módulo de domínio pode ser iniciado antes desta etapa estar aprovada.

### 0.1 Configuração do ambiente — concluída em 2026-04-01
- [x] ⚙️ Configurar `.env` para MySQL (não SQLite) + Reverb
- [x] ⚙️ Instalar e configurar `laravel/reverb`
- [x] ⚙️ Configurar `php-amqplib/php-amqplib` ou driver RabbitMQ para queues — N/A: removido do escopo por decisão do desenvolvedor; nenhum pacote compatível com Laravel 13; QUEUE_CONNECTION=database (ver seção 9 do PROJECT_CONTEXT.md)
- [x] ⚙️ Verificar `owen-it/laravel-auditing` instalado e configurado
- [x] 📋 Reviewer: confirmar stack completa funcional — APROVADO em 2026-04-01

### 0.2 Trait de Multi-tenancy — concluída em 2026-04-01
- [x] ⚙️ Criar `app/Models/Concerns/BelongsToTenant.php` com `GlobalScope` por `school_id`
- [x] ⚙️ Criar `app/Http/Middleware/SetActiveTenant.php`
- [x] ⚙️ Criar `app/Http/Middleware/EnsureTenantAccess.php`
- [x] ⚙️ Registrar middlewares em `bootstrap/app.php` com aliases `tenant` e `tenant.access`
- [x] 🧪 Teste: GlobalScope filtra por school_id automaticamente
- [x] 🧪 Teste: request com school_id forjado é ignorado pelo middleware
- [x] 📋 Reviewer: verificar que nenhuma query vaza sem scope de tenant — APROVADO em 2026-04-01

### 0.3 Roles e permissões do CRM no sistema ACL existente

> **Decisão de arquitetura:** não existe coluna `role` extra em `users`, nem enum separado.
> Gestor e Comercial são roles normais no sistema ACL do boilerplate, exatamente como
> Master, Admin e Operação. O vínculo com tenants é feito via pivot `school_user`.

- [x] 🌱 `RoleSeeder`: adicionar as 5 roles do CRM (`master`, `admin`, `operacao`, `gestor`, `comercial`) com seus slugs e descrições — verificar se já existem antes de inserir (idempotente)
- [x] 🌱 `PermissionSeeder`: adicionar as permissões de cada módulo do CRM para cada role (`schools_list`, `schools_add`, `schools_edit`, `schools_delete`, `tenant_manage`, `opportunities_list`, `opportunities_add`, `opportunities_edit`, etc.) — implementado via `CrmPermissionSeeder`
- [ ] ⚙️ Migration: criar tabela `school_user` (colunas: `school_id` FK, `user_id` FK, `is_active` boolean, `created_at`, `updated_at` — PK composta `school_id + user_id`)
- [x] ⚙️ Atualizar `User` model: adicionar relação `belongsToMany School` via pivot `school_user`, método `currentSchool(): ?School`, método `isCrossTenant(): bool` (verifica se role é master/admin/operacao)
- [ ] ⚙️ Atualizar `SetActiveTenant`: resolver tenant via `school_user` para roles gestor/comercial; via parâmetro de rota `{school_uuid}` para roles cross-tenant
- [ ] ⚙️ Atualizar `EnsureTenantAccess`: bloquear roles gestor/comercial de acessar tenant alheio (verificar pivot `school_user`)
- [ ] 🧪 Teste: usuário com role `master` acessa qualquer tenant sem vínculo em `school_user`
- [ ] 🧪 Teste: usuário com role `gestor` bloqueado em tenant não vinculado em `school_user`
- [ ] 🧪 Teste: usuário com role `comercial` só enxerga dados do próprio tenant
- [x] 📋 Reviewer — APROVADO COM RESSALVAS em 2026-04-01 (dívida: sync() no RoleSeeder do boilerplate, documentada na seção 14 do PROJECT_CONTEXT.md)

### 0.4 Arquivos de rotas do CRM — concluída em 2026-04-01
- [x] ⚙️ Criar `routes/admin.php` (prefixo `/admin`, middleware: `auth`, `verified`, `role:Master,Admin,Operacao`)
- [x] ⚙️ Criar `routes/tenant.php` (middleware: `auth`, `verified`, `tenant`, `tenant.access`)
- [x] ⚙️ Garantir `routes/web.php` com rotas públicas (middleware: `web` apenas)
- [x] ⚙️ Registrar os arquivos em `bootstrap/app.php`
- [x] 📋 Reviewer — APROVADO em 2026-04-01

### 0.5 Seeders globais — concluída em 2026-04-01
- [x] 🌱 `SegmentSeeder` — 6 segmentos: Berçário, Ed. Infantil, Fund. I, Fund. II, Ensino Médio, Integral
- [x] 🌱 `OutcomeSeeder` — todas as tabulações e suas `outcome_actions` (conforme seção 5 do escopo técnico)
- [x] 🧪 Teste: OutcomeSeeder produz exatamente N outcomes e M actions esperados — adiado intencionalmente pelo desenvolvedor para após as etapas relacionadas
- [x] 📋 Reviewer — APROVADO COM RESSALVAS em 2026-04-01 (ressalva: OutcomeSeeder usa updateOrCreate com UUID no segundo argumento — corrigir antes da Etapa 5)

---

## ETAPA 1 — Entidade School (Tenant) 🔴 BLOQUEANTE — concluída em 2026-04-02

> Todas as entidades de domínio dependem de `schools` existir.

### 1.1 Migration e Model
- [ ] ⚙️ Migration `create_schools_table` (schema completo: id uuid, cnpj, razao_social, slug, logo_path, address_json, status, observations, unassigned_lead_alert_days)
- [ ] ⚙️ Migration `create_school_units_table` (id, school_id, nome, cep, logradouro, numero, complemento, bairro, cidade, estado)
- [ ] ⚙️ `app/Models/School.php` — sem BelongsToTenant · com auditing · Observer registrado via `#[ObservedBy]` · relação `belongsToMany User` via pivot `school_user`
- [ ] ⚙️ `app/Models/SchoolUnit.php` — com BelongsToTenant · com auditing
- [ ] ⚙️ `app/Observers/SchoolObserver.php` — gera UUID + slug no `creating` (Str::slug com sufixo numérico em colisão)
- [ ] 🧪 Teste: slug gerado corretamente · colisão gera sufixo · CNPJ duplicado falha
- [ ] 📋 Reviewer

### 1.2 Service e Controller (Admin)
- [ ] ⚙️ `app/Services/School/SchoolService.php` — `lookupCnpj()`, `list()`, `create()`, `update()`, `destroy()`, `attachUser(School, User, Role): void`, `detachUser(School, User): void`
- [ ] ⚙️ `app/Services/School/ViaCepService.php` — lookup CEP via ViaCEP API
- [ ] ⚙️ `app/Http/Requests/School/SchoolStoreRequest.php`
- [ ] ⚙️ `app/Http/Requests/School/SchoolUpdateRequest.php`
- [ ] ⚙️ `app/Policies/SchoolPolicy.php` — destroy: Master apenas
- [ ] ⚙️ `app/Http/Controllers/Admin/SchoolController.php` — delega ao Service
- [ ] ⚙️ Rotas em `routes/admin.php` (index, create, store, edit, update, destroy)
- [ ] 🧪 Teste: Operação não consegue excluir School · Admin edita · Master exclui
- [ ] 📋 Reviewer

### 1.3 Gestão de usuários de tenant dentro do CRUD de School — concluída em 2026-04-02
- [x] ⚙️ `app/Http/Requests/School/SchoolUserAttachRequest.php` — valida `user_id` e `role_id` (apenas roles gestor/comercial permitidas)
- [x] ⚙️ `app/Http/Controllers/Admin/SchoolUserController.php` — `store()` e `destroy()` (attach/detach via `SchoolService`)
- [x] ⚙️ Rotas em `routes/admin.php`: `POST /admin/schools/{uuid}/users` e `DELETE /admin/schools/{uuid}/users/{user_uuid}`
- [x] 🧪 Teste: não é possível vincular role `master`/`admin`/`operacao` a um tenant via este endpoint
- [x] 🧪 Teste: attach cria registro em `school_user` · detach remove · attach duplicado lança ValidationException
- [x] 📋 Reviewer — APROVADO em 2026-04-02

### 1.4 Frontend — CRUD de Schools com gestão de usuários — concluída em 2026-04-02
- [x] 🖥️ `resources/js/types/crm.ts` — interfaces `School`, `SchoolUnit`, `SchoolUser`
- [x] 🖥️ `pages/admin/Schools/Index.vue` — listagem com filtros
- [x] 🖥️ `pages/admin/Schools/Create.vue` — com CNPJ lookup + CEP lookup
- [x] 🖥️ `pages/admin/Schools/Edit.vue` — slug readonly com banner de alerta ao editar; seção "Usuários da escola" com lista de vinculados e formulário para adicionar (seleciona usuário + role gestor/comercial)
- [x] 🖥️ Composable `useCepLookup.ts` — debounce 400ms, auto-preenchimento
- [x] 📋 Reviewer — APROVADO em 2026-04-02

---

## ETAPA 2 — Entidades de Suporte do Tenant 🔴 BLOQUEANTE

> LeadSource, Segment/Grade, SchoolYear são necessários antes de Oportunidades.

### 2.1 Segments e Grades — concluída em 2026-04-03
- [x] ⚙️ `app/Models/Segment.php` — global, sem BelongsToTenant
- [x] ⚙️ Migration `create_school_segment_table` (pivot)
- [x] ⚙️ Migration `create_grades_table` (id, school_id nullable, segment_id, nome, order)
- [x] ⚙️ `app/Models/Grade.php` — com BelongsToTenant · com auditing
- [x] 📋 Reviewer — APROVADO em 2026-04-03

### 2.2 SchoolYear — concluída em 2026-04-03
- [x] ⚙️ Migration `create_school_years_table` (id, school_id, nome, inicio, fim, status string(30))
- [x] ⚙️ Migration `fix_school_years_status_to_string` — migração de correção enum→string para banco já migrado
- [x] ⚙️ `app/Models/SchoolYear.php` — com BelongsToTenant · com auditing · #[ObservedBy]
- [x] ⚙️ `app/Enums/SchoolYearStatus.php` — backed enum string
- [x] ⚙️ `app/Observers/SchoolYearObserver.php` — UUID no creating
- [x] ⚙️ `app/Services/SchoolYear/SchoolYearService.php` — LengthAwarePaginator
- [x] ⚙️ `app/Http/Requests/SchoolYear/SchoolYearStoreRequest.php` · `SchoolYearUpdateRequest.php`
- [x] ⚙️ `app/Policies/SchoolYearPolicy.php` — viewAny/create/update/delete
- [x] ⚙️ `app/Http/Controllers/Tenant/Settings/SchoolYearController.php` — Gate::authorize() em todos os métodos
- [x] ⚙️ `AppServiceProvider::registerRouteBindings()` — Route::bind('school_uuid') resolve School pelo UUID
- [x] ⚙️ Rotas em `routes/tenant.php` para `/tenant-settings/school-years`
- [x] 🧪 Testes HTTP: GET Master (200), GET Gestor (200), GET sem auth (302), store, update, destroy, fim < inicio (422), Comercial (403)
- [ ] 🧪 Teste: criação de oportunidade para ano encerrado exibe aviso (não bloqueia) — adiado para Etapa 4
- [x] 📋 Reviewer — APROVADO em 2026-04-03

### 2.3 LeadSource — concluída em 2026-04-03
- [x] ⚙️ Migration `create_lead_sources_table` (id, school_id nullable, nome, is_system, is_active)
- [x] ⚙️ `app/Models/LeadSource.php` — BelongsToTenant condicional (nullable school_id)
- [x] ⚙️ `app/Services/LeadSource/LeadSourceService.php`
- [x] ⚙️ `app/Http/Controllers/Tenant/Settings/LeadSourceController.php`
- [x] 🧪 Teste: origem padrão (is_system=true) não pode ser editada ou excluída pelo gestor
- [x] 📋 Reviewer — APROVADO em 2026-04-03

### 2.4 Frontend — Configurações do Tenant
- [x] 🖥️ `pages/tenant-settings/SchoolYears.vue`
- [x] 🖥️ `pages/tenant-settings/LeadSources.vue`
- [x] 🖥️ `pages/tenant-settings/Grades.vue` — por segmento, configurável pelo gestor
- [ ] 📋 Reviewer

---

## ETAPA 3 — Alunos e Responsáveis

### 3.1 Backend
- [x] ⚙️ Migration `create_students_table` (id uuid, school_id, nome, cpf nullable, data_nascimento)
- [x] ⚙️ Migration `create_guardians_table` (id uuid, school_id, nome, cpf, telefone, email)
- [x] ⚙️ Migration `create_student_guardian_table` (pivot)
- [x] ⚙️ `app/Models/Student.php` — BelongsToTenant · auditing · índice (school_id, cpf)
- [x] ⚙️ `app/Models/Guardian.php` — BelongsToTenant · auditing · índice (school_id, cpf)
- [x] ⚙️ `app/Services/Student/StudentService.php` — `lookup(school, cpf)`, `findOrCreate()`
- [x] ⚙️ `app/Services/Guardian/GuardianService.php` — `lookup(school, cpf)`, `findOrCreate()`
- [x] ⚙️ `app/Http/Controllers/Tenant/StudentController.php` — inclui endpoint `GET /students/lookup/{cpf}`
- [x] ⚙️ `app/Http/Controllers/Tenant/GuardianController.php` — inclui endpoint `GET /guardians/lookup/{cpf}`
- [x] ⚙️ `app/Policies/StudentPolicy.php` · `GuardianPolicy.php`
- [x] 🧪 Teste: CPF duplicado no mesmo tenant falha · CPF em tenant diferente passa
- [x] 🧪 Teste: lookup retorna dados existentes · lookup de inexistente retorna 404
- [ ] 📋 Reviewer

### 3.2 Frontend — CPF Lookup
- [x] 🖥️ Composable `useCpfLookup.ts` — debounce 400ms, loading state, auto-preenchimento
- [x] 🖥️ Componente `CpfField.vue` — campo de CPF com lookup integrado
- [ ] 📋 Reviewer

---

## ETAPA 4 — Módulo de Oportunidades 🔴 BLOQUEANTE

> Núcleo de negócio. Tarefas dependem desta etapa.

### 4.1 Enums e Model
- [ ] ⚙️ `app/Enums/OpportunityStatus.php`
- [ ] ⚙️ Migration `create_opportunities_table` (id uuid, school_id, student_id, guardian_id, grade_id, school_year_id, lead_source_id, responsible_user_id, status enum, observations, created_at, updated_at)
- [ ] ⚙️ `app/Models/Opportunity.php` — BelongsToTenant · auditing · SoftDelete · relações
- [ ] ⚙️ `app/Observers/OpportunityObserver.php` — gera UUID no `creating`
- [ ] 🧪 Teste: não é possível criar oportunidade sem tenant ativo
- [ ] 📋 Reviewer

### 4.2 Service e Controller
- [ ] ⚙️ `app/Services/Opportunity/OpportunityService.php` — `create()`, `update()`, `assignResponsible()`, `list()`
- [ ] ⚙️ `app/Http/Requests/Opportunity/OpportunityStoreRequest.php`
- [ ] ⚙️ `app/Http/Requests/Opportunity/OpportunityUpdateRequest.php`
- [ ] ⚙️ `app/Policies/OpportunityPolicy.php` — Comercial só vê próprias oportunidades
- [ ] ⚙️ `app/Http/Controllers/Tenant/OpportunityController.php`
- [ ] ⚙️ Rotas em `routes/tenant.php`
- [ ] 🧪 Teste: Comercial não vê oportunidades de outro Comercial
- [ ] 🧪 Teste: Gestor vê todas as oportunidades do tenant
- [ ] 📋 Reviewer

### 4.3 Frontend
- [ ] 🖥️ `pages/opportunities/Index.vue` — kanban ou listagem, filtros por status/responsável
- [ ] 🖥️ `pages/opportunities/Create.vue` — formulário completo com CPF lookup
- [ ] 🖥️ `pages/opportunities/Show.vue` — detalhe + painel de tarefas
- [ ] 📋 Reviewer

---

## ETAPA 5 — Tarefas e Tabulações 🔴 BLOQUEANTE

### 5.1 Models e Migrations
- [ ] ⚙️ `app/Enums/TaskType.php` · `app/Enums/TaskStatus.php`
- [ ] ⚙️ Migration `create_tasks_table` (id uuid, school_id, opportunity_id, type enum, status enum, due_date, completed_at, outcome_id nullable, renitente_count, is_schedule, observations)
- [ ] ⚙️ Migration `create_outcomes_table` (id, name, slug, task_type, opens_window)
- [ ] ⚙️ Migration `create_outcome_actions_table` (id, outcome_id, action_type, payload json nullable)
- [ ] ⚙️ `app/Models/Task.php` — BelongsToTenant · auditing
- [ ] ⚙️ `app/Models/Outcome.php` — global (sem BelongsToTenant) · sem auditing
- [ ] ⚙️ `app/Models/OutcomeAction.php` — global · sem auditing
- [ ] 📋 Reviewer

### 5.2 Actions atômicas
- [ ] ⚙️ `app/Actions/Task/CreateTaskAction.php` — valida unicidade de tarefa open antes de criar
- [ ] ⚙️ `app/Actions/Task/CompleteTaskAction.php`
- [ ] ⚙️ `app/Actions/Task/CancelPendingTasksAction.php`
- [ ] ⚙️ `app/Actions/Opportunity/MoveOpportunityStatusAction.php` — valida status terminal
- [ ] 🧪 Teste: CreateTaskAction lança DomainException se já existe tarefa open na oportunidade
- [ ] 🧪 Teste: MoveOpportunityStatusAction rejeita mover status terminal
- [ ] 📋 Reviewer

### 5.3 RenitenteCycleService
- [ ] ⚙️ `app/Services/Task/RenitenteCycleService.php`
- [ ] 🧪 Teste: count 0 → +1h, count vira 1
- [ ] 🧪 Teste: count 1–5 → +3h cada, count incrementa
- [ ] 🧪 Teste: count 6 → RenitenteLimitReachedException, count reseta para 0
- [ ] 📋 Reviewer

### 5.4 OutcomeProcessorService
- [ ] ⚙️ `app/Services/Task/OutcomeProcessorService.php` — itera OutcomeActions, executa por action_type
- [ ] 🧪 Teste: `compareceu_agendamento` → move para Visita + cria Provável Matrícula
- [ ] 🧪 Teste: `nao_compareceu_agendamento` → cria Reagendamento com due_date +2 dias
- [ ] 🧪 Teste: `pre_matricula` → move para Matrícula
- [ ] 🧪 Teste: recusa sem categoria → ValidationException
- [ ] 🧪 Teste: transação revertida se qualquer Action falhar
- [ ] 🧪 Teste: `open_window` retornado corretamente para tabulações que abrem modal
- [ ] 📋 Reviewer

### 5.5 TaskService e Controller
- [ ] ⚙️ `app/Services/Task/TaskService.php` — `create()`, `complete()` (com DB::transaction)
- [ ] ⚙️ `app/Policies/TaskPolicy.php`
- [ ] ⚙️ `app/Http/Requests/Task/TaskStoreRequest.php`
- [ ] ⚙️ `app/Http/Requests/Task/TaskCompleteRequest.php` — valida outcome_id + payload de recusa
- [ ] ⚙️ `app/Http/Controllers/Tenant/TaskController.php`
- [ ] ⚙️ Rotas em `routes/tenant.php` (store, complete, cancel)
- [ ] 🧪 Teste: conclusão completa — task completed + opportunity atualizada + nova task criada
- [ ] 📋 Reviewer

### 5.6 Frontend — Tarefa e Tabulação
- [ ] 🖥️ `components/Task/TaskPanel.vue` — painel lateral na Show de oportunidade
- [ ] 🖥️ `components/Task/OutcomeSelector.vue` — modal de seleção de tabulação
- [ ] 🖥️ `components/Task/RefusalForm.vue` — categoria + detalhamento obrigatório
- [ ] 🖥️ `components/Task/TaskCreateModal.vue` — modal para criar próxima tarefa (acionado por open_window)
- [ ] 🖥️ Integrar `open_window` da response para acionar modal correto
- [ ] 📋 Reviewer

---

## ETAPA 6 — Notificações (Reverb)

### 6.1 Backend — Jobs e Listeners
- [ ] ⚙️ Migration `create_notifications_table`
- [ ] ⚙️ `app/Notifications/TaskDueNotification.php`
- [ ] ⚙️ `app/Notifications/TaskOverdueNotification.php`
- [ ] ⚙️ `app/Notifications/UnassignedLeadNotification.php`
- [ ] ⚙️ `app/Notifications/OpportunityStaleNotification.php`
- [ ] ⚙️ `app/Jobs/ProcessOverdueTasksJob.php` — schedule diário
- [ ] ⚙️ `app/Jobs/ProcessStaleOpportunitiesJob.php` — schedule configurável
- [ ] ⚙️ `app/Jobs/ProcessUnassignedLeadsJob.php` — schedule configurável
- [ ] ⚙️ Configurar Reverb broadcast channel por usuário
- [ ] 🧪 Teste: job dispara notification correta · Queue::fake() verifica dispatch
- [ ] 📋 Reviewer

### 6.2 Frontend — Sino de Notificações
- [ ] 🖥️ Configurar Laravel Echo com Reverb no `app.ts`
- [ ] 🖥️ Composable `useNotifications.ts` — escuta canal Reverb, badge com contador
- [ ] 🖥️ `components/NotificationBell.vue` — sino com dropdown de notificações recentes
- [ ] 🖥️ Integrar no `AppSidebarLayout.vue`
- [ ] 📋 Reviewer

---

## ETAPA 7 — Eventos e Salas

### 7.1 Backend
- [ ] ⚙️ Migration `create_event_types_table` (id, school_id, nome)
- [ ] ⚙️ Migration `create_rooms_table` (id, school_id, unit_id, nome, capacidade, is_external, status)
- [ ] ⚙️ Migration `create_events_table` (id uuid, school_id, titulo, data, hora_inicio, capacidade, sala_id, sem_data)
- [ ] ⚙️ Migration `create_opportunity_events_table` (pivot)
- [ ] ⚙️ `app/Models/Room.php` — BelongsToTenant · auditing
- [ ] ⚙️ `app/Models/Event.php` — BelongsToTenant · auditing · SoftDelete
- [ ] ⚙️ `app/Policies/EventPolicy.php` · `RoomPolicy.php`
- [ ] ⚙️ `app/Services/Event/EventService.php` — `create()`, `invite()`, verificação de conflito de sala (alerta, não bloqueia)
- [ ] ⚙️ `app/Http/Controllers/Tenant/EventController.php`
- [ ] ⚙️ `app/Http/Controllers/Tenant/RoomController.php`
- [ ] ⚙️ Rotas em `routes/tenant.php`
- [ ] 🧪 Teste: conflito de horário de sala emite alerta mas salva normalmente
- [ ] 🧪 Teste: vincular oportunidade ao evento gera job de criação de tarefa Evento
- [ ] 📋 Reviewer

### 7.2 Frontend
- [ ] 🖥️ `pages/events/Index.vue` · `Create.vue` · `Edit.vue`
- [ ] 🖥️ `pages/tenant-settings/Rooms.vue`
- [ ] 📋 Reviewer

---

## ETAPA 8 — Formulário Público de Captação

### 8.1 Backend
- [ ] ⚙️ Migration `create_lgpd_consents_table` (id, opportunity_id, guardian_id, ip, user_agent, accepted_at)
- [ ] ⚙️ `app/Models/LgpdConsent.php` — sem BelongsToTenant · sem auditing
- [ ] ⚙️ `app/Services/Lead/LeadCaptureService.php` — dentro de DB::transaction()
- [ ] ⚙️ `app/Http/Requests/Lead/LeadCaptureRequest.php`
- [ ] ⚙️ `app/Http/Controllers/Public/LeadCaptureController.php`
- [ ] ⚙️ Rotas em `routes/web.php` (GET e POST `/formulario/{slug}`)
- [ ] 🧪 Teste: envio sem LGPD aceito → falha validação
- [ ] 🧪 Teste: envio com LGPD → lgpd_consent registrado com IP e timestamp
- [ ] 🧪 Teste: slug inexistente → 404
- [ ] 🧪 Teste: CPF existente no tenant → reutiliza guardian/student existente
- [ ] 📋 Reviewer

### 8.2 Frontend
- [ ] 🖥️ `layouts/PublicLayout.vue` — sem menu, sem auth, com identidade visual da escola
- [ ] 🖥️ `pages/public/LeadCapture.vue` — formulário completo com CPF lookup e CEP lookup
- [ ] 🖥️ `pages/public/LeadCaptureConfirmation.vue` — página de confirmação pós-envio
- [ ] 📋 Reviewer

---

## ETAPA 9 — Calendário

### 9.1 Backend
- [ ] ⚙️ `app/Http/Controllers/Tenant/CalendarController.php` — retorna tasks is_schedule=true filtradas por perfil
- [ ] ⚙️ Rotas em `routes/tenant.php`
- [ ] 📋 Reviewer

### 9.2 Frontend
- [ ] 🖥️ `pages/calendar/Index.vue` — visualização mensal/semanal de tarefas agendadas
- [ ] 🖥️ Integração com componente de calendário (ex: FullCalendar ou customizado)
- [ ] 📋 Reviewer

---

## ETAPA 10 — Relatórios e Dashboard Macro

### 10.1 Backend
- [ ] ⚙️ `app/Http/Controllers/Tenant/ReportController.php` — queries otimizadas com índices
- [ ] ⚙️ `app/Http/Controllers/Admin/DashboardController.php` — consolidado cross-tenant
- [ ] 🧪 Teste: relatório de comercial não retorna dados de outro usuário
- [ ] 📋 Reviewer

### 10.2 Frontend
- [ ] 🖥️ `pages/reports/Index.vue` — funil, conversão, por comercial, por origem
- [ ] 🖥️ `pages/admin/Dashboard.vue` — macro consolidado
- [ ] 📋 Reviewer

---

## ETAPA 11 — LGPD e Auditoria

- [ ] ⚙️ Endpoint de exclusão total de dados a pedido do responsável (direito ao esquecimento)
- [ ] ⚙️ Tela de log de auditoria para Master e Admin (via `audits` do owen-it)
- [ ] 🧪 Teste: exclusão apaga guardian, student e opportunities vinculados
- [ ] 📋 Reviewer

---

## CHECKLIST FINAL DE RELEASE

- [ ] Todos os Reviewers aprovados em todas as etapas
- [ ] `php artisan test --compact` — zero falhas
- [ ] `vendor/bin/pint --dirty` — zero erros
- [ ] `npm run lint && npm run format` — zero erros
- [ ] Nenhuma query sem scope de tenant identificada
- [ ] Nenhum ID numérico exposto em URLs ou props Inertia
- [ ] LGPD: 100% dos envios do formulário registram lgpd_consent
- [ ] Auditoria: alterações de status e responsável geram audit_log
- [ ] Testes do ciclo Renitente: 7 cenários cobertos
- [ ] Testes do OutcomeProcessor: cada tabulação testada com assert de estado final