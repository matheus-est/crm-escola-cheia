#!/bin/bash
# pre-bash-guard.sh
# Blocks destructive commands that should never run automatically.
# Configured in .claude/settings.json under PreToolUse → Bash matcher.

COMMAND="${CLAUDE_TOOL_INPUT_COMMAND:-}"

BLOCKED_PATTERNS=(
    "php artisan migrate:fresh"
    "php artisan db:wipe"
    "php artisan migrate:reset"
    "git push --force"
    "git push -f"
    "rm -rf"
    "DROP TABLE"
    "DROP DATABASE"
)

for pattern in "${BLOCKED_PATTERNS[@]}"; do
    if echo "$COMMAND" | grep -qF "$pattern"; then
        echo "{\"decision\":\"block\",\"reason\":\"Blocked: '$pattern' requires explicit human confirmation before running.\"}"
        exit 0
    fi
done

exit 0