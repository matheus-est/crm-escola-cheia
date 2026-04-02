# CLAUDE.md

## Ao iniciar qualquer sessão
Leia `MEMORY.md` — é o único arquivo obrigatório.
Consulte `PROJECT_CONTEXT.md` ou `TECHNICAL_PLAN.md` só se `MEMORY.md` não responder.

## Agents (`.claude/agents/`)
```
ideia → /prompt-engineer → /planner → [aprovação sua] → /backend → /reviewer → /frontend → /reviewer
```

## Ao encerrar qualquer tarefa
```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run lint && npm run format
```
Atualizar `MEMORY.md` obrigatoriamente.

## Retomada
```
Leia MEMORY.md. Informe: (1) o que está pronto (2) o que falta (3) por onde retomar.
Aguarde confirmação antes de continuar.
```