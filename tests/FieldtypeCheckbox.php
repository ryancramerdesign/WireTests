<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_checkbox';
$field = fields()->get($name);

$fieldtype = modules()->get('FieldtypeCheckbox');

if(!$field) {
	$field = new CheckboxField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Checkbox';
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Test: set checked (1)
$page->set($name, 1);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 1) {
	throw new WireTestException("Expected 1, got: " . var_export($page->get($name), true));
}
wireTests()->li("Checked value (1) verified");

// Test: set unchecked (0)
$page->set($name, 0);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 0) {
	throw new WireTestException("Expected 0, got: " . var_export($page->get($name), true));
}
wireTests()->li("Unchecked value (0) verified");

// Test: bool true sanitizes to 1
$page->set($name, true);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 1) {
	throw new WireTestException("Expected 1 from bool true, got: " . var_export($page->get($name), true));
}
wireTests()->li("Bool true sanitized to 1 verified");

// Test: bool false sanitizes to 0
$page->set($name, false);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 0) {
	throw new WireTestException("Expected 0 from bool false, got: " . var_export($page->get($name), true));
}
wireTests()->li("Bool false sanitized to 0 verified");
