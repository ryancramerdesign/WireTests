# ProcessWire Test Suite

WireTests is a simple, runnable test suite for ProcessWire modules and core classes. Tests verify
that modules work as intended and as documented — covering field creation, value
storage/retrieval, output formatting, selectors, and more. Each test file corresponds
to one ProcessWire module or class, and is skipped automatically if that module is
not installed.

The module currently ships with tests focused on Fieldtype modules, but it has no
dependency on any particular module type — tests can cover Fieldtypes, Inputfields,
Process modules, core classes, or anything else in the ProcessWire API.

Designed to help core developers and module authors catch regressions, and as a
starting point for anyone who wants to contribute new tests.


## Requirements

- ProcessWire 3.0.259 or newer
- PHP 8.0+
- `$config->useFunctionsAPI=true;` in /site/config.php


## Installation

We have tested with and recommend installing this with ProcessWire's `site-blank` default 
installation profile, but technically it should work with any installation profile. Note
that this module creates a template named `test` and a page named `/test/` so verify
that you don't already have a template/page with the same names.

1. Copy or clone this module into your `/site/modules/WireTests/` directory.
2. In the ProcessWire admin go to **Modules > Refresh**, then install **Wire Tests**.
3. Installation creates a hidden `/test/` page (template: `test`) used as the test page
   that all test files can read from and write to.


## Running tests

Tests can be run either from the command line or from the admin in the WireTests 
module configuration screen.

### From the command line (recommended)

From your ProcessWire root directory:

```bash
# Run a single test
php index.php test FieldtypeText

# Run all tests
php index.php test all

# List all tests (command help)
php index.php
```

Test names match the module/class name exactly (e.g. `FieldtypeText`, `FieldtypeOptions`).
Tests for modules that are not installed are skipped automatically.

### From the admin

Go to **Modules > Configure > WireTests**, choose a test from the dropdown, and submit.
Results appear below the form.


## Included tests

The following Fieldtype tests ship with the module. Each test creates its own field
(if not already present), adds it to the `test` template, performs read/write/selector
checks, and cleans up after itself on uninstall.

| Test file | What it covers |
|---|---|
| `FieldtypeCheckbox` | Boolean 0/1 storage, output formatting |
| `FieldtypeDatetime` | Date/time storage, PHP date strings, timestamp input, selectors |
| `FieldtypeDecimal` | Decimal storage, precision, comparison selectors |
| `FieldtypeEmail` | Email storage, sanitization, selectors |
| `FieldtypeFile` | File upload/storage/retrieval |
| `FieldtypeFloat` | Float storage, precision, comparison selectors |
| `FieldtypeImage` | Image upload/storage/retrieval |
| `FieldtypeInteger` | Integer storage, comparison selectors |
| `FieldtypeOptions` | Single/multi-select options, set by ID/title/value, selectors |
| `FieldtypePage` | Page references (single and multiple), selectors |
| `FieldtypePageTable` | PageTable child page creation and retrieval |
| `FieldtypeRepeater` | Repeater item creation, value storage, retrieval |
| `FieldtypeRepeaterMatrix` | RepeaterMatrix types, item creation, retrieval |
| `FieldtypeSelector` | Selector field storage and retrieval |
| `FieldtypeTable` | Table row storage, column types, retrieval |
| `FieldtypeText` | Text storage, textformatters, selectors |
| `FieldtypeTextarea` | Textarea storage, selectors |
| `FieldtypeToggle` | Toggle (0/1) storage, output formatting |
| `FieldtypeURL` | URL storage, scheme sanitization, `noRelative` setting, selectors |
| `FieldtypeCustom` | Subfield definition file, JSON storage, rename migration, selectors |
| `FieldtypeCombo` | Typed subfields, select formatting, field config API, subfield CRUD |


## Writing your own test

### File naming and location

Create a PHP file in the `site/modules/WireTests/tests/` directory. Name it after the module it tests,
exactly matching the module/class name:

```
tests/FieldtypeMyModule.php
```

The test is skipped automatically if `FieldtypeMyModule` is not installed, so it is
safe to include tests for optional or third-party modules.

### File structure

Below is a contrived simple test just to demonstrate the basics. 

```php
<?php namespace ProcessWire;
/** @var Page $page */

// All ProcessWire API variables are in scope: $pages, $fields, $templates,
// $modules, $sanitizer, $config, $user, etc.
// $page is the pre-created hidden /test/ page (template: "test", of=false).

$a = 1; 
$b = 2;
$passed = $a < $b; // replace with your own test logic

if($passed) {
    // use the wireTests()->li('text') to show status
    wireTests()->li("Ok: 1 < 2"); 
} else {
    // throw WireTestException when test fails
    throw new WireTestException("Oops: 1 > 2"); 
}

// Reaching this point without throwing = test passed
```

The example below demonstrates the file structure for a Fieldtype test. 
For more and better examples, see the files in the `tests/` directory.

```php
<?php namespace ProcessWire;
/** @var Page $page */

// All ProcessWire API variables are in scope: $pages, $fields, $templates,
// $modules, $sanitizer, $config, $user, etc.
// $page is the pre-created hidden /test/ page (template: "test", of=false).

$name = 'my_field_name';
$field = fields()->get($name);

// Create the field if it does not already exist
if(!$field) {
    $field = fields()->new('FieldtypeMyModule', $name, 'My Field');
    wireTests()->li("Created field: $name");
}

// Add field to the test template if not already there
$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
    $fieldgroup->add($field);
    $fieldgroup->save();
}

// Write a value
$page->set($name, 'some value');
$page->save($name);

// Read it back from a fresh page load
$fresh = pages()->getFresh($page->id);
if($fresh->get($name) !== 'some value') {
    throw new WireTestException("Value mismatch: got " . var_export($fresh->get($name), true));
}
wireTests()->li("Value round-trip verified");

// Test a selector
$match = pages()->findOne("template=test, $name='some value'");
if($match->id !== $page->id) {
    throw new WireTestException("Selector failed: $name='some value'");
}
wireTests()->li("Selector passed: $name='some value'");

// Reaching this point without throwing = test passed
```

### Key conventions

| Thing | Convention                                                                            |
|---|---------------------------------------------------------------------------------------|
| **Fail** | Throw `WireTestException('reason')`                                                   |
| **Pass** | Reach end of file without throwing                                                    |
| **Status output** | `wireTests()->li('message')`                                                          |
| **Fresh page load** | `pages()->getFresh($page->id)`                                                        |
| **Output formatting off** | `$page->of(false)` before setting/saving values                                       |
| **Field already exists** | Check with `fields()->get($name)` and skip creation |
| **Idempotent setup** | Guard any one-time setup (adding options, creating child pages, etc.) so it's safe to run more than once |

### Available helpers

```php
wireTests()->li('message');    // output a status line
wireTests()->note('message');  // output a plain note
```

### Tips

- **Make tests idempotent.** A test may run many times against the same database.
  Skip setup steps (field creation, option population) if they already exist.
- **Restore state when you change field settings.** If a test modifies a field setting
  (e.g. `$field->noRelative = 1`), restore the original value before the test ends.
- **Test the documented API, not just the happy path.** Include edge cases like empty
  values, sanitization, and selector operators.
- **Use `$page->of(false)` before modifying and saving.** Output formatting should be off when
  writing values, and explicitly turned on when testing formatted output.


## How the test runner works

1. `runTests()` iterates every `.php` file in `tests/`, sorted by filename.
2. For each file whose basename matches an installed module name, it `include()`s the file
   inside a `try/catch` block.
3. `WireTestException` → test fails (message shown). Any other `Throwable` → test fails.
4. No exception → test passes.
5. A summary line is printed at the end showing passed/failed counts.


## Contributing

Pull requests with new or improved tests are welcome. Please follow the conventions
above and make sure your test is idempotent (safe to run multiple times). If the test
covers a module that is not part of the ProcessWire core, include a note in the test
file about where the module can be obtained.
