<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_selector';
$fieldtype = modules()->get('FieldtypeSelector');
$field = fields()->get($name);

if(!$field) {
	$field = new SelectorField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Selector';
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field->name");
}

// Test: basic selector string roundtrip
$selector = 'template=basic-page, sort=-created, limit=10';
$page->set($name, $selector);
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== $selector) {
	throw new WireTestException("Expected '$selector', got: " . var_export($page->get($name), true));
}
wireTests()->li("Selector string roundtrip verified");

// Test: blank value
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($page->get($name), true));
}
wireTests()->li("Blank value ('') verified");

// Test: stored selector can be used to find pages
$page->set($name, 'id>0, limit=1');
$page->save($name);
$page = pages()->getFresh($page->id);
$storedSelector = $page->get($name);
$results = pages()->find($storedSelector);
if(!($results instanceof PageArray)) {
	throw new WireTestException("Expected PageArray from stored selector, got: " . get_class($results));
}
wireTests()->li("Stored selector executed successfully, found {$results->count()} page(s)");

// Test: initValue prefix is prepended on wakeup and stripped before saving
$field->initValue = 'template=admin';
$field->save();

$userPart = 'sort=name';
$page->set($name, $userPart);
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);

// The returned value should include the initValue prefix
if(strpos($val, 'template=admin') === false) {
	throw new WireTestException("Expected initValue prefix 'template=admin' in returned value, got: " . var_export($val, true));
}
wireTests()->li("initValue prefix present in returned value: " . var_export($val, true));

// initValue is applied at wakeup (not format time), so getUnformatted() also
// returns the combined value — this is expected and documented behavior
$page->of(false);
$rawVal = $page->getUnformatted($name);
if(strpos($rawVal, 'template=admin') === false) {
	throw new WireTestException("Expected initValue in getUnformatted() too (applied at wakeup), got: " . var_export($rawVal, true));
}
wireTests()->li("getUnformatted() also includes initValue prefix (wakeup, not format-time) verified");

// Restore: clear initValue
$field->initValue = '';
$field->save();

// Selectors — set a known value for selector field searching
$page->set($name, 'template=basic-page, sort=title, limit=10');
$page->save($name);
$selectors = [
	"template=test, $name*=\"template=basic-page\"",
	"template=test, $name~=basic-page",
	"template=test, $name^=\"template=basic-page\"",
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
