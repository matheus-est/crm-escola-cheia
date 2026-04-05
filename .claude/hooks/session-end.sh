#!/bin/bash
# session-end.sh
# Reminds Claude to update MEMORY.md before the session closes.
# Configured in .claude/settings.json under SessionEnd.

echo '{"decision":"block","reason":"Before ending: make sure MEMORY.md is updated with current state, new decisions, and any active pitfalls discovered this session."}'