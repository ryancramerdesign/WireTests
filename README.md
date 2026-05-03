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

The following tests ship with the module.

Fieldtype tests create their own field (if not already present), add it to the `test`
template, perform read/write/selector checks, and clean up after themselves on uninstall.
Core class tests call API methods directly and verify return values.

| Test file                 | What it covers                                                                                            |
|---------------------------|-----------------------------------------------------------------------------------------------------------|
| `Modules`                 | get, install, uninstall, findByPrefix/Flag/Info, getModuleInfo, config get/save, helper classes           |
| `Pages`                   | get, find, findIDs, getRaw, findRaw, getFresh, add, new, save, clone, cache, sort, trash, restore, delete |
| `Sanitizer`               | Text, names, numbers, booleans, URLs, arrays, HTML entities, validation, truncation, chaining             |
| `WireDatabasePDO`         | Connection access, queries, transactions, schema inspection, sanitization, info, query log, backups       |
| `WireInput`               | GET/POST/COOKIE/whitelist input, inline sanitization, URL segments, page numbers, URLs, query strings     |
| `FieldtypeCheckbox`       | Boolean 0/1 storage, output formatting                                                                    |
| `FieldtypeDatetime`       | Date/time storage, PHP date strings, timestamp input, selectors                                           |
| `FieldtypeDecimal`        | Decimal storage, precision, comparison selectors                                                          |
| `FieldtypeEmail`          | Email storage, sanitization, selectors                                                                    |
| `FieldtypeFile`           | File upload/storage/retrieval                                                                             |
| `FieldtypeFloat`          | Float storage, precision, comparison selectors                                                            |
| `FieldtypeImage`          | Image upload/storage/retrieval                                                                            |
| `FieldtypeInteger`        | Integer storage, comparison selectors                                                                     |
| `FieldtypeOptions`        | Single/multi-select options, set by ID/title/value, selectors                                             |
| `FieldtypePage`           | Page references (single and multiple), selectors                                                          |
| `FieldtypePageTable`      | PageTable child page creation and retrieval                                                               |
| `FieldtypeRepeater`       | Repeater item creation, value storage, retrieval                                                          |
| `FieldtypeRepeaterMatrix` | RepeaterMatrix types, item creation, retrieval                                                            |
| `FieldtypeSelector`       | Selector field storage and retrieval                                                                      |
| `FieldtypeTable`          | Table row storage, column types, retrieval                                                                |
| `FieldtypeText`           | Text storage, textformatters, selectors                                                                   |
| `FieldtypeTextarea`       | Textarea storage, selectors                                                                               |
| `FieldtypeToggle`         | Toggle (0/1) storage, output formatting                                                                   |
| `FieldtypeURL`            | URL storage, scheme sanitization, `noRelative` setting, selectors                                         |
| `FieldtypeCustom`         | Subfield definition file, JSON storage, rename migration, selectors                                       |
| `FieldtypeCombo`          | Typed subfields, select formatting, field config API, subfield CRUD                                       |


## Writing your own test

### File naming and location

Create a PHP file in the `site/modules/WireTests/tests/` directory. Name it after the
module or core class it tests, exactly matching the class name:

```
tests/FieldtypeMyModule.php   # module test
tests/Sanitizer.php           # core class test
```

Module tests are skipped automatically if the module is not installed, so it is safe to
include tests for optional or third-party modules. Core ProcessWire classes (such as
`Sanitizer`) are detected by class name and run without requiring a module install.

### File structure

New tests should extend the `WireTest` base class. The test class name must be
`WireTest_` followed by the test file basename.

```php
<?php namespace ProcessWire;

class WireTest_MyClass extends WireTest {

    public function init() {
        // Optional setup before execute()
    }

    public function execute() {
        $a = 1;
        $b = 2;

        $this->check("1 is less than 2", true, $a < $b);

        if($a > $b) {
            $this->fail("Unexpected comparison result");
        }

        $this->ok("Custom status line");
    }

    public function finish() {
        // Optional cleanup; runs even when execute() fails
    }
}
```

The example below demonstrates the file structure for a Fieldtype test. 
For more and better examples, see the files in the `tests/` directory.

```php
<?php namespace ProcessWire;

class WireTest_FieldtypeMyModule extends WireTest {

    protected $name = 'my_field_name';

    public function init() {
        $page = $this->getTestPage();
        $fields = $this->wire()->fields;
        $field = $fields->get($this->name);

        // Create the field if it does not already exist
        if(!$field) {
            $field = $fields->new('FieldtypeMyModule', $this->name, 'My Field');
            $this->ok("Created field: $this->name");
        }

        // Add field to the test template if not already there
        $fieldgroup = $page->template->fieldgroup;
        if(!$fieldgroup->hasField($field)) {
            $fieldgroup->add($field);
            $fieldgroup->save();
            $this->ok("Added field to fieldgroup: $fieldgroup->name");
        }
    }

    public function execute() {
        $page = $this->getTestPage();
        $pages = $this->wire()->pages;
        $name = $this->name;

        // Write a value
        $page->of(false);
        $page->set($name, 'some value');
        $page->save($name);

        // Read it back from a fresh page load
        $fresh = $pages->getFresh($page->id);
        $this->check("Value round-trip", 'some value', $fresh->get($name));

        // Test a selector
        $match = $pages->findOne("template=test, $name='some value'");
        $this->check("Selector finds test page", $page->id, $match->id);
    }
}
```

Legacy flat-file tests are still supported. In flat-file tests, ProcessWire API variables
are extracted into scope and the file passes if it reaches the end without throwing:

```php
<?php namespace ProcessWire;
/** @var Page $page */

check("1 is less than 2", true, 1 < 2);
```

### Key conventions

| Thing                     | Convention                                                                                               |
|---------------------------|----------------------------------------------------------------------------------------------------------|
| **Test class**            | `class WireTest_TestName extends WireTest`                                                               |
| **Run test logic**        | Implement `execute()`                                                                                    |
| **Setup**                 | Implement `init()` when setup is needed                                                                  |
| **Cleanup**               | Implement `finish()`; it runs even when `execute()` fails                                                |
| **Fail**                  | `$this->fail('reason')` or throw `WireTestException('reason')`                                           |
| **Check**                 | `$this->check('description', $expected, $actual)`                                                        |
| **Pass**                  | `execute()` and `finish()` complete without throwing                                                     |
| **Status output**         | `$this->ok('message')`, `$this->li('message')`, or `wireTests()->li('message')`                          |
| **Fresh page load**       | `$this->wire()->pages->getFresh($page->id)`                                                              |
| **Output formatting off** | `$page->of(false)` before setting/saving values                                                          |
| **Field already exists**  | Check with `fields()->get($name)` and skip creation                                                      |
| **Idempotent setup**      | Guard any one-time setup (adding options, creating child pages, etc.) so it's safe to run more than once |

### Available helpers

```php
$this->check('description', $expected, $actual); // assert strict equality by default
$this->check('description', $expected, $actual, '>='); // supported operators: ===, !==, ==, !=, <, <=, >, >=, *=, ^=, $=
$this->fail('reason');   // fail this test
$this->ok('message');    // output an OK status line
$this->li('message');    // output a status line

wireTests()->li('message');   // legacy/global helper
wireTests()->note('message'); // output a plain note
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

1. `runTests()` iterates every `.php` file in `tests/`.
2. Tests for optional modules are skipped when the module is not installed. Core class tests
   run when the core class exists.
3. The runner includes the test file inside a `try/catch` block.
4. If the file defines `WireTest_TestName`, the runner creates it, calls `allow()`,
   `init()`, `execute()`, and then `finish()`.
5. `finish()` is called even when `execute()` fails, so it is the right place for cleanup.
6. Legacy flat-file tests pass when the file reaches the end without throwing.
7. `WireTestException` → test fails (message shown). Any other `Throwable` → test fails.
8. A summary line is printed at the end showing passed/failed counts.


## Contributing

Pull requests with new or improved tests are welcome. Please follow the conventions
above and make sure your test is idempotent (safe to run multiple times). If the test
covers a module that is not part of the ProcessWire core, include a note in the test
file about where the module can be obtained.
