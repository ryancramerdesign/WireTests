<?php namespace ProcessWire;
/** @var TestPage $page */

$version = $modules->getModuleInfoProperty('FieldtypeCustom', 'version');
if($version < 5) {
	wireTests()->li("Skipping test (version=$version, required version=5)");
	return;
}

$name = 'test_custom';

// Create field if it doesn't exist
/** @var CustomField $field */
$field = fields()->get($name);
if(!$field) {
	$field = new CustomField();
	$field->type = modules()->get('FieldtypeCustom');
	$field->name = $name;
	$field->label = 'Test custom';
	$field->save();
	wireTests()->li("Created field: $name");
}

// Add field to test template/fieldgroup
$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Set values via property access
$page->of(false);
$page->$name->first_name = 'Ada';
$page->$name->age = 36;
$page->$name->color = 'g';
$page->save($name);

// Verify with fresh page
$fresh = pages()->getFresh($page->id);
$fresh->of(false);
/** @var CustomWireData $data */
$data = $fresh->get($name);

if(!($data instanceof CustomWireData)) {
	throw new WireTestException("Expected CustomWireData, got: " . get_class($data));
}
if($data->first_name !== 'Ada') {
	throw new WireTestException("first_name mismatch: '{$data->first_name}' != 'Ada'");
}
if((int) $data->age !== 36) {
	throw new WireTestException("age mismatch: '{$data->age}' != 36");
}
if($data->color !== 'g') {
	throw new WireTestException("color mismatch: '{$data->color}' != 'g'");
}
wireTests()->li("Set/get values verified (first_name, age, color)");

// Test hasValue()
if(!$data->hasValue('first_name')) {
	throw new WireTestException("hasValue('first_name') returned false");
}
wireTests()->li("hasValue() works");

// Test getArray()
$arr = $data->getArray();
if(!is_array($arr) || !array_key_exists('first_name', $arr)) {
	throw new WireTestException("getArray() did not return expected array");
}
wireTests()->li("getArray() works");

// Test label() for subfield label
$label = $data->label('first_name');
if($label !== 'First name') {
	throw new WireTestException("label('first_name') returned '$label', expected 'First name'");
}
wireTests()->li("label() works: '$label'");

// Test optionLabel() for select subfield
$optLabel = $data->optionLabel('color', 'g');
if($optLabel !== 'Green') {
	throw new WireTestException("optionLabel('color', 'g') returned '$optLabel', expected 'Green'");
}
wireTests()->li("optionLabel() works: '$optLabel'");

// Test label() shorthand for option label
$optLabel2 = $data->label('color', 'g');
if($optLabel2 !== 'Green') {
	throw new WireTestException("label('color', 'g') returned '$optLabel2', expected 'Green'");
}
wireTests()->li("label() option shorthand works");

// Test setArray()
$page->of(false);
$page->$name->setArray(['first_name' => 'Grace', 'age' => 85, 'color' => 'b']);
$page->save($name);
$fresh2 = pages()->getFresh($page->id);
$fresh2->of(false);
$data2 = $fresh2->get($name);
if($data2->first_name !== 'Grace') {
	throw new WireTestException("setArray() first_name mismatch: '{$data2->first_name}'");
}
if($data2->color !== 'b') {
	throw new WireTestException("setArray() color mismatch: '{$data2->color}'");
}
wireTests()->li("setArray() works");

// Test set() via array
$page->of(false);
$page->set($name, ['first_name' => 'Ada', 'age' => 36, 'color' => 'g']);
$page->save($name);
$fresh3 = pages()->getFresh($page->id);
$fresh3->of(false);
$data3 = $fresh3->get($name);
if($data3->first_name !== 'Ada') {
	throw new WireTestException("set() via array mismatch: '{$data3->first_name}'");
}
wireTests()->li("set() via array works");

// Iterate subfield values
$iterKeys = [];
foreach($fresh3->get($name) as $key => $value) {
	$iterKeys[] = $key;
}
if(!in_array('first_name', $iterKeys) || !in_array('color', $iterKeys)) {
	throw new WireTestException("foreach iteration missing expected keys: " . implode(', ', $iterKeys));
}
wireTests()->li("foreach iteration works");

// Test selectors
$selectors = [
	"template=test, $name.first_name=Ada",
	"template=test, $name.first_name!=\"\"",
	"template=test, $name.first_name%=Ada",
	"template=test, $name.first_name^=Ada",
	"template=test, $name.color=g",
	"template=test, $name.color!=r",
	"template=test, $name.age>30",
	"template=test, $name.age>=36",
	"template=test, $name*=Ada",
];

foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id === $page->id) {
		wireTests()->li("Selector passed: $selector");
	} else {
		throw new WireTestException("Selector failed: $selector");
	}
}

// Test renameSubfieldData() — migrate stored data from old key to new key
// At this point first_name='Ada' is saved on $page
$tools = $field->type->tools();
$n = $tools->renameSubfieldData($field, 'first_name', 'first_name_new');
if($n < 1) throw new WireTestException("renameSubfieldData() returned $n, expected >= 1");
wireTests()->li("renameSubfieldData() updated $n row(s)");

$freshR = pages()->getFresh($page->id);
$freshR->of(false);
$dataR = $freshR->getUnformatted($name)->getArray();
if(!array_key_exists('first_name_new', $dataR)) {
	throw new WireTestException("New key 'first_name_new' not found after rename; keys: " . implode(', ', array_keys($dataR)));
}
if($dataR['first_name_new'] !== 'Ada') {
	throw new WireTestException("Value under new key is '{$dataR['first_name_new']}', expected 'Ada'");
}
if(array_key_exists('first_name', $dataR) && $dataR['first_name'] !== '') {
	throw new WireTestException("Old key 'first_name' still has a value after rename: '{$dataR['first_name']}'");
}
wireTests()->li("Data accessible under new key 'first_name_new' after rename");

// Rename back so subsequent test runs start clean
$tools->renameSubfieldData($field, 'first_name_new', 'first_name');
$freshR2 = pages()->getFresh($page->id);
$freshR2->of(false);
$dataR2 = $freshR2->getUnformatted($name)->getArray();
if(!array_key_exists('first_name', $dataR2) || $dataR2['first_name'] !== 'Ada') {
	throw new WireTestException("Rename-back failed; first_name='{$dataR2['first_name']}'");
}
wireTests()->li("Rename-back to 'first_name' verified — data intact");
