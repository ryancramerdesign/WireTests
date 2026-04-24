<?php namespace ProcessWire;
/** @var TestPage $page */

/** @var FieldtypePageTable $fieldtype */
$fieldtype = modules()->get('FieldtypePageTable');

// Create or get a template for PageTable items
$itemTemplateName = 'test-pagetable-item';
$itemTemplate = templates()->get($itemTemplateName);
if(!$itemTemplate) {
	$fieldgroup = new Fieldgroup();
	$fieldgroup->name = $itemTemplateName;
	$fieldgroup->add(fields()->get('title'));
	$fieldgroup->save();

	$itemTemplate = new Template();
	$itemTemplate->name = $itemTemplateName;
	$itemTemplate->fieldgroup = $fieldgroup;
	$itemTemplate->save();
	wireTests()->li("Created item template: $itemTemplateName");
}

$name = 'test_pagetable';
/** @var PageTableField $field */
$field = fields()->get($name);
if(!$field) {
	$field = new PageTableField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test PageTable';
	$field->template_id = $itemTemplate->id;
	// parent_id=0 means items are children of the owning page
	$field->save();
	wireTests()->li("Created field: $field->name (template: $itemTemplateName, parent: owning page)");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field->name");
}

$page->of(false);

// Clean slate — remove and delete any existing items
foreach($page->get($name) as $item) {
	$page->get($name)->remove($item);
	pages()->delete($item);
}
$page->save($name);

// Test: empty value is a PageTableArray
$val = $page->get($name);
if(!($val instanceof PageTableArray)) {
	throw new WireTestException("Expected PageTableArray, got: " . get_class($val));
}
if($val->count() !== 0) {
	throw new WireTestException("Expected empty PageTableArray, got count: " . $val->count());
}
wireTests()->li("Empty value is PageTableArray (count=0) verified");

// Test: getNewItem() creates a new unsaved Page
$item1 = $page->get($name)->getNewItem();
if(!($item1 instanceof Page)) {
	throw new WireTestException("Expected Page from getNewItem(), got: " . get_class($item1));
}
if($item1->template->name !== $itemTemplateName) {
	throw new WireTestException("Expected item template '$itemTemplateName', got: " . $item1->template->name);
}
wireTests()->li("getNewItem() returns Page with correct template verified");

// Test: save item and verify it appears in field value
$item1->title = 'First Item';
$item1->save();
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$items = $page->get($name);
if($items->count() !== 1) {
	throw new WireTestException("Expected 1 item after save, got: " . $items->count());
}
if($items->first()->title !== 'First Item') {
	throw new WireTestException("Expected title 'First Item', got: " . var_export($items->first()->title, true));
}
wireTests()->li("Item saved and retrieved, title='First Item' verified");

// Test: items are real pages with IDs and parents
$savedItem = $items->first();
if(!$savedItem->id) {
	throw new WireTestException("Expected saved item to have an id");
}
if($savedItem->parent->id !== $page->id) {
	throw new WireTestException("Expected item parent to be test page (id={$page->id}), got: " . $savedItem->parent->id);
}
wireTests()->li("Item is a real page: id={$savedItem->id}, parent={$savedItem->parent->path}");

// Test: items are independently findable via $pages->find()
$found = pages()->find("template=$itemTemplateName, parent={$page->id}");
if(!$found->has($savedItem)) {
	throw new WireTestException("Expected item to be findable via pages()->find()");
}
wireTests()->li("Item independently findable via pages()->find() verified");

// Test: add a second item
$item2 = $page->get($name)->getNewItem();
$item2->title = 'Second Item';
$item2->save();
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 2) {
	throw new WireTestException("Expected 2 items, got: " . $page->get($name)->count());
}
wireTests()->li("Two items added, count=2 verified");

// Test: OF=on excludes unpublished items
$page->of(false);
$item = $page->get($name)->first();
$item->addStatus(Page::statusUnpublished);
$item->save();

$page->of(true);
$countFormatted = $page->get($name)->count();
$page->of(false);
$countUnformatted = $page->get($name)->count();

if($countFormatted >= $countUnformatted) {
	throw new WireTestException("Expected OF=on to exclude unpublished (got $countFormatted), OF=off to include all (got $countUnformatted)");
}
wireTests()->li("OF=on excludes unpublished: count=$countFormatted; OF=off includes all: count=$countUnformatted");

// Restore published status
$page->of(false);
$item = $page->get($name)->first();
$item->removeStatus(Page::statusUnpublished);
$item->save();

// Test: remove() detaches item from field but does NOT delete the page
$page = pages()->getFresh($page->id);
$page->of(false);
$toRemove = $page->get($name)->first();
$removeId = $toRemove->id;
$page->get($name)->remove($toRemove);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 1) {
	throw new WireTestException("Expected 1 item after remove(), got: " . $page->get($name)->count());
}
// The page itself should still exist
$stillExists = pages()->get($removeId);
if(!$stillExists->id) {
	throw new WireTestException("Expected removed item page to still exist (remove != delete), but it's gone");
}
wireTests()->li("remove() detaches from field but page still exists (id=$removeId) verified");

// Clean up: delete all item pages
foreach(pages()->find("template=$itemTemplateName, parent={$page->id}, include=all") as $item) {
	pages()->delete($item);
}
// Also delete the re-added item
pages()->delete($stillExists);
$page->of(false);
$page->get($name)->removeAll();
$page->save($name);
wireTests()->li("Cleanup: all item pages deleted");

// Selectors — add a known item for subfield matching
$page = pages()->getFresh($page->id);
$page->of(false);
$selectorItem = $page->get($name)->getNewItem();
$selectorItem->title = 'Selector Test Item';
$selectorItem->save();
$page->save($name);
$selectors = [
	"template=test, $name.count>0",
	"template=test, $name.title*=Selector Test",
	"template=test, $name.title=Selector Test Item",
	"template=test, $name.template=$itemTemplateName",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// count=0 — delete item and verify
pages()->delete($selectorItem);
$page = pages()->getFresh($page->id);
$page->of(false);
$page->get($name)->removeAll();
$page->save($name);
$p = pages()->findOne("template=test, $name.count=0");
if($p->id !== $page->id) throw new WireTestException("Selector failed: $name.count=0");
wireTests()->li("Selector passed: $name.count=0");
