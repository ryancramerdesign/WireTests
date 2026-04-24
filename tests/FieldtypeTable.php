<?php namespace ProcessWire;
/** @var TestPage $page */

$version = $modules->getModuleInfoProperty('FieldtypeTable', 'version');
if($version < 31) {
	wireTests()->li("Skipping test (version=$version, required version=31)");
	return;
}

/** @var FieldtypeTable $fieldtype */
$fieldtype = modules()->get('FieldtypeTable');

$name = 'test_table';
/** @var TableField $field */
$field = fields()->get($name);

if(!$field) {
	$field = new TableField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Table';
	$field->set('maxCols', 3);
	// col1: text
	$field->set('col1name',  'item_name');
	$field->set('col1label', 'Item Name');
	$field->set('col1type',  'text');
	$field->set('col1width', 40);
	// col2: integer
	$field->set('col2name',  'score');
	$field->set('col2label', 'Score');
	$field->set('col2type',  'int1');
	$field->set('col2width', 20);
	// col3: select with options
	$field->set('col3name',    'status');
	$field->set('col3label',   'Status');
	$field->set('col3type',    'select');
	$field->set('col3width',   40);
	$field->set('col3options', "active\ninactive\npending");
	fields()->save($field);
	wireTests()->li("Created field: $field->name (3 columns: text, int, select)");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field->name");
}

$page->of(false);

// Clean slate
$page->get($name)->removeAll();
$page->save($name);

// Test: empty value is a TableRows object
$rows = $page->get($name);
if(!($rows instanceof TableRows)) {
	throw new WireTestException("Expected TableRows, got: " . get_class($rows));
}
if($rows->count() !== 0) {
	throw new WireTestException("Expected empty TableRows, got count: " . $rows->count());
}
wireTests()->li("Empty value is TableRows (count=0) verified");

// Test: add a row via new()
$row = $page->get($name)->new();
$row->item_name = 'Widget';
$row->score = 42;
$row->status = 'active';
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$rows = $page->get($name);
if($rows->count() !== 1) {
	throw new WireTestException("Expected 1 row after add, got: " . $rows->count());
}
$r = $rows->first();
if($r->item_name !== 'Widget' || $r->score !== 42 || $r->status !== 'active') {
	throw new WireTestException("Row values mismatch: " . var_export([$r->item_name, $r->score, $r->status], true));
}
wireTests()->li("Row added via new(), values verified: item_name={$r->item_name}, score={$r->score}, status={$r->status}");

// Test: rowId is accessible on unformatted value
if(!$r->rowId) {
	throw new WireTestException("Expected rowId to be set, got: " . var_export($r->rowId, true));
}
wireTests()->li("rowId accessible: {$r->rowId}");

// Test: new() with pre-populated array
$page->of(false);
$page->get($name)->new(['item_name' => 'Gadget', 'score' => 99, 'status' => 'pending']);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 2) {
	throw new WireTestException("Expected 2 rows, got: " . $page->get($name)->count());
}
wireTests()->li("new() with array shorthand verified, count=2");

// Test: modify an existing row
$page->of(false);
$rows = $page->get($name);
foreach($rows as $row) {
	if($row->item_name === 'Widget') {
		$row->score = 55;
	}
}
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$updated = null;
foreach($page->get($name) as $row) {
	if($row->item_name === 'Widget') { $updated = $row; break; }
}
if(!$updated || $updated->score !== 55) {
	throw new WireTestException("Expected modified score=55, got: " . var_export($updated?->score, true));
}
wireTests()->li("Row modification (score 42→55) verified");

// Test: preventSaving — $page->save() while OF=on throws WireException because the
// page's cached field value is a formatted TableRows with preventSaving=true
$page->of(true);
$rows_formatted = $page->get($name); // gets formatted value, sets preventSaving=true
$threw = false;
try {
	$page->save($name); // sleepValue() checks preventSaving and throws
} catch(WireException $e) {
	$threw = true;
}
$page->of(false);
if(!$threw) {
	throw new WireTestException("Expected WireException when saving page with OF=on (preventSaving), but none was thrown");
}
wireTests()->li("Saving with OF=on triggers preventSaving WireException verified");

// Test: filter via Page::__call() syntax $page->fieldname('selector')
$page->of(false);
$active = $page->$name('status=active'); // $page->test_table('status=active')
if(!($active instanceof TableRows)) {
	throw new WireTestException("Expected TableRows from filter, got: " . get_class($active));
}
if($active->count() !== 1 || $active->first()->item_name !== 'Widget') {
	throw new WireTestException("Expected 1 active row (Widget), got count=" . $active->count());
}
wireTests()->li("Filter syntax \$page->{$name}('status=active') verified: {$active->first()->item_name}");

// Test: $rows->save() on partial (filtered) set
$page->of(false);
$filtered = $page->$name('status=pending');
foreach($filtered as $row) $row->score = 77;
$filtered->save();
$page = pages()->getFresh($page->id);
$page->of(false);
$check = null;
foreach($page->get($name) as $row) {
	if($row->item_name === 'Gadget') { $check = $row; break; }
}
if(!$check || $check->score !== 77) {
	throw new WireTestException("Expected $rows->save() to persist score=77 for Gadget, got: " . var_export($check?->score, true));
}
wireTests()->li("\$rows->save() on partial/filtered set verified (Gadget score=77)");

// Test: remove() a row
$page->of(false);
$rows = $page->get($name);
$toRemove = null;
foreach($rows as $row) {
	if($row->item_name === 'Widget') { $toRemove = $row; break; }
}
if($toRemove) {
	$rows->remove($toRemove);
	$page->save($name);
	$page = pages()->getFresh($page->id);
	$page->of(false);
	if($page->get($name)->count() !== 1) {
		throw new WireTestException("Expected 1 row after remove(), got: " . $page->get($name)->count());
	}
	wireTests()->li("remove() row verified, count=1");
}

// Test: removeAll() leaves empty TableRows
$page->of(false);
$page->get($name)->removeAll();
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 0) {
	throw new WireTestException("Expected 0 rows after removeAll(), got: " . $page->get($name)->count());
}
wireTests()->li("removeAll() verified, count=0");

// Selectors — add known rows for column-based matching
$page = pages()->getFresh($page->id);
$page->of(false);
$page->get($name)->new(['item_name' => 'Widget', 'score' => 42, 'status' => 'active']);
$page->get($name)->new(['item_name' => 'Gadget', 'score' => 10, 'status' => 'inactive']);
$page->save($name);
$selectors = [
	"template=test, $name.item_name*=Widget",
	"template=test, $name.item_name^=Wid",
	"template=test, $name.score>=42",
	"template=test, $name.score>9, $name.score<100",
	"template=test, $name.status=active",
	"template=test, $name.count>0",
	"template=test, $name.count=2",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// @ group prefix — both conditions must match the same row
// Widget row has score=42 (not 10), so @item_name=Widget, @score=42 should match
$p = pages()->findOne("template=test, @$name.item_name=Widget, @$name.score=42");
if($p->id !== $page->id) throw new WireTestException("Selector failed: same-row group match");
wireTests()->li("Selector passed: @$name.item_name=Widget, @$name.score=42 (same-row group)");
// Widget/10 is NOT a valid row combo, so this should NOT match our page
$p = pages()->findOne("template=test, @$name.item_name=Widget, @$name.score=10");
if($p->id === $page->id) throw new WireTestException("Expected no match for cross-row group selector, but page was found");
wireTests()->li("Same-row group correctly excludes cross-row match (@item_name=Widget, @score=10)");
// count=0 — removeAll then verify
$page = pages()->getFresh($page->id);
$page->of(false);
$page->get($name)->removeAll();
$page->save($name);
$p = pages()->findOne("template=test, $name.count=0");
if($p->id !== $page->id) throw new WireTestException("Selector failed: $name.count=0");
wireTests()->li("Selector passed: $name.count=0");
