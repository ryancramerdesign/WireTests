<?php namespace ProcessWire;
/** @var TestPage $page */

/** @var FieldtypeRepeater $fieldtype */
$fieldtype = modules()->get('FieldtypeRepeater');
$fieldtype->getFieldClass(); // triggers require_once of RepeaterField.php
modules()->get('FieldtypeText'); // ensure TextField class is loaded for sub-field

// Sub-fields used inside the repeater
$subTextField = fields()->get('headline'); // reuse existing text field from text test
if(!$subTextField) throw new WireTestException("Sub-field 'headline' not found — run text test first");

$name = 'test_repeater';
/** @var RepeaterField $field */
$field = fields()->get($name);

if(!$field) {
	$field = new RepeaterField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Repeater';
	$field->save();
	wireTests()->li("Created field: $field->name");

	// Get/create the repeater template and add sub-fields to its fieldgroup
	$repeaterTemplate = $fieldtype->_getRepeaterTemplate($field);
	$repeaterFieldgroup = $repeaterTemplate->fieldgroup;
	if(!$repeaterFieldgroup->hasField($subTextField)) {
		$repeaterFieldgroup->add($subTextField);
		$repeaterFieldgroup->save();
	}

	// Register sub-fields with the repeater field
	$field->repeaterFields = [$subTextField->id];
	$field->save();
	wireTests()->li("Repeater template: {$repeaterTemplate->name}, sub-fields: {$subTextField->name}");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field->name");
}

$page->of(false);

// Clean slate — remove any existing items
foreach($page->get($name) as $item) {
	$page->get($name)->remove($item);
}
$page->save($name);

// Test: empty value is a RepeaterPageArray
$val = $page->get($name);
if(!($val instanceof RepeaterPageArray)) {
	throw new WireTestException("Expected RepeaterPageArray, got: " . get_class($val));
}
wireTests()->li("Empty value is RepeaterPageArray verified");

// Test: add a new item via getNewItem()
$item1 = $page->get($name)->getNewItem();
if(!($item1 instanceof RepeaterPage)) {
	throw new WireTestException("Expected RepeaterPage from getNewItem(), got: " . get_class($item1));
}
$item1->set($subTextField->name, 'First Item');
$item1->save();
$page->save($name);
wireTests()->li("getNewItem() returns RepeaterPage verified");

// Test: item is retrievable with correct value
$page = pages()->getFresh($page->id);
$page->of(false);
$items = $page->get($name);
if($items->count() !== 1) {
	throw new WireTestException("Expected 1 item after add, got: " . $items->count());
}
if($items->first()->get($subTextField->name) !== 'First Item') {
	throw new WireTestException("Expected 'First Item', got: " . var_export($items->first()->get($subTextField->name), true));
}
wireTests()->li("Item value '{$subTextField->name}' = 'First Item' verified");

// Test: add a second item
$item2 = $page->get($name)->getNewItem();
$item2->set($subTextField->name, 'Second Item');
$item2->save();
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 2) {
	throw new WireTestException("Expected 2 items, got: " . $page->get($name)->count());
}
wireTests()->li("Two items added, count=2 verified");

// Test: getForPage() and getForField()
$item = $page->get($name)->first();
if($item->getForPage()->id !== $page->id) {
	throw new WireTestException("Expected getForPage() to return page id={$page->id}, got: " . $item->getForPage()->id);
}
if($item->getForField()->name !== $name) {
	throw new WireTestException("Expected getForField() to return field '$name', got: " . $item->getForField()->name);
}
wireTests()->li("getForPage() and getForField() verified");

// Test: OF=on excludes unpublished items; OF=off includes all
// Unpublish one item by setting it to hidden (status)
$item = $page->get($name)->first();
$item->addStatus(Page::statusUnpublished);
$item->save();

$page->of(true);
$countFormatted = $page->get($name)->count();

$page->of(false);
$countUnformatted = $page->get($name)->count();

if($countFormatted >= $countUnformatted) {
	throw new WireTestException("Expected OF=on to exclude unpublished items (got $countFormatted), OF=off to include all (got $countUnformatted)");
}
wireTests()->li("OF=on excludes unpublished: count=$countFormatted; OF=off includes all: count=$countUnformatted");

// Restore: re-publish
$page->of(false);
$item = $page->get($name)->first();
$item->removeStatus(Page::statusUnpublished);
$item->save();

// Test: remove an item
$page = pages()->getFresh($page->id);
$page->of(false);
$toRemove = $page->get($name)->first();
$page->get($name)->remove($toRemove);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 1) {
	throw new WireTestException("Expected 1 item after remove, got: " . $page->get($name)->count());
}
wireTests()->li("remove() item verified, count=1");

// Test: eq(0) access by index
$byIndex = $page->get($name)->eq(0);
if(!($byIndex instanceof RepeaterPage)) {
	throw new WireTestException("Expected RepeaterPage from eq(0), got: " . get_class($byIndex));
}
wireTests()->li("eq(0) index access verified: '{$byIndex->get($subTextField->name)}'");

// Selectors — clean slate, add a known item for subfield matching
$page->of(false);
$items = $page->get($name);
foreach($items as $item) $items->remove($item);
$page->save($name);
$item = $page->get($name)->getNewItem();
$item->set($subTextField->name, 'Selector Test Item');
$item->save();
$page->save($name);
$sub = $subTextField->name;
$selectors = [
	"template=test, $name.count>0",
	"template=test, $name.count=1",
	"template=test, $name.$sub*=Selector Test",
	"template=test, $name.$sub~=Item",
	"template=test, $name.$sub^=Selector",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// Empty count — getFresh to avoid stale cache, then remove all
$page = pages()->getFresh($page->id);
$page->of(false);
$items = $page->get($name);
foreach($items as $item) $items->remove($item);
$page->save($name);
$p = pages()->findOne("template=test, $name.count=0");
if($p->id !== $page->id) throw new WireTestException("Selector failed: $name.count=0");
wireTests()->li("Selector passed: $name.count=0");
