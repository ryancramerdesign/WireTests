<?php namespace ProcessWire;
/** @var Page $page */

$s = $sanitizer;

// ===== TEXT SANITIZERS =====

check("text() strips tags and replaces newlines with space", 'Hello World Newline', $s->text("Hello <b>World</b>\nNewline"));
check("text() default maxLength=255 enforced", 255, strlen($s->text(str_repeat('x', 300))));
check("text() custom maxLength option", 'Hello', $s->text("Hello World", ['maxLength' => 5]));

$r = $s->textarea("Line 1\nLine 2<b>bold</b>");
check("textarea() preserves newlines", true, strpos($r, "\n") !== false);
check("textarea() strips tags", false, strpos($r, '<b>') !== false);

check("line() has no built-in max length", 500, strlen($s->line(str_repeat('x', 500))));
check("line() respects explicit maxLength argument", 100, strlen($s->line(str_repeat('x', 500), 100)));
check("lines() preserves newlines", true, strpos($s->lines("Line 1\nLine 2", 0), "\n") !== false);

// ===== NAMES AND IDENTIFIERS =====

check("name() replaces invalid chars with underscore, keeps hyphen", 'Foo_Bar_Baz-123', $s->name("Foo+Bar Baz-123"));
check("fieldName() replaces spaces with underscore", 'Hello_World', $s->fieldName("Hello World"));
check("fieldName() replaces hyphens with underscore (no hyphens allowed)", 'hello_world', $s->fieldName("hello-world"));
check("pageName() beautified: lowercase, spaces to hyphens, strips punctuation", 'hello-world', $s->pageName("Hello World!", true));
check("pageName() always lowercases", 'hello', $s->pageName("HELLO"));
check("fieldSubfield() default limit=1 returns 'a.b'", 'a.b', $s->fieldSubfield('a.b.c'));
check("fieldSubfield() limit=2 returns 'a.b.c'", 'a.b.c', $s->fieldSubfield('a.b.c', 2));
check("fieldSubfield() limit=0 returns field only 'a'", 'a', $s->fieldSubfield('a.b.c', 0));

$r = $s->filename("©My File.jpg");
check("filename() strips non-ASCII", false, strpos($r, '©') !== false);
check("filename() keeps extension", true, str_ends_with($r, '.jpg'));

// ===== CHARACTER FILTERING =====

check("alpha() keeps only a-zA-Z", 'HelloWorld', $s->alpha("Hello 123 World!"));
check("alphanumeric() keeps a-zA-Z and 0-9", 'Hello123World', $s->alphanumeric("Hello 123 World!"));
check("digits() keeps 0-9 only", '8005551234', $s->digits("(800) 555-1234"));
check("chars() keeps only characters in the allow list", '1baraz', $s->chars('foo123barBaz456', 'barz1'));
check("chars() [digit] alias with collapse and trim", '800.555.1234', $s->chars('(800) 555-1234', '[digit]', '.'));
check("chars() [alpha] alias with collapse and trim", 'Decatur-GA', $s->chars('Decatur, GA 30030', '[alpha]', '-'));
check("word() returns first word only", 'hello', $s->word("hello world"));
check("word() separator option joins all words", 'hello-world', $s->word("hello world", ['separator' => '-']));
check("words() strips markup and returns space-separated words", 'Hello World', $s->words('<p>Hello <em>World</em>!</p>'));

// ===== CASE CONVERSION =====

check("hyphenCase() = 'hello-world'", 'hello-world', $s->hyphenCase('Hello World'));
check("kebabCase() = 'hello-world' (alias of hyphenCase)", 'hello-world', $s->kebabCase('Hello World'));
check("snakeCase() = 'hello_world'", 'hello_world', $s->snakeCase('Hello World'));
check("camelCase() = 'helloWorld'", 'helloWorld', $s->camelCase('Hello World'));
check("pascalCase() = 'HelloWorld'", 'HelloWorld', $s->pascalCase('Hello World'));

// ===== HTML AND ENTITIES =====

check("entities() encodes tags, quotes, and ampersands",
	'&lt;b&gt;Hello&lt;/b&gt; &quot;World&quot; &amp; more',
	$s->entities('<b>Hello</b> "World" & more'));

check("entities1() does not double-encode existing entities", '&lt;b&gt; &amp; test', $s->entities1('&lt;b&gt; &amp; test'));
check("entities() does double-encode (contrast with entities1)", true, strpos($s->entities('&lt;b&gt;'), '&amp;lt;') !== false);
check("unentities() decodes HTML entities", '<b>Hello</b>', $s->unentities('&lt;b&gt;Hello&lt;/b&gt;'));

$r = $s->entitiesMarkdown('**bold** and *em* and `code`');
check("entitiesMarkdown() converts **bold**", '<strong>bold</strong>', $r, '*=');
check("entitiesMarkdown() converts *em*", '<em>em</em>', $r, '*=');
check("entitiesMarkdown() converts backtick code", '<code>code</code>', $r, '*=');

$r = $s->markupToText('<p>Hello <strong>World</strong></p>');
check("markupToText() strips tags", false, strpos($r, '<') !== false);
check("markupToText() keeps text content", 'Hello', $r, '*=');

// ===== NUMBERS =====

check("int() returns integer", 42, $s->int('42'));
check("int() default min=0 clamps negative to 0", 0, $s->int(-5));
check("int() clamps value to max", 100, $s->int(200, ['max' => 100]));
check("int() clamps value to min", 10, $s->int(5, ['min' => 10]));
check("intSigned() allows negative values", -42, $s->intSigned(-42));
check("float() parses comma-formatted numbers", 1234.56, $s->float('1,234.56'));
check("float() precision option rounds to 2 decimal places", 3.14, $s->float('3.14159', ['precision' => 2]));
check("range() clamps over-max value to max", 100, $s->range(150, 0, 100));
check("range() clamps under-min value to min", 0, $s->range(-10, 0, 100));
check("range() returns float when bounds are float", true, is_float($s->range(0.5, 0.0, 1.0)));
check("min() returns minimum when value is below it", 10, $s->min(5, 10));
check("min() returns value when value is above minimum", 15, $s->min(15, 10));
check("max() returns maximum when value exceeds it", 100, $s->max(150, 100));
check("max() returns value when value is below maximum", 50, $s->max(50, 100));

// ===== BOOLEANS =====

check("bool('false') === false", false, $s->bool('false'));
check("bool('0') === false", false, $s->bool('0'));
check("bool('') === false", false, $s->bool(''));
check("bool('1') === true", true, $s->bool('1'));
check("bool('true') === true", true, $s->bool('true'));
check("bool('yes') === true (any non-empty non-false string)", true, $s->bool('yes'));
check("bool([]) === false (empty array)", false, $s->bool([]));
check("bool(['a']) === true (non-empty array)", true, $s->bool(['a']));
check("bit('1') returns int 1", 1, $s->bit('1'));
check("bit('0') returns int 0", 0, $s->bit('0'));
check("checkbox(1) returns true", true, $s->checkbox(1));
check("checkbox(0) returns false", false, $s->checkbox(0));
check("checkbox('', false, true) returns \$no value", true, $s->checkbox('', false, true));
check("checkbox(1, 'yes', 'no') returns 'yes'", 'yes', $s->checkbox(1, 'yes', 'no'));

// ===== URL AND EMAIL =====

check("url() accepts valid https URL", 'https://processwire.com/', $s->url('https://processwire.com/'));
check("url() allows relative paths by default", '/path/to/page', $s->url('/path/to/page'));
check("url() rejects relative when allowRelative=false", '', $s->url('/path/to/page', ['allowRelative' => false]));
check("url() strips disallowed javascript: scheme", false, stripos($s->url('javascript:alert(1)'), 'javascript') !== false);
check("httpUrl() accepts https URL", 'https://processwire.com/', $s->httpUrl('https://processwire.com/'));
check("httpUrl() rejects relative paths", '', $s->httpUrl('/relative/path'));
check("email() accepts valid address", 'user@example.com', $s->email('user@example.com'));
check("email() returns blank for invalid address", '', $s->email('not-an-email'));
check("email() accepts plus-tag and subdomain addresses", 'user+tag@sub.example.com', $s->email('user+tag@sub.example.com'));

// ===== ARRAYS =====

check("array() splits comma-delimited string", ['foo', 'bar', 'baz'], $s->array('foo,bar,baz'));
check("array() splits pipe-delimited string", ['foo', 'bar', 'baz'], $s->array('foo|bar|baz'));
check("array() sanitizes items with 'int'", [1, 2, 3], $s->array('1,2,3', 'int'));
check("array() with pageName sanitizer", ['foo', 'bar', 'baz'], $s->array('foo,bar,baz', 'pageName'));
check("intArray() converts CSV, non-integers become 0", [1, 2, 3, 0], $s->intArray('1,2,3,foo'));

$data = ['a' => 'foo', 'b' => '', 'c' => 0, 'd' => 'bar'];
$r = $s->minArray($data);
check("minArray() removes empty string", false, isset($r['b']));
check("minArray() removes 0", false, isset($r['c']));
check("minArray() keeps non-empty values", true, isset($r['a']) && isset($r['d']));

$data = ['a' => 'foo', 'b' => '', 'c' => 0];
$r = $s->minArray($data, 0);
check("minArray(data, 0) removes empty string", false, isset($r['b']));
check("minArray(data, 0) keeps integer 0", true, isset($r['c']) && $r['c'] === 0);

$data = ['a' => 'foo', 'b' => '', 'c' => 0];
$r = $s->minArray($data, ['b', 'c']);
check("minArray(data, [keys]) keeps listed empty keys", true, isset($r['b']) && isset($r['c']));

check("option() returns value when in whitelist", 'red', $s->option('red', ['red', 'green', 'blue']));
check("option() returns null when not in whitelist", null, $s->option('purple', ['red', 'green', 'blue']));
check("options() filters array to only allowed values", ['red', 'blue'], array_values($s->options(['red', 'purple', 'blue'], ['red', 'green', 'blue'])));

// ===== SELECTOR VALUE =====

$r = $s->selectorValue("O'Brien");
check("selectorValue() wraps in double-quotes when value contains single quote", '"', $r, '^=');
check("selectorValue() plain value passes through", 'hello', $s->selectorValue('hello'));
$r = $s->selectorValue(['foo', 'bar']);
check("selectorValue() array becomes OR value containing 'foo'", 'foo', $r, '*=');
check("selectorValue() array becomes OR value containing '|'", '|', $r, '*=');

// ===== VALIDATION =====

check("validate() returns value unchanged by sanitizer", 'user@example.com', $s->validate('user@example.com', 'email'));
check("validate() returns null when sanitizer changes value", null, $s->validate('not-an-email', 'email'));
check("validate() passes clean alpha string", 'hello', $s->validate('hello', 'alpha'));
check("valid() returns true for valid value", true, $s->valid('hello', 'alpha'));
check("valid() returns false for invalid value", false, $s->valid('hello 123', 'alpha'));

// ===== WHITESPACE =====

check("trim() removes leading/trailing whitespace", 'hello', $s->trim("  hello  "));
check("trim() trims custom characters", 'hello', $s->trim("--hello--", '-'));
check("removeNewlines() removes newline types", false, strpos($s->removeNewlines("Line1\nLine2\r\nLine3"), "\n") !== false);
check("removeNewlines() with '' removes newlines entirely", 'Line1Line2', $s->removeNewlines("Line1\nLine2", ''));
check("removeWhitespace() removes spaces and tabs", 'foobarbaz', $s->removeWhitespace("foo bar\tbaz"));

// ===== TRUNCATION AND LENGTH =====

$str = "The quick brown fox jumps over the lazy dog. It was a beautiful day.";
check("truncate() respects maxLength", true, strlen($s->truncate($str, 30)) <= 30);
$r = $s->trunc($str, 30);
check("trunc() respects maxLength", true, strlen($r) <= 30);
check("trunc() does not append ellipsis", false, strpos($r, '…') !== false);
check("maxLength() truncates string to N chars", 'Hello', $s->maxLength("Hello World", 5));
check("maxLength() limits array to N items", 3, count($s->maxLength([1, 2, 3, 4, 5], 3)));
check("minLength() returns blank when value is shorter than minimum", '', $s->minLength("Hi", 5));
check("minLength() pads right with pad character", 'Hi000', $s->minLength("Hi", 5, '0'));
check("minLength() pads left with pad character", '000Hi', $s->minLength("Hi", 5, '0', true));

// ===== CHAINING AND SHORTHAND =====

check("text20() shorthand limits to 20 chars", true, strlen($s->text20("This string is longer than twenty characters long")) <= 20);
check("text_entities() chains text() then entities()", 'Tom &amp; Jerry', $s->text_entities('<b>Tom & Jerry</b>'));
check("sanitize() calls sanitizer by name", 'Hello World', $s->sanitize("Hello <b>World</b>", 'text'));
check("sanitize() chained 'text,entities'", 'Hello World', $s->sanitize("Hello <b>World</b>", 'text,entities'));

// ===== STRING UTILITY =====

check("string() converts int to string", '42', $s->string(42));
check("string() converts bool true to '1'", '1', $s->string(true));
check("string() converts bool false to ''", '', $s->string(false));
check("string() converts null to ''", '', $s->string(null));
