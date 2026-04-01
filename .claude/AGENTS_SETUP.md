# AGENTS_SETUP.md

> Documentação de onboarding para desenvolvedores.
> Explica a estrutura de agents deste projeto e como configurá-la em novos projetos.
> Este arquivo não é lido automaticamente por nenhuma ferramenta — é documentação humana.

---

## Estrutura de arquivos

```
projeto/
├── CLAUDE.md                        # Lido automaticamente pelo Claude Code
├── PROJECT_CONTEXT.md               # Contexto do domínio — lido por todos os agents
├── TECHNICAL_PLAN.md                # Estrutura técnica — lido por todos os agents
└── .claude/
    ├── AGENTS_SETUP.md              # Este arquivo (documentação humana)
    └── agents/
        ├── planner.md               # Agent de planejamento
        ├── backend-developer.md     # Agent de backend PHP
        ├── frontend-developer.md    # Agent de frontend Vue/TS
        └── reviewer.md              # Agent de revisão e QA
```

| Arquivo | Lido por | Propósito |
|---|---|---|
| `CLAUDE.md` | Claude Code (automático) | Instruções operacionais — fluxo, regras delta, validações |
| `PROJECT_CONTEXT.md` | Todos os agents (instrução explícita) | Stack, models, convenções, entidades |
| `TECHNICAL_PLAN.md` | Todos os agents (instrução explícita) | Pastas, rotas, fluxos técnicos |
| `.claude/agents/*.md` | Claude Code (automático) | System prompt de cada agent especializado |

---

## Como usar em um novo projeto baseado neste boilerplate

1. Copie os arquivos de referência para a raiz do novo projeto:
   `CLAUDE.md`, `PROJECT_CONTEXT.md`, `TECHNICAL_PLAN.md`

2. Copie a pasta `.claude/` inteira (incluindo `agents/`)

3. Preencha as seções marcadas como `_(preencher ao iniciar projeto)_` em:
   - `PROJECT_CONTEXT.md` — seção 1 (identificação) e seção 8 (escopo)
   - `TECHNICAL_PLAN.md` — ajuste rotas e estrutura conforme o novo domínio

4. Atualize `PROJECT_CONTEXT.md` e `TECHNICAL_PLAN.md` sempre que houver
   mudanças estruturais — os agents dependem desses arquivos para tomar decisões corretas

---

## Dependências do ambiente

- **Laravel Boost MCP** ativo — fornece `search-docs`, `database-schema`,
  `database-query`, `browser-logs` e `get-absolute-url`
- **Claude Code** como interface — lê `CLAUDE.md` e `.claude/agents/` automaticamente
