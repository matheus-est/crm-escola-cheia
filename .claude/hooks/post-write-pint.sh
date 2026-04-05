#!/bin/bash
# post-write-pint.sh
# Runs Laravel Pint on any PHP file written by Claude Code.
# Configured in .claude/settings.json under PostToolUse → Write matcher.

FILE_PATH="${CLAUDE_TOOL_INPUT_PATH:-}"

if [[ "$FILE_PATH" == *.php ]]; then
    vendor/bin/pint "$FILE_PATH" --quiet 2>/dev/null || true
fi