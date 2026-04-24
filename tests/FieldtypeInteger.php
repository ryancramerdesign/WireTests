<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_integer';
$fieldtype = modules()->get('FieldtypeInteger');
$field = fields()->get($name);

if(!$field) {
	$field = new IntegerField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Integer';
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Test: positive integer
$page->set($name, 42);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 42) {
	throw new WireTestException("Expected int 42, got: " . var_export($page->get($name), true));
}
wireTests()->li("Positive integer (42) verified");

// Test: negative integer
$page->set($name, -10);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== -10) {
	throw new WireTestException("Expected int -10, got: " . var_export($page->get($name), true));
}
wireTests()->li("Negative integer (-10) verified");

// Test: blank value (empty string, not 0)
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($page->get($name), true));
}
wireTests()->li("Blank value ('') verified");

// Test: zero is stored and returned as int 0
$page->set($name, 0);
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== 0 && $val !== '') {
	throw new WireTestException("Expected int 0 or '', got: " . var_export($val, true));
}
wireTests()->li("Zero value verified: " . var_export($val, true));

// Selectors — set a known non-zero value for comparison tests
$page->set($name, 42);
$page->save($name);
$selectors = [
	"template=test, $name=42",
	"template=test, $name>40",
	"template=test, $name>=42",
	"template=test, $name<100",
	"template=test, $name<=42",
	"template=test, $name!=99",
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
