<?php namespace ProcessWire;
/** @var Page $page */

$s = $sanitizer;

// ===== TEXT SANITIZERS =====

$r = $s->text("Hello <b>World</b>\nNewline");
if($r !== 'Hello World Newline') throw new WireTestException("text() basic: '$r'");
wireTests()->li("text() strips tags and replaces newlines with space");

$r = $s->text(str_repeat('x', 300));
if(strlen($r) !== 255) throw new WireTestException("text() default maxLength=255 not enforced: " . strlen($r));
wireTests()->li("text() default maxLength=255 enforced");

$r = $s->text("Hello World", ['maxLength' => 5]);
if($r !== 'Hello') throw new WireTestException("text() custom maxLength: '$r'");
wireTests()->li("text() custom maxLength option");

$r = $s->textarea("Line 1\nLine 2<b>bold</b>");
if(strpos($r, "\n") === false) throw new WireTestException("textarea() stripped newlines: '$r'");
if(strpos($r, '<b>') !== false) throw new WireTestException("textarea() kept tags: '$r'");
wireTests()->li("textarea() preserves newlines and strips tags");

$r = $s->line(str_repeat('x', 500));
if(strlen($r) !== 500) throw new WireTestException("line() applied unexpected max: " . strlen($r));
wireTests()->li("line() has no built-in max length");

$r = $s->line(str_repeat('x', 500), 100);
if(strlen($r) !== 100) throw new WireTestException("line() explicit maxLength: " . strlen($r));
wireTests()->li("line() respects explicit maxLength argument");

$r = $s->lines("Line 1\nLine 2", 0);
if(strpos($r, "\n") === false) throw new WireTestException("lines() stripped newlines: '$r'");
wireTests()->li("lines() preserves newlines");

// ===== NAMES AND IDENTIFIERS =====

$r = $s->name("Foo+Bar Baz-123");
if($r !== 'Foo_Bar_Baz-123') throw new WireTestException("name() basic: '$r'");
wireTests()->li("name() replaces invalid chars with underscore, keeps hyphen");

$r = $s->fieldName("Hello World");
if($r !== 'Hello_World') throw new WireTestException("fieldName() space: '$r'");
wireTests()->li("fieldName() replaces spaces with underscore");

$r = $s->fieldName("hello-world");
if($r !== 'hello_world') throw new WireTestException("fieldName() hyphen: '$r'");
wireTests()->li("fieldName() replaces hyphens with underscore (no hyphens allowed)");

$r = $s->pageName("Hello World!", true);
if($r !== 'hello-world') throw new WireTestException("pageName() beautified: '$r'");
wireTests()->li("pageName() beautified: lowercase, spaces to hyphens, strips punctuation");

$r = $s->pageName("HELLO");
if($r !== 'hello') throw new WireTestException("pageName() no beautify still lowercases: '$r'");
wireTests()->li("pageName() always lowercases");

$r = $s->fieldSubfield('a.b.c');
if($r !== 'a.b') throw new WireTestException("fieldSubfield() default limit=1: '$r'");
wireTests()->li("fieldSubfield() default limit=1 returns 'a.b'");

$r = $s->fieldSubfield('a.b.c', 2);
if($r !== 'a.b.c') throw new WireTestException("fieldSubfield() limit=2: '$r'");
wireTests()->li("fieldSubfield() limit=2 returns 'a.b.c'");

$r = $s->fieldSubfield('a.b.c', 0);
if($r !== 'a') throw new WireTestException("fieldSubfield() limit=0: '$r'");
wireTests()->li("fieldSubfield() limit=0 returns field only 'a'");

$r = $s->filename("©My File.jpg");
if(strpos($r, '©') !== false) throw new WireTestException("filename() kept non-ASCII: '$r'");
if(strpos($r, '.jpg') === false) throw new WireTestException("filename() lost extension: '$r'");
wireTests()->li("filename() strips non-ASCII, keeps extension");

// ===== CHARACTER FILTERING =====

$r = $s->alpha("Hello 123 World!");
if($r !== 'HelloWorld') throw new WireTestException("alpha(): '$r'");
wireTests()->li("alpha() keeps only a-zA-Z");

$r = $s->alphanumeric("Hello 123 World!");
if($r !== 'Hello123World') throw new WireTestException("alphanumeric(): '$r'");
wireTests()->li("alphanumeric() keeps a-zA-Z and 0-9");

$r = $s->digits("(800) 555-1234");
if($r !== '8005551234') throw new WireTestException("digits(): '$r'");
wireTests()->li("digits() keeps 0-9 only");

$r = $s->chars('foo123barBaz456', 'barz1');
if($r !== '1baraz') throw new WireTestException("chars() basic: '$r'");
wireTests()->li("chars() keeps only characters in the allow list");

$r = $s->chars('(800) 555-1234', '[digit]', '.');
if($r !== '800.555.1234') throw new WireTestException("chars() with [digit]: '$r'");
wireTests()->li("chars() [digit] alias with collapse and trim");

$r = $s->chars('Decatur, GA 30030', '[alpha]', '-');
if($r !== 'Decatur-GA') throw new WireTestException("chars() with [alpha]: '$r'");
wireTests()->li("chars() [alpha] alias with collapse and trim");

$r = $s->word("hello world");
if($r !== 'hello') throw new WireTestException("word() basic: '$r'");
wireTests()->li("word() returns first word only");

$r = $s->word("hello world", ['separator' => '-']);
if($r !== 'hello-world') throw new WireTestException("word() separator: '$r'");
wireTests()->li("word() separator option joins all words");

$r = $s->words('<p>Hello <em>World</em>!</p>');
if($r !== 'Hello World') throw new WireTestException("words() from markup: '$r'");
wireTests()->li("words() strips markup and returns space-separated words");

// ===== CASE CONVERSION =====

$r = $s->hyphenCase('Hello World');
if($r !== 'hello-world') throw new WireTestException("hyphenCase(): '$r'");
wireTests()->li("hyphenCase() = 'hello-world'");

$r = $s->kebabCase('Hello World');
if($r !== 'hello-world') throw new WireTestException("kebabCase(): '$r'");
wireTests()->li("kebabCase() = 'hello-world' (alias of hyphenCase)");

$r = $s->snakeCase('Hello World');
if($r !== 'hello_world') throw new WireTestException("snakeCase(): '$r'");
wireTests()->li("snakeCase() = 'hello_world'");

$r = $s->camelCase('Hello World');
if($r !== 'helloWorld') throw new WireTestException("camelCase(): '$r'");
wireTests()->li("camelCase() = 'helloWorld'");

$r = $s->pascalCase('Hello World');
if($r !== 'HelloWorld') throw new WireTestException("pascalCase(): '$r'");
wireTests()->li("pascalCase() = 'HelloWorld'");

// ===== HTML AND ENTITIES =====

$r = $s->entities('<b>Hello</b> "World" & more');
$expected = '&lt;b&gt;Hello&lt;/b&gt; &quot;World&quot; &amp; more';
if($r !== $expected) throw new WireTestException("entities(): '$r'");
wireTests()->li("entities() encodes tags, quotes, and ampersands");

// entities1() should not double-encode existing entities
$r = $s->entities1('&lt;b&gt; &amp; test');
if($r !== '&lt;b&gt; &amp; test') throw new WireTestException("entities1() double-encoded: '$r'");
wireTests()->li("entities1() does not double-encode existing entities");

// entities() would double-encode; confirm the difference
$r2 = $s->entities('&lt;b&gt; &amp; test');
if(strpos($r2, '&amp;lt;') === false) throw new WireTestException("entities() should double-encode but got: '$r2'");
wireTests()->li("entities() does double-encode (contrast with entities1)");

$r = $s->unentities('&lt;b&gt;Hello&lt;/b&gt;');
if($r !== '<b>Hello</b>') throw new WireTestException("unentities(): '$r'");
wireTests()->li("unentities() decodes HTML entities");

$r = $s->entitiesMarkdown('**bold** and *em* and `code`');
if(strpos($r, '<strong>bold</strong>') === false) throw new WireTestException("entitiesMarkdown() strong: '$r'");
if(strpos($r, '<em>em</em>') === false) throw new WireTestException("entitiesMarkdown() em: '$r'");
if(strpos($r, '<code>code</code>') === false) throw new WireTestException("entitiesMarkdown() code: '$r'");
wireTests()->li("entitiesMarkdown() converts **bold**, *em*, backtick code");

$r = $s->markupToText('<p>Hello <strong>World</strong></p>');
if(strpos($r, '<') !== false) throw new WireTestException("markupToText() left tags: '$r'");
if(strpos($r, 'Hello') === false || strpos($r, 'World') === false) throw new WireTestException("markupToText() lost text: '$r'");
wireTests()->li("markupToText() strips tags, keeps text content");

// ===== NUMBERS =====

$r = $s->int('42');
if($r !== 42 || !is_int($r)) throw new WireTestException("int() basic: " . var_export($r, true));
wireTests()->li("int() returns integer 42");

$r = $s->int(-5);
if($r !== 0) throw new WireTestException("int() negative clamped to default min=0: " . var_export($r, true));
wireTests()->li("int() default min=0 clamps negative to 0");

$r = $s->int(200, ['max' => 100]);
if($r !== 100) throw new WireTestException("int() over max clamped: " . var_export($r, true));
wireTests()->li("int() clamps value to max");

$r = $s->int(5, ['min' => 10]);
if($r !== 10) throw new WireTestException("int() under min clamped: " . var_export($r, true));
wireTests()->li("int() clamps value to min");

$r = $s->intSigned(-42);
if($r !== -42) throw new WireTestException("intSigned() negative: " . var_export($r, true));
wireTests()->li("intSigned() allows negative values");

$r = $s->float('1,234.56');
if($r !== 1234.56) throw new WireTestException("float() comma thousands: " . var_export($r, true));
wireTests()->li("float() parses comma-formatted numbers");

$r = $s->float('3.14159', ['precision' => 2]);
if($r !== 3.14) throw new WireTestException("float() precision: " . var_export($r, true));
wireTests()->li("float() precision option rounds to 2 decimal places");

$r = $s->range(150, 0, 100);
if($r !== 100) throw new WireTestException("range() over max: " . var_export($r, true));
wireTests()->li("range() clamps over-max value to max");

$r = $s->range(-10, 0, 100);
if($r !== 0) throw new WireTestException("range() under min: " . var_export($r, true));
wireTests()->li("range() clamps under-min value to min");

$r = $s->range(0.5, 0.0, 1.0);
if(!is_float($r)) throw new WireTestException("range() should return float when bounds are float: " . var_export($r, true));
wireTests()->li("range() returns float when bounds are float");

$r = $s->min(5, 10);
if($r !== 10) throw new WireTestException("min() below minimum: " . var_export($r, true));
wireTests()->li("min() returns minimum when value is below it");

$r = $s->min(15, 10);
if($r !== 15) throw new WireTestException("min() above minimum: " . var_export($r, true));
wireTests()->li("min() returns value when value is above minimum");

$r = $s->max(150, 100);
if($r !== 100) throw new WireTestException("max() above maximum: " . var_export($r, true));
wireTests()->li("max() returns maximum when value exceeds it");

$r = $s->max(50, 100);
if($r !== 50) throw new WireTestException("max() below maximum: " . var_export($r, true));
wireTests()->li("max() returns value when value is below maximum");

// ===== BOOLEANS =====

if($s->bool('false') !== false) throw new WireTestException("bool('false') should be false");
if($s->bool('0') !== false) throw new WireTestException("bool('0') should be false");
if($s->bool('') !== false) throw new WireTestException("bool('') should be false");
if($s->bool('1') !== true) throw new WireTestException("bool('1') should be true");
if($s->bool('true') !== true) throw new WireTestException("bool('true') should be true");
if($s->bool('yes') !== true) throw new WireTestException("bool('yes') should be true");
wireTests()->li("bool() recognizes 'false'/'0'/'' as false; '1'/'true'/'yes' as true");

if($s->bool([]) !== false) throw new WireTestException("bool([]) should be false");
if($s->bool(['a']) !== true) throw new WireTestException("bool(['a']) should be true");
wireTests()->li("bool() empty array=false, non-empty array=true");

$r = $s->bit('1');
if($r !== 1 || !is_int($r)) throw new WireTestException("bit('1') should be int 1: " . var_export($r, true));
$r = $s->bit('0');
if($r !== 0 || !is_int($r)) throw new WireTestException("bit('0') should be int 0: " . var_export($r, true));
wireTests()->li("bit() returns integer 0 or 1");

$r = $s->checkbox(1);
if($r !== true) throw new WireTestException("checkbox(1) should be true");
$r = $s->checkbox(0);
if($r !== false) throw new WireTestException("checkbox(0) should be false");
$r = $s->checkbox('', false, true); // unchecked
if($r !== true) throw new WireTestException("checkbox('') unchecked should return \$no: " . var_export($r, true));
$r = $s->checkbox(1, 'yes', 'no');
if($r !== 'yes') throw new WireTestException("checkbox(1, 'yes', 'no') should be 'yes'");
wireTests()->li("checkbox() returns \$yes/\$no based on truthiness of value");

// ===== URL AND EMAIL =====

$r = $s->url('https://processwire.com/');
if($r !== 'https://processwire.com/') throw new WireTestException("url() valid https: '$r'");
wireTests()->li("url() accepts valid https URL");

$r = $s->url('/path/to/page');
if($r !== '/path/to/page') throw new WireTestException("url() relative allowed by default: '$r'");
wireTests()->li("url() allows relative paths by default");

$r = $s->url('/path/to/page', ['allowRelative' => false]);
if($r !== '') throw new WireTestException("url() relative disallowed should return '': '$r'");
wireTests()->li("url() rejects relative when allowRelative=false");

// javascript: scheme is disallowed by default (generates a notice, but returns '' or stripped value)
$r = $s->url('javascript:alert(1)');
if(stripos($r, 'javascript') !== false) throw new WireTestException("url() should strip javascript: scheme: '$r'");
wireTests()->li("url() strips disallowed javascript: scheme");

$r = $s->httpUrl('https://processwire.com/');
if($r !== 'https://processwire.com/') throw new WireTestException("httpUrl() valid: '$r'");
wireTests()->li("httpUrl() accepts https URL");

$r = $s->httpUrl('/relative/path');
if($r !== '') throw new WireTestException("httpUrl() should reject relative path: '$r'");
wireTests()->li("httpUrl() rejects relative paths");

$r = $s->email('user@example.com');
if($r !== 'user@example.com') throw new WireTestException("email() valid: '$r'");
wireTests()->li("email() accepts valid address");

$r = $s->email('not-an-email');
if($r !== '') throw new WireTestException("email() invalid should return '': '$r'");
wireTests()->li("email() returns blank for invalid address");

$r = $s->email('user+tag@sub.example.com');
if($r !== 'user+tag@sub.example.com') throw new WireTestException("email() plus+tag subdomain: '$r'");
wireTests()->li("email() accepts plus-tag and subdomain addresses");

// ===== ARRAYS =====

$r = $s->array('foo,bar,baz');
if($r !== ['foo', 'bar', 'baz']) throw new WireTestException("array() CSV comma: " . implode(',', $r));
wireTests()->li("array() splits comma-delimited string");

$r = $s->array('foo|bar|baz');
if($r !== ['foo', 'bar', 'baz']) throw new WireTestException("array() CSV pipe: " . implode(',', $r));
wireTests()->li("array() splits pipe-delimited string");

$r = $s->array('1,2,3', 'int');
if($r !== [1, 2, 3]) throw new WireTestException("array() int sanitized: " . implode(',', $r));
wireTests()->li("array() sanitizes each item when sanitizer name given");

$r = $s->array('foo,bar,baz', 'pageName');
if($r !== ['foo', 'bar', 'baz']) throw new WireTestException("array() pageName sanitized: " . implode(',', $r));
wireTests()->li("array() with pageName sanitizer");

$r = $s->intArray('1,2,3,foo');
if($r !== [1, 2, 3, 0]) throw new WireTestException("intArray() non-ints become 0: " . implode(',', $r));
wireTests()->li("intArray() converts CSV, non-integers become 0");

$data = ['a' => 'foo', 'b' => '', 'c' => 0, 'd' => 'bar'];
$r = $s->minArray($data);
if(isset($r['b'])) throw new WireTestException("minArray() kept empty string at 'b'");
if(isset($r['c'])) throw new WireTestException("minArray() kept 0 at 'c'");
if(!isset($r['a']) || !isset($r['d'])) throw new WireTestException("minArray() removed non-empty values");
wireTests()->li("minArray() removes empty strings and 0 by default");

$data = ['a' => 'foo', 'b' => '', 'c' => 0];
$r = $s->minArray($data, 0);
if(isset($r['b'])) throw new WireTestException("minArray(0) kept empty string at 'b'");
if(!isset($r['c']) || $r['c'] !== 0) throw new WireTestException("minArray(0) removed integer 0 at 'c': " . print_r($r, true));
wireTests()->li("minArray(data, 0) keeps integer 0, removes empty strings");

$data = ['a' => 'foo', 'b' => '', 'c' => 0, 'd' => 'bar'];
$r = $s->minArray($data, ['b', 'c']);
if(!isset($r['b'])) throw new WireTestException("minArray(['b','c']) removed 'b' (should keep even if empty)");
if(!isset($r['c'])) throw new WireTestException("minArray(['b','c']) removed 'c' (should keep even if empty)");
if(isset($r['e']) ?? false) {} // no-op, just checking structure
wireTests()->li("minArray(data, [keys]) keeps listed keys even if empty");

$r = $s->option('red', ['red', 'green', 'blue']);
if($r !== 'red') throw new WireTestException("option() allowed value: " . var_export($r, true));
wireTests()->li("option() returns value when it's in the whitelist");

$r = $s->option('purple', ['red', 'green', 'blue']);
if($r !== null) throw new WireTestException("option() disallowed value should be null: " . var_export($r, true));
wireTests()->li("option() returns null when value is not in whitelist");

$r = $s->options(['red', 'purple', 'blue'], ['red', 'green', 'blue']);
$r = array_values($r); // reset keys
if($r !== ['red', 'blue']) throw new WireTestException("options() filter: " . implode(',', $r));
wireTests()->li("options() filters array to only allowed values");

// ===== SELECTOR VALUE =====

// selectorValue() wraps values in double-quotes when they contain single quotes
$r = $s->selectorValue("O'Brien");
if(substr($r, 0, 1) !== '"' || substr($r, -1) !== '"') {
	throw new WireTestException("selectorValue() with single quote should wrap in double-quotes: '$r'");
}
wireTests()->li("selectorValue() wraps in double-quotes when value contains single quote");

// plain value with no special chars should pass through cleanly
$r = $s->selectorValue("hello");
if($r !== 'hello') throw new WireTestException("selectorValue() plain value: '$r'");
wireTests()->li("selectorValue() plain value passes through");

// array becomes pipe-delimited OR value
$r = $s->selectorValue(['foo', 'bar']);
if(strpos($r, 'foo') === false || strpos($r, 'bar') === false || strpos($r, '|') === false) {
	throw new WireTestException("selectorValue() array should become OR value with |: '$r'");
}
wireTests()->li("selectorValue() array becomes pipe-delimited OR value");

// ===== VALIDATION =====

$r = $s->validate('user@example.com', 'email');
if($r !== 'user@example.com') throw new WireTestException("validate() valid email: " . var_export($r, true));
wireTests()->li("validate() returns value unchanged by sanitizer");

$r = $s->validate('not-an-email', 'email');
if($r !== null) throw new WireTestException("validate() invalid email should be null: " . var_export($r, true));
wireTests()->li("validate() returns null when sanitizer changes the value");

$r = $s->validate('hello', 'alpha');
if($r !== 'hello') throw new WireTestException("validate() valid alpha: " . var_export($r, true));
wireTests()->li("validate() passes clean alpha string");

if(!$s->valid('hello', 'alpha')) throw new WireTestException("valid() alpha should be true");
if($s->valid('hello 123', 'alpha')) throw new WireTestException("valid() alpha+digits should be false");
wireTests()->li("valid() returns true/false based on whether sanitizer changes the value");

// ===== WHITESPACE =====

$r = $s->trim("  hello  ");
if($r !== 'hello') throw new WireTestException("trim() basic: '$r'");
wireTests()->li("trim() removes leading/trailing whitespace");

$r = $s->trim("--hello--", '-');
if($r !== 'hello') throw new WireTestException("trim() custom chars: '$r'");
wireTests()->li("trim() trims custom characters");

$r = $s->removeNewlines("Line1\nLine2\r\nLine3");
if(strpos($r, "\n") !== false || strpos($r, "\r") !== false) throw new WireTestException("removeNewlines() left newlines: '$r'");
wireTests()->li("removeNewlines() removes \\n and \\r\\n");

$r = $s->removeNewlines("Line1\nLine2", '');
if($r !== 'Line1Line2') throw new WireTestException("removeNewlines() empty replacement: '$r'");
wireTests()->li("removeNewlines() with '' removes newlines entirely");

$r = $s->removeWhitespace("foo bar\tbaz");
if($r !== 'foobarbaz') throw new WireTestException("removeWhitespace(): '$r'");
wireTests()->li("removeWhitespace() removes spaces and tabs");

// ===== TRUNCATION AND LENGTH =====

$str = "The quick brown fox jumps over the lazy dog. It was a beautiful day.";
$r = $s->truncate($str, 30);
if(strlen($r) > 30) throw new WireTestException("truncate() exceeded maxLength: " . strlen($r));
wireTests()->li("truncate() respects maxLength");

// trunc() does not append ellipsis
$r = $s->trunc($str, 30);
if(strlen($r) > 30) throw new WireTestException("trunc() exceeded maxLength: " . strlen($r));
if(strpos($r, '…') !== false) throw new WireTestException("trunc() appended ellipsis: '$r'");
wireTests()->li("trunc() truncates without appending ellipsis");

$r = $s->maxLength("Hello World", 5);
if($r !== 'Hello') throw new WireTestException("maxLength() string: '$r'");
wireTests()->li("maxLength() truncates string to N chars");

$r = $s->maxLength([1, 2, 3, 4, 5], 3);
if(count($r) !== 3) throw new WireTestException("maxLength() array: count=" . count($r));
wireTests()->li("maxLength() limits array to N items");

$r = $s->minLength("Hi", 5);
if($r !== '') throw new WireTestException("minLength() too short should return '': '$r'");
wireTests()->li("minLength() returns blank string when value is shorter than minimum");

$r = $s->minLength("Hi", 5, '0');
if($r !== 'Hi000') throw new WireTestException("minLength() pad right: '$r'");
wireTests()->li("minLength() pads right with pad character");

$r = $s->minLength("Hi", 5, '0', true);
if($r !== '000Hi') throw new WireTestException("minLength() pad left: '$r'");
wireTests()->li("minLength() pads left with pad character");

// ===== CHAINING AND SHORTHAND =====

$r = $s->text20("This string is longer than twenty characters long");
if(strlen($r) > 20) throw new WireTestException("text20() maxLength not enforced: " . strlen($r));
wireTests()->li("text20() shorthand limits to 20 chars");

$r = $s->text_entities('<b>Tom & Jerry</b>');
if($r !== 'Tom &amp; Jerry') throw new WireTestException("text_entities(): '$r'");
wireTests()->li("text_entities() chains text() then entities()");

$r = $s->sanitize("Hello <b>World</b>", 'text');
if($r !== 'Hello World') throw new WireTestException("sanitize() by name: '$r'");
wireTests()->li("sanitize() calls sanitizer by name");

$r = $s->sanitize("Hello <b>World</b>", 'text,entities');
if(strpos($r, '&lt;') !== false) throw new WireTestException("sanitize() chained: '$r' (should have stripped tags before encoding)");
if($r !== 'Hello World') throw new WireTestException("sanitize() text,entities: '$r'");
wireTests()->li("sanitize() with chained 'text,entities' works correctly");

// ===== STRING UTILITY =====

$r = $s->string(42);
if($r !== '42') throw new WireTestException("string() int: " . var_export($r, true));
wireTests()->li("string() converts int to string");

$r = $s->string(true);
if($r !== '1') throw new WireTestException("string() true: " . var_export($r, true));
wireTests()->li("string() converts bool true to '1'");

$r = $s->string(false);
if($r !== '') throw new WireTestException("string() false: " . var_export($r, true));
wireTests()->li("string() converts bool false to ''");

$r = $s->string(null);
if($r !== '') throw new WireTestException("string() null: " . var_export($r, true));
wireTests()->li("string() converts null to ''");
