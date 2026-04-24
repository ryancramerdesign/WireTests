<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'test_email';
$fieldtype = modules()->get('FieldtypeEmail');
$field = fields()->get($name);

if(!$field) {
	$field = new EmailField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Email';
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Test: valid email address
$page->set($name, 'user@example.com');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 'user@example.com') {
	throw new WireTestException("Expected 'user@example.com', got: " . var_export($page->get($name), true));
}
wireTests()->li("Valid email verified");

// Test: blank value
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== '') {
	throw new WireTestException("Expected blank '', got: " . var_export($page->get($name), true));
}
wireTests()->li("Blank value ('') verified");

// Test: invalid email is sanitized to blank
$page->set($name, 'not-an-email');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val !== '') {
	throw new WireTestException("Expected invalid email to sanitize to '', got: " . var_export($val, true));
}
wireTests()->li("Invalid email sanitized to blank verified");

// Test: email with subdomains and plus addressing
$page->set($name, 'user+tag@mail.example.co.uk');
$page->save($name);
$page = pages()->getFresh($page->id);
if($page->get($name) !== 'user+tag@mail.example.co.uk') {
	throw new WireTestException("Expected 'user+tag@mail.example.co.uk', got: " . var_export($page->get($name), true));
}
wireTests()->li("Complex valid email (plus addressing, subdomain) verified");

// Test: email with uppercase is preserved or normalized
$page->set($name, 'User@Example.COM');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val === '') {
	throw new WireTestException("Expected uppercase email to be accepted, got blank");
}
wireTests()->li("Uppercase email accepted: " . var_export($val, true));

// Selectors — set a known value
$page->set($name, 'user@example.com');
$page->save($name);
$selectors = [
	"template=test, $name=user@example.com",
	"template=test, $name*=example",
	"template=test, $name\$=.com",
	"template=test, $name^=user",
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
