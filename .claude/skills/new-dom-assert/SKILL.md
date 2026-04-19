---
name: new-dom-assert
description: Scaffold a new DOM assertion specialization — creates src/Dom/Asserts/Assert<Name>.php and tests/Unit/Assert<Name>Test.php following the BaseAssert + Pest pattern used by AssertForm, AssertSelect, and AssertDatalist.
disable-model-invocation: true
---

# new-dom-assert

Scaffold a new DOM assertion class that specializes `BaseAssert`, plus a Pest test file that drives it against an HTML fixture.

## Usage

User invokes with the target name (without the `Assert` prefix):

```
/new-dom-assert Dialog
```

→ creates `src/Dom/Asserts/AssertDialog.php` + `tests/Unit/AssertDialogTest.php`.

## Before you start

Ask the user these up front if not given:

1. **Name** — `Dialog`, `Table`, `Nav`, etc. Must be `PascalCase`, no `Assert` prefix, no suffix.
2. **Fixture path** — relative to `tests/Fixtures/html/`, e.g. `dialog.html`. If the user doesn't have one yet, create a minimal one containing the element type they're targeting.
3. **Selector** — CSS selector the test helper uses to scope into the fixture, e.g. `'dialog[open]'`. Defaults to the lowercase element name if unspecified.

Refuse to scaffold if:
- A file at either target path already exists (don't overwrite user work).
- The name isn't strict `PascalCase`.

## Files to create

### 1. `src/Dom/Asserts/Assert<Name>.php`

Template:

```php
<?php

declare(strict_types=1);

namespace Derteaser\KirbyTesting\Dom\Asserts;

final class Assert<Name> extends BaseAssert
{
    // Add specialization methods here. Delete this comment before committing.
    //
    // Magic dispatch from BaseAssert covers has<Attr>(), is<El>(), find<El>(),
    // contains<El>(), doesntContain<El>() out of the box. Only add explicit
    // methods for behaviour that can't be expressed via those prefixes —
    // e.g. AssertForm::hasAction() normalizes the comparison, AssertSelect::
    // hasValues() unwraps a collection.
}
```

Check existing specializations before writing any new method:

- [src/Dom/Asserts/AssertForm.php](src/Dom/Asserts/AssertForm.php) for attribute-normalising helpers (case-insensitive, slash-trimming).
- [src/Dom/Asserts/AssertSelect.php](src/Dom/Asserts/AssertSelect.php) for iterating scoped children.
- [src/Dom/Asserts/AssertDatalist.php](src/Dom/Asserts/AssertDatalist.php) for id-based scoping with explicit error messages.

### 2. `tests/Fixtures/html/<fixture>.html`

Only create this if the user says there isn't one. Keep it minimal — one instance of the target element with enough attributes/children to drive the first assertion the user mentions. Do not dump an entire page template.

### 3. `tests/Unit/Assert<Name>Test.php`

Template (matches the `AssertFormTest`/`AssertElementTest` style — `declare(strict_types=1)`, fixture-loader helper that throws on missing file, Pest `it()` blocks):

```php
<?php

declare(strict_types=1);

use Derteaser\KirbyTesting\Dom\Asserts\Assert<Name>;
use Symfony\Component\DomCrawler\Crawler;

function <camelName>Scope(): Assert<Name>
{
    $html = file_get_contents(__DIR__ . '/../Fixtures/html/<fixture>');

    if ($html === false) {
        throw new RuntimeException('Missing fixture: <fixture>');
    }

    return new Assert<Name>((new Crawler($html))->filter('<selector>')->first());
}

it('<describe the first assertion>', function () {
    <camelName>Scope()->/* fluent assertion here */;
});
```

Replace:
- `<Name>` → PascalCase name (e.g. `Dialog`)
- `<camelName>` → lowercase-first-letter (e.g. `dialog`)
- `<fixture>` → fixture filename (e.g. `dialog.html`)
- `<selector>` → CSS selector (e.g. `dialog[open]`)

## After scaffolding

1. Run `composer pint` on the two new files — the PostToolUse hook will usually beat you to it, but confirm.
2. Run `composer stan` — new files must pass PHPStan level 8.
3. Run `composer test` — the stub `it()` block won't assert anything yet, but the file must at least load without syntax or type errors.
4. Tell the user which methods they'll typically want to add, based on the element type (e.g. for `dialog`, point them at `isOpen()`, `hasAriaLabel()`).

## Don't

- Don't invent methods on the Assert class that the user didn't ask for. Stub empty and let them drive.
- Don't update [CLAUDE.md](CLAUDE.md) or [README.md](README.md) unless the user asks — new specializations are routine, not architectural.
- Don't add the new class to any registry, factory, or index — there isn't one. Consumers `use` it directly.
