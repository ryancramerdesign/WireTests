<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_toggle';
$fieldtype = modules()->get('FieldtypeToggle');
$field = fields()->get($name);

if(!$field) {
	$field = new ToggleField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Toggle';
	$field->useOther = 1; // enable the optional "other" (2) state
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// With output formatting OFF for reliable raw value comparisons
$page->of(false);

// Test: yes (1)
$page->set($name, 1);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== 1) {
	throw new WireTestException("Expected 1 (yes), got: " . var_export($page->get($name), true));
}
wireTests()->li("Yes (1) verified");

// Test: no (0)
$page->set($name, 0);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== 0) {
	throw new WireTestException("Expected 0 (no), got: " . var_export($page->get($name), true));
}
wireTests()->li("No (0) verified");

// Test: other (2)
$page->set($name, 2);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== 2) {
	throw new WireTestException("Expected 2 (other), got: " . var_export($page->get($name), true));
}
wireTests()->li("Other (2) verified");

// Test: unknown / no-selection ('')
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== '') {
	throw new WireTestException("Expected '' (unknown), got: " . var_export($page->get($name), true));
}
wireTests()->li("Unknown ('') verified");

// Test: keyword string inputs
$page->set($name, 'yes');
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== 1) {
	throw new WireTestException("Expected 'yes' to store as 1, got: " . var_export($page->get($name), true));
}
wireTests()->li("Keyword 'yes' stored as 1 verified");

$page->set($name, 'no');
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== 0) {
	throw new WireTestException("Expected 'no' to store as 0, got: " . var_export($page->get($name), true));
}
wireTests()->li("Keyword 'no' stored as 0 verified");

$page->set($name, 'unknown');
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== '') {
	throw new WireTestException("Expected 'unknown' to store as '', got: " . var_export($page->get($name), true));
}
wireTests()->li("Keyword 'unknown' stored as '' verified");

// Test: bool inputs
$page->set($name, true);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== 1) {
	throw new WireTestException("Expected bool true to store as 1, got: " . var_export($page->get($name), true));
}
wireTests()->li("Bool true stored as 1 verified");

$page->set($name, false);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name) !== 0) {
	throw new WireTestException("Expected bool false to store as 0, got: " . var_export($page->get($name), true));
}
wireTests()->li("Bool false stored as 0 verified");

// Test: 0 and '' are distinct (unlike Checkbox/Integer)
$page->set($name, 0);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$noVal = $page->get($name);

$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$unknownVal = $page->get($name);

if($noVal === $unknownVal) {
	throw new WireTestException("Expected 0 (no) and '' (unknown) to be distinct, but both returned: " . var_export($noVal, true));
}
wireTests()->li("0 (no) and '' (unknown) are distinct: 0=" . var_export($noVal, true) . ", ''=" . var_export($unknownVal, true));

// Test: formatType=1 (boolean) with output formatting ON
$field->formatType = 1;
$field->save();
$page->set($name, 1);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(true);
$val = $page->get($name);
if($val !== true) {
	throw new WireTestException("Expected bool true with formatType=1, got: " . var_export($val, true));
}
wireTests()->li("formatType=1 returns bool true for yes verified");

// Restore defaults
$page->of(false);
$field->formatType = 0;
$field->save();

// Selectors (only = and != are supported)
$page->set($name, 1);
$page->save($name);
$selectors = [
	"template=test, $name=1",
	"template=test, $name=yes",
	"template=test, $name!=0",
	"template=test, $name!=\"\"",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// No (0)
$page->set($name, 0);
$page->save($name);
foreach(["template=test, $name=0", "template=test, $name=no"] as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// Unknown ('')
$page->set($name, '');
$page->save($name);
foreach(["template=test, $name=\"\"", "template=test, $name=unknown"] as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
