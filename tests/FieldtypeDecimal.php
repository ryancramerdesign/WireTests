<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_decimal';
$fieldtype = modules()->get('FieldtypeDecimal');
$field = fields()->get($name);

if(!$field) {
	$field = new DecimalField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Decimal';
	$field->digits = 10;    // default
	$field->precision = 2;  // default
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Test: value is returned as string, not float
$page->set($name, '123.45');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== '123.45') {
	throw new WireTestException("Expected string '123.45', got: " . var_export($val, true));
}
wireTests()->li("String value ('123.45') verified");

// Test: int input is accepted and returned as string
$page->set($name, 99);
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if(!is_string($val)) {
	throw new WireTestException("Expected string type, got: " . var_export($val, true));
}
if((float) $val !== 99.0) {
	throw new WireTestException("Expected value of 99, got: " . var_export($val, true));
}
wireTests()->li("Integer input (99) returned as string: " . var_export($val, true));

// Test: negative value
$page->set($name, '-7.50');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if((float) $val !== -7.5) {
	throw new WireTestException("Expected -7.50, got: " . var_export($val, true));
}
wireTests()->li("Negative value (-7.50) verified: " . var_export($val, true));

// Test: exact decimal precision (no floating-point error)
$page->set($name, '0.10');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
// float 0.1 is famously imprecise; DECIMAL should return exactly '0.10' or '0.1'
if((float) $val !== 0.1) {
	throw new WireTestException("Expected 0.10 exact, got: " . var_export($val, true));
}
wireTests()->li("Exact decimal precision (0.10) verified: " . var_export($val, true));

// Test: blank value
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($val, true));
}
wireTests()->li("Blank value ('') verified");

// Test: zero value (note same zeroNotEmpty behavior as Integer — may return '' or '0.00')
$page->set($name, '0.00');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== '' && (float) $val !== 0.0) {
	throw new WireTestException("Expected '0.00' or '', got: " . var_export($val, true));
}
wireTests()->li("Zero value verified: " . var_export($val, true));

// Selectors — set a known value for comparison tests
$page->set($name, '123.45');
$page->save($name);
$selectors = [
	"template=test, $name=123.45",
	"template=test, $name>100",
	"template=test, $name>=123.45",
	"template=test, $name<200",
	"template=test, $name<=123.45",
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
