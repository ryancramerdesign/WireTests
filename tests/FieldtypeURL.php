<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_url';
$fieldtype = modules()->get('FieldtypeURL');
$field = fields()->get($name);

if(!$field) {
	$field = new URLField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test URL';
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Test: absolute URL
$page->set($name, 'https://processwire.com/docs/');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 'https://processwire.com/docs/') {
	throw new WireTestException("Expected 'https://processwire.com/docs/', got: " . var_export($page->get($name), true));
}
wireTests()->li("Absolute URL verified");

// Test: relative URL (allowed by default)
$page->set($name, '/local/path/');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== '/local/path/') {
	throw new WireTestException("Expected '/local/path/', got: " . var_export($page->get($name), true));
}
wireTests()->li("Relative URL verified");

// Test: blank value
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($page->get($name), true));
}
wireTests()->li("Blank value ('') verified");

// Test: dangerous scheme (javascript:) is sanitized to blank
$page->set($name, 'javascript:alert(1)');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== '') {
	throw new WireTestException("Expected javascript: URL to sanitize to '', got: " . var_export($val, true));
}
wireTests()->li("Dangerous URL scheme (javascript:) sanitized to blank verified");

// Test: noRelative=1 rejects relative URLs
$field->noRelative = 1;
$field->save();
$page->set($name, '/should/be/rejected/');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== '') {
	throw new WireTestException("Expected relative URL to be rejected with noRelative=1, got: " . var_export($val, true));
}
wireTests()->li("noRelative=1 rejects relative URL verified");

// Confirm absolute still works with noRelative=1
$page->set($name, 'https://processwire.com/');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 'https://processwire.com/') {
	throw new WireTestException("Expected absolute URL with noRelative=1, got: " . var_export($page->get($name), true));
}
wireTests()->li("Absolute URL still accepted with noRelative=1 verified");

// Restore default
$field->noRelative = 0;
$field->save();

// Selectors — value is 'https://processwire.com/' from last test above
$selectors = [
	"template=test, $name^=https://",
	"template=test, $name*=processwire.com",
	"template=test, $name\$=.com/",
	"template=test, $name%=processwire",
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
