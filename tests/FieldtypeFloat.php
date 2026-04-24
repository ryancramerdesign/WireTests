<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_float';
$fieldtype = modules()->get('FieldtypeFloat');
$field = fields()->get($name);

if(!$field) {
	$field = new FloatField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Float';
	$field->precision = 2; // default
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Test: basic float value
$page->set($name, 3.14);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 3.14) {
	throw new WireTestException("Expected float 3.14, got: " . var_export($page->get($name), true));
}
wireTests()->li("Basic float (3.14) verified");

// Test: negative float
$page->set($name, -2.5);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== -2.5) {
	throw new WireTestException("Expected float -2.5, got: " . var_export($page->get($name), true));
}
wireTests()->li("Negative float (-2.5) verified");

// Test: precision rounding (default=2 decimals) — 3.14159 should round to 3.14
$page->set($name, 3.14159);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 3.14) {
	throw new WireTestException("Expected 3.14159 rounded to 3.14, got: " . var_export($page->get($name), true));
}
wireTests()->li("Precision rounding (3.14159 → 3.14) verified");

// Test: blank value
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($page->get($name), true));
}
wireTests()->li("Blank value ('') verified");

// Test: zero comes back as '' (same as Integer behavior without zeroNotEmpty)
$page->set($name, 0.0);
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== 0.0 && $val !== '') {
	throw new WireTestException("Expected 0.0 or '', got: " . var_export($val, true));
}
wireTests()->li("Zero value verified: " . var_export($val, true));

// Test: precision=-1 disables rounding — update field and re-test
$field->precision = -1;
$field->save();
$page->set($name, 3.14159);
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
// single-precision float has ~7 significant digits, so check we get more than 2 decimal places
if(round((float) $val, 2) === $val) {
	throw new WireTestException("Expected unrounded value with precision=-1, got: " . var_export($val, true));
}
wireTests()->li("Precision=-1 (no rounding) verified: $val");

// Restore default precision
$field->precision = 2;
$field->save();

// Selectors — set a known value for comparison tests
$page->set($name, 3.14);
$page->save($name);
$selectors = [
	"template=test, $name=3.14",
	"template=test, $name!=3.15",
	"template=test, $name>3",
	"template=test, $name<4",
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
