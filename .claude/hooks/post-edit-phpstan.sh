#!/usr/bin/env bash
# PostToolUse hook: run PHPStan after edits inside src/.
#
# Strategy: analyse the whole project (PHPStan's per-file cache makes this
# fast on incremental edits) using the project's phpstan.neon.dist config.
# If errors are found, emit them to stderr with exit code 2 so Claude sees
# the failure and can fix it before moving on.

set -uo pipefail

payload=$(cat)
file=$(printf '%s' "$payload" | jq -r '.tool_input.file_path // empty')

[[ -z "$file" ]] && exit 0
[[ "$file" == *.php ]] || exit 0

project_dir="${CLAUDE_PROJECT_DIR:-$(pwd)}"
[[ "$file" == "$project_dir"/src/* ]] || exit 0
[[ -x "$project_dir/vendor/bin/phpstan" ]] || exit 0
[[ -f "$project_dir/phpstan.neon.dist" ]] || exit 0

cd "$project_dir"
output=$(vendor/bin/phpstan analyse --no-progress --memory-limit=512M 2>&1)
status=$?

if [[ $status -ne 0 ]]; then
    printf 'PHPStan level 8 failed after edit:\n\n%s\n' "$output" >&2
    exit 2
fi
