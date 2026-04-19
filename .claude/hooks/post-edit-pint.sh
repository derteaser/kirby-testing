#!/usr/bin/env bash
# PostToolUse hook: format PHP files with Laravel Pint after Edit/Write/MultiEdit.
#
# - Runs silently (in-place fix, no chatty output).
# - Skips if Pint isn't installed (fresh clone, no vendor yet).
# - Only touches files inside the project (absolute path must start with
#   $CLAUDE_PROJECT_DIR) so hooks don't reformat unrelated paths.

set -euo pipefail

payload=$(cat)

# jq is the only external dependency. Silently skip if it's not installed —
# hooks must never block a session on missing optional tooling.
command -v jq >/dev/null 2>&1 || exit 0

file=$(printf '%s' "$payload" | jq -r '.tool_input.file_path // empty' 2>/dev/null || printf '')

# No path, nothing to do.
[[ -z "$file" ]] && exit 0

# Only .php files.
[[ "$file" == *.php ]] || exit 0

# Only files inside this project.
project_dir="${CLAUDE_PROJECT_DIR:-$(pwd)}"
[[ "$file" == "$project_dir"/* ]] || exit 0

# Pint missing? Silently skip so the hook never blocks a fresh clone.
[[ -x "$project_dir/vendor/bin/pint" ]] || exit 0

cd "$project_dir"
vendor/bin/pint "$file" >/dev/null 2>&1 || true
