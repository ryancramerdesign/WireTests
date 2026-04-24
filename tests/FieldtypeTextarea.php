<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_textarea';
$fieldtype = modules()->get('FieldtypeTextarea');
$field = fields()->get($name);

if(!$field) {
	$field = new TextareaField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Textarea';
	$field->contentType = 0; // plain text (default)
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Test: basic multi-line text roundtrip
$value = "Line one\nLine two\nLine three";
$page->set($name, $value);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->getUnformatted($name) !== $value) {
	throw new WireTestException("Multi-line text mismatch: " . var_export($page->getUnformatted($name), true));
}
wireTests()->li("Multi-line text roundtrip verified");

// Test: blank value
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->getUnformatted($name) !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($page->getUnformatted($name), true));
}
wireTests()->li("Blank value ('') verified");

// Test: HTML content stored and retrieved raw
$html = '<p>Hello <strong>World</strong></p>';
$page->set($name, $html);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->getUnformatted($name) !== $html) {
	throw new WireTestException("HTML content mismatch: " . var_export($page->getUnformatted($name), true));
}
wireTests()->li("HTML content roundtrip (getUnformatted) verified");

// Test: TextformatterEntities applied when OF is on
$field->textformatters = ['TextformatterEntities'];
$field->save();
$raw = '<p>Hello <strong>World</strong></p>';
$page->set($name, $raw);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(true);
$formatted = $page->get($name);
$page->of(false);
// entities formatter should encode < > & characters
if(strpos($formatted, '<') !== false) {
	throw new WireTestException("Expected entities encoded output, got: " . var_export($formatted, true));
}
if(strpos($formatted, '&lt;') === false) {
	throw new WireTestException("Expected &lt; in entities-encoded output, got: " . var_export($formatted, true));
}
wireTests()->li("TextformatterEntities applied on OF=on verified");

// Test: getUnformatted() always returns raw value regardless of formatter
$rawCheck = $page->getUnformatted($name);
if($rawCheck !== $raw) {
	throw new WireTestException("getUnformatted() should return raw value, got: " . var_export($rawCheck, true));
}
wireTests()->li("getUnformatted() bypasses Textformatters verified");

// Restore: remove textformatters
$field->textformatters = [];
$field->save();

// Selectors — set known plain-text value
$page->set($name, 'The quick brown fox jumps over the lazy dog');
$page->save($name);
$selectors = [
	"template=test, $name*=quick brown",
	"template=test, $name~=fox lazy",
	"template=test, $name~|=cat fox bird",
	"template=test, $name%=quick brown",
	"template=test, $name^=The quick",
	"template=test, $name\$=lazy dog",
	"template=test, $name!=\"\"",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// Blank selector
$page->set($name, '');
$page->save($name);
$p = pages()->findOne("template=test, $name=\"\"");
if($p->id !== $page->id) throw new WireTestException("Selector failed: $name=\"\"");
wireTests()->li("Selector passed: $name=\"\"");
