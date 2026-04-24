<?php namespace ProcessWire;
/** @var TestPage $page */

$fieldtype = modules()->get('FieldtypePage');

// Use home page as a known reference target
$refPage = pages()->get(1);
if(!$refPage->id) throw new WireTestException("Could not load reference page (id=1)");

// --- PageArray mode (derefAsPage=0, default) ---

$name = 'test_page';
/** @var PageField $field */
$field = fields()->get($name);
if(!$field) {
	$field = new PageField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Page';
	$field->derefAsPage = FieldtypePage::derefAsPageArray; // 0, default
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field->name");
}

// Test: set by Page object, get back PageArray
$page->of(false);
$page->set($name, $refPage);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name);
if(!($val instanceof PageArray)) {
	throw new WireTestException("Expected PageArray, got: " . get_class($val));
}
if($val->count() !== 1 || $val->first()->id !== $refPage->id) {
	throw new WireTestException("Expected PageArray with id={$refPage->id}, got count={$val->count()}");
}
wireTests()->li("derefAsPage=0: set by Page object, got PageArray with 1 item verified");

// Test: set by page ID
$page->set($name, $refPage->id);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name);
if($val->count() !== 1 || $val->first()->id !== $refPage->id) {
	throw new WireTestException("Expected page id={$refPage->id}, got: " . $val->count());
}
wireTests()->li("derefAsPage=0: set by page ID verified");

// Test: empty value returns empty PageArray
$page->set($name, null);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name);
if(!($val instanceof PageArray) || $val->count() !== 0) {
	throw new WireTestException("Expected empty PageArray, got: " . var_export($val, true));
}
wireTests()->li("derefAsPage=0: empty value returns empty PageArray verified");

// Test: add() and remove()
$page->of(false);
$page->get($name)->add($refPage);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 1) {
	throw new WireTestException("Expected 1 after add(), got: " . $page->get($name)->count());
}
wireTests()->li("derefAsPage=0: add() verified");

$page->of(false);
$page->get($name)->remove($refPage);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 0) {
	throw new WireTestException("Expected 0 after remove(), got: " . $page->get($name)->count());
}
wireTests()->li("derefAsPage=0: remove() verified");

// --- Single-page mode: derefAsPage=1 (Page or false) ---

$name1 = 'test_page_or_false';
/** @var PageField $field1 */
$field1 = fields()->get($name1);
if(!$field1) {
	$field1 = new PageField();
	$field1->name = $name1;
	$field1->type = $fieldtype;
	$field1->label = 'Test Page Or False';
	$field1->derefAsPage = FieldtypePage::derefAsPageOrFalse; // 1
	$field1->save();
	wireTests()->li("Created field: $field1->name");
}
if(!$fieldgroup->hasField($field1)) {
	$fieldgroup->add($field1);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field1->name");
}

$page->set($name1, $refPage->id);
$page->save($name1);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name1);
if(!($val instanceof Page) || $val->id !== $refPage->id) {
	throw new WireTestException("Expected Page with id={$refPage->id}, got: " . var_export($val, true));
}
wireTests()->li("derefAsPage=1: populated returns Page verified");

$page->set($name1, null);
$page->save($name1);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name1);
if($val !== false) {
	throw new WireTestException("Expected false when empty with derefAsPage=1, got: " . var_export($val, true));
}
wireTests()->li("derefAsPage=1: empty returns false verified");

// --- Single-page mode: derefAsPage=2 (Page or NullPage) ---

$name2 = 'test_page_or_null';
/** @var PageField $field2 */
$field2 = fields()->get($name2);
if(!$field2) {
	$field2 = new PageField();
	$field2->name = $name2;
	$field2->type = $fieldtype;
	$field2->label = 'Test Page Or NullPage';
	$field2->derefAsPage = FieldtypePage::derefAsPageOrNullPage; // 2
	$field2->save();
	wireTests()->li("Created field: $field2->name");
}
if(!$fieldgroup->hasField($field2)) {
	$fieldgroup->add($field2);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field2->name");
}

$page->set($name2, $refPage->id);
$page->save($name2);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name2);
if(!($val instanceof Page) || $val->id !== $refPage->id) {
	throw new WireTestException("Expected Page with id={$refPage->id}, got: " . var_export($val, true));
}
wireTests()->li("derefAsPage=2: populated returns Page verified");

$page->set($name2, null);
$page->save($name2);
$page = pages()->getFresh($page->id);
$page->of(false);
$val = $page->get($name2);
if(!($val instanceof NullPage)) {
	throw new WireTestException("Expected NullPage when empty with derefAsPage=2, got: " . get_class($val));
}
wireTests()->li("derefAsPage=2: empty returns NullPage verified");

// Selectors — use test_page (derefAsPage=0) with home page as reference
$page->of(false);
$page->set($name, $refPage);
$page->save($name);
$selectors = [
	"template=test, $name={$refPage->id}",
	"template=test, $name={$refPage->name}",
	"template=test, $name={$refPage->path}",
	"template=test, $name.count>0",
	"template=test, $name!=\"\"",
	"template=test, $name.template={$refPage->template->name}",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// Blank selector
$page->set($name, null);
$page->save($name);
$p = pages()->findOne("template=test, $name=\"\"");
if($p->id !== $page->id) throw new WireTestException("Selector failed: $name=\"\"");
wireTests()->li("Selector passed: $name=\"\"");
