---
name: release
description: Cut a new GitHub release — analyze changes since the last tag, suggest a semver bump, tag, push, and publish a release with grouped notes. Run this when the user asks "release", "cut a release", "tag a release", "publish v1.2.0", or similar.
disable-model-invocation: true
---

# release

Cut a new GitHub release for `derteaser/kirby-testing`.

You walk the user through four phases (preflight → analyze → draft → execute). You stop for a single confirmation before the execute phase because tagging, pushing, and publishing are all externally visible actions.

## Phase 1 — Preflight

Refuse (don't just warn) to proceed if any of these fail. Print what you checked and why it blocks.

```bash
git status --short                 # must be empty
git rev-parse --abbrev-ref HEAD    # must be main
git fetch origin --tags --quiet
git rev-list --left-right --count origin/main...main  # must be "0 0"
```

If the user invoked `/release <version>`, remember the argument — it's their explicit version override. Otherwise you'll suggest one in Phase 2.

## Phase 2 — Analyze

Gather facts:

```bash
last=$(git describe --tags --abbrev=0)
git log "$last"..HEAD --format='%h %s'
git diff "$last"..HEAD --stat -- composer.json src/
git diff "$last"..HEAD -- composer.json                # for require changes
```

Classify each commit as **breaking / feature / fix / internal / docs / deps**. Go beyond emoji prefixes — actually read the diffs when it matters. Specific things to look for:

- **Breaking**: removed public methods; narrowed param/return types in `src/`; raised a floor in `require` (e.g. PHP min bumped from 8.3 → 8.4); changed throwing behaviour on a public method; renamed a public class/trait.
- **Feature**: new public method, new assertion class, new opt-in trait, new `@method` annotation, new composer script.
- **Fix**: behaviour corrections without API change.
- **Internal**: PHPStan types, Pint formatting, refactors, hook scripts, docs, CI, dev-only deps.
- **Deps (consumer-visible)**: widened `require` constraints (→ minor), bumped floors (→ possibly major), new transitive via explicit pin (→ minor).

Note any pull-request numbers from commit subjects (`(#6)`, `(#8)`) — the notes should link to them.

## Phase 3 — Suggest version and draft notes

### Version suggestion

Apply semver strictly to what you found:

| Trigger | Bump |
|---|---|
| Anything in **Breaking** | major |
| New public API, no breaks | minor |
| Only **Fix** or **Deps (widening)** | patch |
| Only **Internal** or **Docs** | usually no release |

For pre-1.0 repos, minor and major compress (breaking → minor, feature → patch). This repo is past 1.0, so full semver applies.

Present the suggestion to the user with one-sentence reasoning per triggering commit:

```
Suggested: v1.1.0 (minor)

Why minor:
  - #6  Widened PHP/Kirby/Symfony/PHPUnit constraints (new installable platforms → minor)
  - #8  Added Pint + PHPStan tooling, @param generics across public API (non-breaking additions → minor)

Nothing in the diff removes or narrows public API, so this is not a major.
```

If the user overrides, accept it without argument — but if they pick something that contradicts a breaking change you found, flag it once:

```
You picked patch. Heads up: #12 changed TestResponse::assertSee signature from array
to list<string>, which is a narrowing. Patch consumers on `^1.2` will break. Still
proceed?
```

### Release notes

Write Markdown, grouped by the classifications above, omitting empty groups. Format:

```markdown
## Highlights
One or two sentences on what this release unlocks for consumers. Skip if purely internal.

## Breaking changes
- **X** — what it was, what it is now, how to migrate. One bullet per break. (#PR)

## New features
- Short imperative description. (#PR)

## Fixes
- Short imperative description. (#PR)

## Compatibility
Only include when `require` changed. One line per dependency: "PHP: 8.4 → 8.3/8.4/8.5".

## Internal
One line, collapsed. e.g. "Adopted Pint + PHPStan level 8 internally; no consumer impact."

**Full Changelog**: https://github.com/derteaser/kirby-testing/compare/<last>...<next>
```

Show the draft to the user. Edit it based on their feedback. Keep iterating until they approve.

## Phase 4 — Confirm and execute

Print a single summary of exactly what you're about to do, then ask once:

```
About to release v1.1.0:
  1. git tag -a v1.1.0 -m "Release v1.1.0"
  2. git push origin v1.1.0
  3. gh release create v1.1.0 --title "v1.1.0" --notes-file <tmp>

Proceed?
```

Wait for explicit yes. Then:

```bash
tag=v1.1.0
notes_file=$(mktemp)
cat > "$notes_file" <<'EOF'
# ... the approved notes ...
EOF

git tag -a "$tag" -m "Release $tag"
git push origin "$tag"
gh release create "$tag" --title "$tag" --notes-file "$notes_file"
rm "$notes_file"
```

Print the release URL from `gh`'s output.

## The composer.json version question

This repo deliberately has **no `version` field in composer.json** — tags are the source of truth and that's the Composer/Packagist idiomatic setup. The skill must:

1. `grep '"version"' composer.json` — if absent, skip any composer.json edit. Do not add a `version` field "to be helpful." It causes drift and Packagist warns against it.
2. If a `version` field IS present (e.g. future policy change, fork with different conventions), update it, commit with a message like `🔖 Bump to v1.2.3`, and push that commit *before* the tag. Ask the user whether to PR or push direct; default to direct-push since it's a one-liner on `main`.

Same check applies to other version strings — currently there are none in `src/`, `README.md`, or `CLAUDE.md`. If a future change adds one (e.g. a `const VERSION` in a class), grep for it before tagging so we don't ship a stale string.

## Don'ts

- **Don't** use `--force`, `--force-with-lease`, or `-f` on anything.
- **Don't** delete or rewrite existing tags. If the user needs to fix a botched release, that's a separate (manual) conversation.
- **Don't** auto-create a CHANGELOG.md. The authoritative log lives on GitHub Releases for this project. Add a CHANGELOG only if the user explicitly asks.
- **Don't** use `gh release create --generate-notes` as a shortcut for a real release. The auto-generated format is boilerplate ("**Full Changelog**: ..." was what v1.0.0 got) and loses the breaking/feature/fix structure. Always write notes via `--notes-file`.
- **Don't** tag from a dirty or out-of-sync working tree. The preflight check is non-negotiable.

## Failure modes and recovery

- **Tag push rejected**: Someone else tagged between your fetch and push. Stop, re-run preflight, re-analyze (there may be new commits), let the user decide.
- **Release creation fails after tag pushed**: Don't delete the tag. Run `gh release create "$tag" --title "$tag" --notes-file "$notes_file"` again; the tag is already in place, so it's safe.
- **User changes their mind mid-execute**: If the tag was created but not pushed, `git tag -d <tag>` locally is safe. If pushed, stop and surface the state — deleting a published tag needs user sign-off.
