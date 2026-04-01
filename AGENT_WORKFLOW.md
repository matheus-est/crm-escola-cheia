# AGENT_WORKFLOW.md
# Guia Operacional — Como Trabalhar com os Agents

> Objetivo: máxima clareza, mínimo de tokens desperdiçados, zero retrabalho.
> Leia uma vez, mantenha aberto durante o desenvolvimento.

---

## 1. O Princípio Fundamental

**Cada mensagem para um agent deve ter exatamente uma responsabilidade.**

Agent errado → resultado errado → retrabalho → tokens desperdiçados.
Mensagem vaga → agent pede esclarecimento → mais tokens.
Contexto repetido → tokens gastos sem produção.

---

## 2. Mapa de Agents — Quando Usar Cada Um

```
Você tem uma IDEIA em linguagem natural
        ↓
   Prompt Engineer   ← use aqui primeiro SEMPRE

O Prompt Engineer gera um prompt estruturado
        ↓
      Planner        ← recebe o prompt e decompõe em subtasks

Você APROVA o plano (etapa obrigatória — sem aprovação, nada avança)
        ↓
    Backend Dev      ← implementa PHP

    Reviewer         ← audita o backend

    Frontend Dev     ← implementa Vue/TS

    Reviewer         ← audita o frontend
```

**Regra de ouro:** se você não sabe qual agent invocar, use o Prompt Engineer.

---

## 3. Como Invocar Cada Agent — Comandos Diretos

### 3.1 Prompt Engineer
**Quando:** você tem uma ideia em linguagem natural.
**O que escrever:** a ideia, sem estrutura.

```
/prompt-engineer

Quero implementar a [nome da feature]. [2-3 frases descrevendo o que deve fazer].
```

**Exemplo real:**
```
/prompt-engineer

Quero o cadastro de escola com validação de CNPJ via BrasilAPI e busca
automática de endereço por CEP. Gestor/Admin criam, Master pode excluir.
```

O Prompt Engineer vai entregar um prompt completo e já dizer qual agent deve executá-lo.

---

### 3.2 Planner
**Quando:** você tem um prompt estruturado (do Prompt Engineer ou próprio).
**O que escrever:** o prompt estruturado. Nada mais.

```
/planner

[cole o prompt estruturado aqui]
```

**Você vai receber:** um plano com lista de arquivos + ordem de execução + critérios de aceitação.

**Sua resposta após receber o plano deve ser uma destas:**
- `aprovado` — segue para Backend
- `ajustar: [o que mudar]` — Planner revisa
- `cancelar` — descarta o plano

---

### 3.3 Backend Developer
**Quando:** plano aprovado por você.
**O que escrever:** apenas os itens do plano que o Backend deve executar **nesta rodada**.

```
/backend

Implemente os itens 1 a 4 do plano aprovado:

1. Migration create_schools_table — schema em TECHNICAL_PLAN.md §2.1
2. School model — BelongsToTenant, auditing, Observer
3. SchoolObserver — UUID + slug no creating
4. Testes: slug com colisão, CNPJ duplicado

Arquivos existentes a preservar: [liste se houver]
```

**Não inclua:** contexto do projeto (o agent já leu os docs), histórico de conversa, regras que já estão nos arquivos de config.

---

### 3.4 Reviewer
**Quando:** Backend ou Frontend terminar uma rodada.
**O que escrever:** o que foi implementado nesta rodada.

```
/reviewer

Revisar backend da Etapa 1.1 — School model e migration.

Arquivos criados/modificados nesta rodada:
- database/migrations/..._create_schools_table.php
- app/Models/School.php
- app/Observers/SchoolObserver.php
- tests/Feature/SchoolTest.php
```

**Você vai receber:** APROVADO ou REJEITADO + lista de problemas.

**Se REJEITADO:** envie apenas os problemas bloqueantes de volta ao Backend:
```
/backend

Corrigir os problemas apontados pelo Reviewer:
1. app/Models/School.php linha 12: $casts → método casts()
2. app/Observers/SchoolObserver.php: falta declare(strict_types=1)
```

---

### 3.5 Frontend Developer
**Quando:** Reviewer aprovar o backend.
**O que escrever:** itens do plano que o Frontend deve executar.

```
/frontend

Implemente o frontend da Etapa 1.3 — CRUD de Schools:

Páginas a criar (conforme TECHNICAL_PLAN.md §1.2):
- pages/admin/Schools/Index.vue
- pages/admin/Schools/Create.vue (com CNPJ lookup + CEP lookup)
- pages/admin/Schools/Edit.vue (slug readonly com banner de alerta)

Composable a criar:
- composables/useCepLookup.ts — debounce 400ms

Props que o Controller já retorna: [cole o trecho do plano]
```

---

## 4. Regras de Ouro para Economizar Tokens

### 4.1 Uma tarefa por mensagem
Nunca envie "implemente a etapa 1, 2 e 3". Envie uma etapa por vez.

### 4.2 Nunca repita contexto que já está nos arquivos de config
Os agents leem `PROJECT_CONTEXT.md` e `TECHNICAL_PLAN.md` automaticamente.
Não repita: "lembre-se de usar BelongsToTenant", "nunca use sync()", etc.
Se está nos arquivos, não escreva. Escreva apenas o que é **específico desta tarefa**.

### 4.3 Use referências, não transcrições
Em vez de copiar o schema da tabela, escreva:
`Schema em TECHNICAL_PLAN.md §2.1` ← o agent vai lá.

### 4.4 Aprovação é uma palavra, não um parágrafo
Quando o plano está bom: `aprovado`
Quando tem ajuste: `ajustar: [1 linha do que mudar]`

### 4.5 Rejeição tem formato fixo
Quando o Reviewer rejeita, copie apenas os **problemas bloqueantes** de volta ao agent.
Não copie as observações não-bloqueantes. Não escreva explicações.

### 4.6 Nunca peça "opinião" a um agent de implementação
Backend e Frontend não opiniões. Planner opina sobre estrutura. Prompt Engineer opina sobre escopo.

---

## 5. Templates de Mensagem — Copie e Cole

### Iniciar uma feature nova
```
/prompt-engineer

[Descreva a feature em 2-4 frases. Mencione quem pode acessar e qual regra de negócio central.]
```

### Aprovar plano e iniciar backend
```
aprovado

/backend

Implemente os itens [X a Y] do plano:
[lista dos itens exatamente como o Planner escreveu]
```

### Enviar para revisão de backend
```
/reviewer

Revisar backend — [nome da etapa/feature].

Arquivos desta rodada:
- [arquivo 1]
- [arquivo 2]
```

### Corrigir rejeição do Reviewer
```
/backend

Corrigir problemas bloqueantes apontados pelo Reviewer:
[cole apenas os problemas bloqueantes, um por linha]
```

### Iniciar frontend após backend aprovado
```
/frontend

Implemente o frontend — [nome da etapa].

[itens do plano para o frontend]

Props do Controller:
[cole o bloco "Contratos entre camadas" do prompt]
```

### Retomada após interrupção
```
[Para qualquer agent]

Retomada. Leia PROJECT_CONTEXT.md e TECHNICAL_PLAN.md.
Último estado: [descreva em 1 frase o que foi feito por último].
Próximo passo: [descreva em 1 frase o que deve ser feito].
```

---

## 6. Fluxo Completo — Exemplo Real da Etapa 1.1

```
Turno 1 — você:
/prompt-engineer
Quero implementar o cadastro de escola (tenant) com validação de CNPJ via
BrasilAPI e busca de endereço por CEP. Acesso: Admin, Operação, Master.

Turno 2 — Prompt Engineer:
[entrega prompt estruturado completo]
→ "Enviar para /planner"

Turno 3 — você:
/planner
[cola o prompt do Prompt Engineer]

Turno 4 — Planner:
[entrega plano com 8 subtasks]

Turno 5 — você:
aprovado

/backend
Implemente os itens 1 a 5 do plano (migration, model, observer, service, testes).

Turno 6 — Backend:
[implementa os 5 itens]

Turno 7 — você:
/reviewer
Revisar backend — Etapa 1.1 School.
Arquivos: [lista]

Turno 8 — Reviewer:
REJEITADO
Problemas bloqueantes:
- app/Models/School.php linha 8: $casts → método casts()

Turno 9 — você:
/backend
Corrigir:
- app/Models/School.php linha 8: $casts → método casts()

Turno 10 — Backend:
[corrige]

Turno 11 — você:
/reviewer
Re-revisar após correção.
Arquivo: app/Models/School.php

Turno 12 — Reviewer:
APROVADO

Turno 13 — você:
/backend
Implemente os itens 6 a 8 do plano (controller, requests, rotas).

[... continua ...]
```

**Total: 13 turnos para uma feature completa. Sem ambiguidade, sem retrabalho.**

---

## 7. Sinais de Problema — O Que Fazer

| Sinal | Causa provável | Ação |
|---|---|---|
| Agent pede esclarecimento | Mensagem vaga | Use o Prompt Engineer primeiro |
| Reviewer rejeita sempre os mesmos itens | Regra não está nos arquivos de config | Adicione em `PROJECT_CONTEXT.md §14` |
| Backend implementa arquivos errados | Plano não especificou paths exatos | Ajuste o plano no Planner |
| Frontend usa URL hardcoded | Wayfinder não gerado para a rota | Execute `php artisan wayfinder:generate` antes do Frontend |
| Agent ignora regra do projeto | Regra não está em nenhum arquivo | Adicione em `CLAUDE.md` ou no agent específico |
| Tokens muito altos por sessão | Tarefas muito grandes | Quebre em subtasks menores |

---

## 8. Referência Rápida — Ordem do DEVELOPMENT_PLAN.md

```
Etapa 0  — Fundação e Boilerplate        🔴 BLOQUEANTE
Etapa 1  — School (Tenant)               🔴 BLOQUEANTE
Etapa 2  — Entidades de Suporte          🔴 BLOQUEANTE
Etapa 3  — Alunos e Responsáveis
Etapa 4  — Oportunidades                 🔴 BLOQUEANTE
Etapa 5  — Tarefas e Tabulações          🔴 BLOQUEANTE
Etapa 6  — Notificações (RabbitMQ+Reverb)
Etapa 7  — Eventos e Salas
Etapa 8  — Formulário Público
Etapa 9  — Calendário
Etapa 10 — Relatórios e Dashboard
Etapa 11 — LGPD e Auditoria
```

**Regra:** etapas `🔴 BLOQUEANTE` precisam de Reviewer aprovado antes de iniciar a próxima.
As demais podem ser desenvolvidas em paralelo se houver capacidade.

---

## 9. Manutenção dos Arquivos de Config

Sempre que o Reviewer ou um Developer detectar um padrão novo de erro:
1. É registrado automaticamente em `PROJECT_CONTEXT.md §14`
2. Você não precisa fazer nada — os agents fazem isso
3. Se uma decisão de arquitetura nova for tomada, adicione ao **registro de decisões** em `prompt-engineer.md`

Quando uma etapa do `DEVELOPMENT_PLAN.md` for concluída:
1. Marque `[x]` nas tasks
2. Anote a data de conclusão ao lado da etapa
3. Comite o `DEVELOPMENT_PLAN.md` atualizado

---

## 10. Checklist Antes de Iniciar o Dia

- [ ] `DEVELOPMENT_PLAN.md` está com o status correto (tasks marcadas)?
- [ ] `PROJECT_CONTEXT.md §14` tem as lições aprendidas do dia anterior?
- [ ] Qual é a próxima tarefa não-bloqueante da etapa atual?
- [ ] O Reviewer aprovou a última rodada?
- [ ] `php artisan wayfinder:generate` executado após últimas rotas adicionadas?