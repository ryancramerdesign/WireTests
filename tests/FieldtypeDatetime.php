<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_datetime';
$fieldtype = modules()->get('FieldtypeDatetime');
$field = fields()->get($name);

if(!$field) {
	$field = new DatetimeField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Datetime';
	$field->dateOutputFormat = 'Y-m-d'; // default
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

$page->of(false);

// Test: Unix timestamp roundtrip (OF off returns int)
$ts = mktime(14, 30, 0, 4, 8, 2026); // 2026-04-08 14:30:00
$page->set($name, $ts);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name);
if(!is_int($val)) {
	throw new WireTestException("Expected int timestamp with OF off, got: " . var_export($val, true));
}
if($val !== $ts) {
	throw new WireTestException("Timestamp mismatch: expected $ts, got $val");
}
wireTests()->li("Unix timestamp roundtrip verified: $val");

// Test: strtotime-compatible string input
$page->set($name, '2026-04-08 14:30:00');
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name);
if($val !== $ts) {
	throw new WireTestException("Expected string input to store as $ts, got: " . var_export($val, true));
}
wireTests()->li("String input ('2026-04-08 14:30:00') stored as timestamp verified");

// Test: DateTime object input
$page->set($name, new \DateTime('2026-04-08 14:30:00'));
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name);
if($val !== $ts) {
	throw new WireTestException("Expected DateTime input to store as $ts, got: " . var_export($val, true));
}
wireTests()->li("DateTime object input stored as timestamp verified");

// Test: output formatting ON returns string
$page->of(true);
$val = $page->get($name);
if(!is_string($val) || $val === '') {
	throw new WireTestException("Expected formatted string with OF on, got: " . var_export($val, true));
}
wireTests()->li("Formatted output (OF on) returns string: '$val'");

// Test: dateOutputFormat setting is applied
$field->dateOutputFormat = 'j F Y';
$field->save();
$page = pages()->getFresh($page->id);
$page->of(true);
$val = $page->get($name);
if($val !== '8 April 2026') {
	throw new WireTestException("Expected '8 April 2026' with format 'j F Y', got: " . var_export($val, true));
}
wireTests()->li("dateOutputFormat 'j F Y' applied correctly: '$val'");

// Restore default format
$field->dateOutputFormat = 'Y-m-d';
$field->save();
$page->of(false);

// Test: blank value
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name);
if($val !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($val, true));
}
wireTests()->li("Blank value ('') verified");

// Test: blank is still '' with OF on
$page->of(true);
$val = $page->get($name);
if($val !== '') {
	throw new WireTestException("Expected blank '' with OF on, got: " . var_export($val, true));
}
wireTests()->li("Blank value stays '' with OF on verified");

// Selectors — set a known date value (non-midnight time to exercise date-only = matching)
$page->of(false);
$page->set($name, '2026-04-08 14:30:00');
$page->save($name);
$selectors = [
	"template=test, $name=2026-04-08",
	"template=test, $name=2026-04-08 14:30:00",
	"template=test, $name!=2026-04-09",
	"template=test, $name^=2026-04",
	"template=test, $name%=2026-04-08",
	"template=test, $name>2026-01-01",
	"template=test, $name>=2026-04-08",
	"template=test, $name<2027-01-01",
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
