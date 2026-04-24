<?php namespace ProcessWire;
/** @var TestPage $page */

$version = $modules->getModuleInfoProperty('FieldtypeRepeaterMatrix', 'version');
if($version < 14) {
	wireTests()->li("Skipping test (version=$version, required version=14)");
	return;
}

/** @var FieldtypeRepeaterMatrix $fieldtype */
$fieldtype = modules()->get('FieldtypeRepeaterMatrix');
$fieldtype->getFieldClass(); // triggers require_once of RepeaterMatrixField.php
modules()->get('FieldtypeText'); // ensure TextField class is loaded for sub-field

$subTextField = fields()->get('headline');
if(!$subTextField) throw new WireTestException("Sub-field 'headline' not found — run text test first");

$name = 'test_matrix';
/** @var RepeaterMatrixField $field */
$field = fields()->get($name);

if(!$field) {
	/** @var RepeaterMatrixField $field */
	$field = fields()->new($fieldtype, $name, 'Test matrix');
	wireTests()->li("Created field: $field->name");

	// Add sub-fields to the matrix template's fieldgroup
	$matrixFieldgroup = $field->getRepeaterFieldgroup();
	if(!$matrixFieldgroup->hasField($subTextField)) {
		$matrixFieldgroup->add($subTextField);
		$matrixFieldgroup->save();
	}

	// Define matrix types
	$field->addMatrixType('banner', [
		'label' => 'Banner', 
		'fields' => [$subTextField]
	]);
	$field->addMatrixType('text_block', [
		'label' => 'Text Block', 
		'fields' => [$subTextField]
	]);
	$field->save();
	wireTests()->li("Matrix types: banner (n=1), text_block (n=2)");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field->name");
}

$page->of(false);

// Clean slate
$items = $page->get($name);
foreach($items as $item) $items->remove($item);
$page->save($name);

// Test: empty value is RepeaterMatrixPageArray
$val = $page->get($name);
if(!($val instanceof RepeaterMatrixPageArray)) {
	throw new WireTestException("Expected RepeaterMatrixPageArray, got: " . get_class($val));
}
wireTests()->li("Empty value is RepeaterMatrixPageArray verified");

// Test: getNewItem() with type name
$item1 = $page->get($name)->getNewItem('banner');
if(!($item1 instanceof RepeaterMatrixPage)) {
	throw new WireTestException("Expected RepeaterMatrixPage from getNewItem('banner'), got: " . get_class($item1));
}
wireTests()->li("getNewItem('banner') returns RepeaterMatrixPage verified");

// Test: type and matrix('n') on new item
if($item1->type !== 'banner') {
	throw new WireTestException("Expected type='banner', got: " . var_export($item1->type, true));
}
$bannerTypeN = $field->getMatrixTypeByName('banner');
if($item1->matrix('n') !== $bannerTypeN) {
	throw new WireTestException("Expected matrix('n')=$bannerTypeN, got: " . var_export($item1->matrix('n'), true));
}
wireTests()->li("\$item->type='banner', \$item->matrix('n')=$bannerTypeN verified");

// Save item and reload
$item1->set($subTextField->name, 'Banner Headline');
$item1->save();
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$items = $page->get($name);
if($items->count() !== 1) {
	throw new WireTestException("Expected 1 item after add, got: " . $items->count());
}
$r = $items->first();
if($r->type !== 'banner') {
	throw new WireTestException("Expected type='banner' after reload, got: " . var_export($r->type, true));
}
if($r->get($subTextField->name) !== 'Banner Headline') {
	throw new WireTestException("Expected headline='Banner Headline', got: " . var_export($r->get($subTextField->name), true));
}
wireTests()->li("Item saved and reloaded: type='{$r->type}', {$subTextField->name}='{$r->get($subTextField->name)}'");

// Test: getForPage() and getForField()
if($r->getForPage()->id !== $page->id) {
	throw new WireTestException("Expected getForPage() id={$page->id}, got: " . $r->getForPage()->id);
}
if($r->getForField()->name !== $name) {
	throw new WireTestException("Expected getForField() name='$name', got: " . $r->getForField()->name);
}
wireTests()->li("getForPage() and getForField() verified");

// Test: getNewItem() by type number
$textBlockTypeN = $field->getMatrixTypeByName('text_block');
$item2 = $page->get($name)->getNewItem($textBlockTypeN);
if($item2->type !== 'text_block') {
	throw new WireTestException("Expected type='text_block' from getNewItem($textBlockTypeN), got: " . var_export($item2->type, true));
}
$item2->set($subTextField->name, 'Text Block Headline');
$item2->save();
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 2) {
	throw new WireTestException("Expected 2 items, got: " . $page->get($name)->count());
}
wireTests()->li("getNewItem($textBlockTypeN) by type number: type='text_block', count=2 verified");

// Test: getMatrixTypes() on the field
$types = $field->getMatrixTypes(); // ['banner' => 1, 'text_block' => 2]
if(!isset($types['banner']) || $types['banner'] !== $bannerTypeN) {
	throw new WireTestException("Expected getMatrixTypes()['banner']=$bannerTypeN, got: " . var_export($types, true));
}
if(!isset($types['text_block']) || $types['text_block'] !== $textBlockTypeN) {
	throw new WireTestException("Expected getMatrixTypes()['text_block']=$textBlockTypeN, got: " . var_export($types, true));
}
wireTests()->li("getMatrixTypes() verified: " . json_encode($types));

// Test: getMatrixTypeByName() and getMatrixTypeName()
if($field->getMatrixTypeByName('banner') !== $bannerTypeN) {
	throw new WireTestException("Expected getMatrixTypeByName('banner')=$bannerTypeN");
}
if($field->getMatrixTypeName($textBlockTypeN) !== 'text_block') {
	throw new WireTestException("Expected getMatrixTypeName($textBlockTypeN)='text_block'");
}
wireTests()->li("getMatrixTypeByName() and getMatrixTypeName() verified");

// Test: getMatrixTypeLabel()
$label = $field->getMatrixTypeLabel('banner');
if($label !== 'Banner') {
	throw new WireTestException("Expected getMatrixTypeLabel('banner')='Banner', got: " . var_export($label, true));
}
wireTests()->li("getMatrixTypeLabel('banner') = '$label' verified");

// Test: setMatrixType() — change first item's type
$page->of(false);
$item = $page->get($name)->first();
$item->setMatrixType('text_block');
$item->save();
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->first()->type !== 'text_block') {
	throw new WireTestException("Expected type='text_block' after setMatrixType(), got: " . var_export($page->get($name)->first()->type, true));
}
wireTests()->li("setMatrixType('text_block') on first item verified");

// Test: OF=on excludes unpublished, OF=off includes all
$page->of(false);
$item = $page->get($name)->first();
$item->addStatus(Page::statusUnpublished);
$item->save();

$page->of(true);
$countFormatted = $page->get($name)->count();
$page->of(false);
$countUnformatted = $page->get($name)->count();
if($countFormatted >= $countUnformatted) {
	throw new WireTestException("Expected OF=on to exclude unpublished (got $countFormatted), OF=off includes all (got $countUnformatted)");
}
wireTests()->li("OF=on excludes unpublished: count=$countFormatted; OF=off includes all: count=$countUnformatted");

// Restore
$page->of(false);
$item = $page->get($name)->first();
$item->removeStatus(Page::statusUnpublished);
$item->save();

// Test: remove()
$page = pages()->getFresh($page->id);
$page->of(false);
$toRemove = $page->get($name)->first();
$page->get($name)->remove($toRemove);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 1) {
	throw new WireTestException("Expected 1 item after remove(), got: " . $page->get($name)->count());
}
wireTests()->li("remove() verified, count=1");

// Selectors — clean slate, add known items for matching
$page->of(false);
$items = $page->get($name);
foreach($items as $item) $items->remove($item);
$page->save($name);
$item1 = $page->get($name)->getNewItem('banner');
$item1->set($subTextField->name, 'Selector Test Banner');
$item1->save();
$item2 = $page->get($name)->getNewItem('text_block');
$item2->set($subTextField->name, 'Selector Test Block');
$item2->save();
$page->save($name);

$sub = $subTextField->name;
$selectors = [
	"template=test, $name.count>0",
	"template=test, $name.count=2",
	"template=test, $name.type=banner",
	"template=test, $name.type!=text_block",
	"template=test, $name.$sub*=Selector Test",
	"template=test, $name.$sub^=Selector Test Banner",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}

// count=0 — getFresh to avoid stale cache, then remove all
$page = pages()->getFresh($page->id);
$page->of(false);
$items = $page->get($name);
foreach($items as $item) $items->remove($item);
$page->save($name);
$p = pages()->findOne("template=test, $name.count=0");
if($p->id !== $page->id) throw new WireTestException("Selector failed: $name.count=0");
wireTests()->li("Selector passed: $name.count=0");
