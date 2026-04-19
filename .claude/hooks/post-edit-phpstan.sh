#!/usr/bin/env bash
# PostToolUse hook: run PHPStan when src/**.php is edited.
#
# Scope note: the *trigger* is src/ edits only, but the analysis itself runs
# against the whole project as configured in phpstan.neon.dist (both `src/`
# and `tests/`). This is intentional — tests are at level 8 too, and
# cross-file issues between src and tests get caught. PHPStan's per-file
# result cache keeps incremental runs fast.
#
# Errors go to stderr with exit code 2 so Claude sees the failure and can
# fix it before moving on.

set -uo pipefail

payload=$(cat)

command -v jq >/dev/null 2>&1 || exit 0

file=$(printf '%s' "$payload" | jq -r '.tool_input.file_path // empty' 2>/dev/null || printf '')

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
