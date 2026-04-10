Leia os seguintes arquivos antes de qualquer alteração:
- MEMORY.md
- DEVELOPMENT_PLAN.md
- resources/js/pages/opportunities/Show.vue
- resources/js/types/crm.ts
- app/Http/Controllers/Tenant/OpportunityController.php
- app/Http/Resources/OpportunityShowResource.php (se existir)

---

# TAREFA: Redesenhar resources/js/pages/opportunities/Show.vue

O layout atual deve ser substituído pelo layout descrito abaixo.
Não quebre o comportamento existente de tabulação (OutcomeModal) e criação de tarefa (TaskCreateModal).

---

## SEÇÃO 1 — Header: Pipeline de Etapas

Renderizar uma barra horizontal no topo da página com as etapas do funil em sequência.

Etapas fixas (nesta ordem): Cadastro Inicial → Agendamento → Visita → Matrícula

Cada step tem um dos três estados visuais:
- completed: círculo preenchido teal com ícone de check branco + linha conectora teal preenchida
- active: círculo com spinner animado azul + texto azul + linha conectora azul parcial
- pending: círculo outline cinza + texto cinza + linha conectora cinza vazia

Layout do header:
- Lado esquerdo: texto "Etapa da Oportunidade" em font-medium
- Centro: ícone Clock + "{N} dias" onde N vem de opportunity.days_in_stage
- Lado direito: avatar circular + nome do usuário logado (usePage().props.auth.user.name)

Os stages vêm de uma prop nova chamada funnel_stages com estrutura:
  { label: string, slug: string, state: 'completed' | 'active' | 'pending' }[]

Se essa prop não existir no OpportunityController::show(), crie-a.
O cálculo do state de cada etapa deve ser baseado no opportunity.status atual.
Mapeamento sugerido:
  - status 'cadastro_inicial' → Cadastro Inicial: active, demais: pending
  - status 'agendamento' → Cadastro Inicial: completed, Agendamento: active, demais: pending
  - status 'visita' → dois primeiros: completed, Visita: active, Matrícula: pending
  - status 'matricula' → todos completed

---

## SEÇÃO 2 — Bloco de Informações do Lead

Dois painéis lado a lado separados por um divisor vertical.

Painel esquerdo — Responsável:
- Label pequeno cinza: "Responsável"
- Nome em font-bold tamanho maior: opportunity.guardian.name
- Linha com ícone Phone + opportunity.guardian.phone
- Linha com ícone Mail + opportunity.guardian.email
- Linha com ícone CreditCard + opportunity.guardian.cpf

Painel direito — Aluno:
- Label pequeno cinza: "Aluno"
- Nome em font-bold tamanho maior: opportunity.student.name
- Linha com ícone BookOpen + "{opportunity.grade.name} - {opportunity.school_year.name}"
- Linha com ícone Building2 + opportunity.school_unit.name
- Linha com ícone Info + opportunity.segment.name

Todos os dados vêm das props já existentes no OpportunityController::show().
Se alguma relação estiver faltando no Resource/Controller, adicione-a.

---

## SEÇÃO 3 — Área principal: Sidebar + Conteúdo em 3 abas

### Sidebar esquerda (largura fixa ~w-44)

Menu vertical com 3 itens clicáveis:
- Ícone History + texto "Histórico"
- Ícone CheckSquare + texto "Próximas Tarefas"
- Ícone Info + texto "Mais Informações"

Controle de aba: ref<'historico' | 'tarefas' | 'info'>('historico') — estado local Vue, sem router push.
NÃO usar shadcn Tabs — usar botões manuais + v-show (padrão do projeto).

Item ativo: classe bg-teal-50 text-teal-700 font-semibold rounded-lg
Item inativo: classe text-gray-600 hover:bg-gray-100 rounded-lg

---

### Aba: Histórico (v-show="activeTab === 'historico'")

Timeline vertical com as tarefas já finalizadas ou canceladas.

Estrutura visual da timeline:
- Linha vertical conectora: border-l-2 border-teal-600 ml-1.5
- Bolinha de cada item: w-3 h-3 rounded-full bg-teal-600 -ml-1.5

Cada item da timeline exibe:
- Título em font-semibold: task.task_type.name
- Status abaixo do título em text-sm text-gray-500: label do task.status
- Ícone CalendarDays + "Data de Início: {task.started_at formatado DD/MM/YYYY}"
- Ícone CalendarDays + "Previsão de Término: {task.due_date formatado DD/MM/YYYY}"
- Ícone MessageSquare + comentário/observação da task
- Alinhado à direita: "Finalizada em:" em text-xs text-gray-400 + data e hora DD/MM/YYYY às HH:mm

Filtro: apenas tasks com status 'completed' ou 'cancelled'
Ordenação: completed_at DESC

---

### Aba: Próximas Tarefas (v-show="activeTab === 'tarefas'")

Timeline vertical com as tarefas abertas/pendentes.
Mesma estrutura visual da timeline do Histórico.

Cada item exibe:
- Título em font-semibold: task.task_type.name
- Ícone CalendarDays + "Previsão de Término: {task.due_date formatado DD/MM/YYYY}"
- Ícone MessageSquare + comentário da task
- Alinhado à direita: "Iniciado em:" em text-xs text-gray-400 + data e hora DD/MM/YYYY às HH:mm

Filtro: apenas tasks com status 'pending' ou 'open'
Ordenação: due_date ASC

---

### Aba: Mais Informações (v-show="activeTab === 'info'")

Dois cards com classes: border border-gray-200 rounded-lg p-4 bg-white mb-4

Card 1 — Endereço:
Título "Endereço" em font-semibold mb-3
Grid interno 2 colunas com os campos abaixo.
Cada campo: label em text-xs text-gray-400 acima + valor em text-sm abaixo.
- CEP (col-span-2): opportunity.guardian.zip_code
- Logradouro: opportunity.guardian.street
- Número: opportunity.guardian.number
- Bairro: opportunity.guardian.neighborhood
- Cidade: opportunity.guardian.city
- Estado: opportunity.guardian.state
- Complemento (col-span-2): opportunity.guardian.address_complement

Card 2 — Indicação:
Título "Indicação" em font-semibold mb-3
Campos em coluna única:
- Nome: opportunity.indication_name
- Cargo: opportunity.indication_role

Verifique os nomes exatos desses campos no model Opportunity antes de usar.
Se os campos se chamarem diferente, use os nomes corretos.

---

## REGRAS OBRIGATÓRIAS DO PROJETO

1. Nenhum dado hardcoded — tudo via props Inertia
2. Rotas tenant via Wayfinder sem school_uuid — ex: route('opportunities.show', { opportunity: uuid })
3. Campos mascarados (CPF, CEP, telefone) devem ser exibidos como vêm do banco, nunca reformatar
4. NÃO usar shadcn Tabs — botões manuais + v-show
5. Sem TypeScript any — tipar usando interfaces existentes em crm.ts; adicionar interfaces novas se necessário
6. Não criar subcomponentes separados — manter tudo em Show.vue (padrão da tela)
7. Manter intacto o comportamento do OutcomeModal e TaskCreateModal existentes
8. Se adicionar props novas no Controller, verificar se o OpportunityShowResource (ou equivalente) precisa ser atualizado

---

## ENTREGÁVEIS

Ao concluir, liste os arquivos modificados e rode npm run lint corrigindo todos os erros de TypeScript antes de reportar como feito.