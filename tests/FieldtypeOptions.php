<?php namespace ProcessWire;
/** @var TestPage $page */

// Single-select field
$name = 'test_options';
$fieldtype = modules()->get('FieldtypeOptions');

/** @var OptionsField $field */
$field = fields()->get($name);
if(!$field) {
	$field = new OptionsField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Options';
	$field->inputfieldClass = 'InputfieldSelect'; // single-select
	$field->save();
	wireTests()->li("Created field: $field->name");
}

// Multi-select field
$nameMulti = 'test_options_multi';
/** @var OptionsField $fieldMulti */
$fieldMulti = fields()->get($nameMulti);
if(!$fieldMulti) {
	$fieldMulti = new OptionsField();
	$fieldMulti->name = $nameMulti;
	$fieldMulti->type = $fieldtype;
	$fieldMulti->label = 'Test Options Multi';
	$fieldMulti->inputfieldClass = 'InputfieldCheckboxes'; // multi-select
	$fieldMulti->save();
	wireTests()->li("Created field: $fieldMulti->name");
}

$fieldgroup = $page->template->fieldgroup;
foreach([$field, $fieldMulti] as $f) {
	if(!$fieldgroup->hasField($f)) {
		$fieldgroup->add($f);
		$fieldgroup->save();
		wireTests()->li("Added field to fieldgroup: $f->name");
	}
}

// Set up options on the single-select field (only if not already set)
$allOptions = $field->getOptions();
if($allOptions->count() !== 3) {
	$field->setOptionsString("Red\n#00ff00|Green\nBlue");
	$field->save();
	$allOptions = $field->getOptions();
}
wireTests()->li("Options defined: " . $field->getOptionsString());

// Verify 3 options were created
if($allOptions->count() !== 3) {
	throw new WireTestException("Expected 3 options, got: " . $allOptions->count());
}
wireTests()->li("Option count (3) verified");

// Get the option IDs for use below
$redOption = $allOptions->getByTitle('Red');
$greenOption = $allOptions->getByTitle('Green');
$blueOption = $allOptions->getByTitle('Blue');

if(!$redOption || !$greenOption || !$blueOption) {
	throw new WireTestException("Could not find expected options by title");
}

// Test: set single option by ID
$page->set($name, $redOption->id);
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if(!$val->count() || $val->first()->title !== 'Red') {
	throw new WireTestException("Expected Red selected by ID, got: " . var_export($val->first(), true));
}
wireTests()->li("Set by ID verified: selected '{$val->first()->title}'");

// Test: set single option by title
$page->set($name, 'Green');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if(!$val->count() || $val->first()->title !== 'Green') {
	throw new WireTestException("Expected Green selected by title, got: " . var_export((string) $val, true));
}
wireTests()->li("Set by title verified: selected '{$val->first()->title}'");

// Test: set single option by value string
$page->set($name, '#00ff00');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if(!$val->count() || $val->first()->title !== 'Green') {
	throw new WireTestException("Expected Green selected by value '#00ff00', got: " . var_export((string) $val, true));
}
wireTests()->li("Set by value ('#00ff00') verified: selected '{$val->first()->title}'");

// Test: clear selection
$page->set($name, '');
$page->save($name);
$page = pages()->getFresh($page->id);
$val = $page->get($name);
if($val->count() !== 0) {
	throw new WireTestException("Expected empty selection, got count: " . $val->count());
}
wireTests()->li("Clear selection (empty) verified");

// Test: value type is always SelectableOptionArray
if(!($val instanceof SelectableOptionArray)) {
	throw new WireTestException("Expected SelectableOptionArray, got: " . get_class($val));
}
wireTests()->li("Value type is SelectableOptionArray verified");

// Set up options on the multi-select field (only if not already set)
$multiOptions = $fieldMulti->getOptions();
if($multiOptions->count() !== 3) {
	$fieldMulti->setOptionsString("Red\n#00ff00|Green\nBlue");
	$fieldMulti->save();
	$multiOptions = $fieldMulti->getOptions();
}
$redM = $multiOptions->getByTitle('Red');
$greenM = $multiOptions->getByTitle('Green');
$blueM = $multiOptions->getByTitle('Blue');

// Test: set multiple options by array of IDs
$page->set($nameMulti, [$redM->id, $blueM->id]);
$page->save($nameMulti);
$page = pages()->getFresh($page->id);
$val = $page->get($nameMulti);
if($val->count() !== 2) {
	throw new WireTestException("Expected 2 selected options, got: " . $val->count());
}
if(!$val->hasTitle('Red') || !$val->hasTitle('Blue')) {
	throw new WireTestException("Expected Red and Blue selected, got: " . $val->implode('|', 'title'));
}
wireTests()->li("Multi-select by array of IDs verified: " . $val->implode(', ', 'title'));

// Test: set multiple options by pipe-separated IDs
$page->set($nameMulti, $redM->id . '|' . $greenM->id . '|' . $blueM->id);
$page->save($nameMulti);
$page = pages()->getFresh($page->id);
$val = $page->get($nameMulti);
if($val->count() !== 3) {
	throw new WireTestException("Expected 3 selected options, got: " . $val->count());
}
wireTests()->li("Multi-select by pipe-separated IDs verified: " . $val->implode(', ', 'title'));

// Test: addByTitle / removeByTitle
$page->of(false);
$page->set($nameMulti, [$redM->id]);
$page->save($nameMulti);
$page = pages()->getFresh($page->id);
$page->of(false);
$page->get($nameMulti)->addByTitle('Blue');
$page->save($nameMulti);
$page = pages()->getFresh($page->id);
$val = $page->get($nameMulti);
if(!$val->hasTitle('Blue')) {
	throw new WireTestException("Expected Blue added by title, got: " . $val->implode('|', 'title'));
}
wireTests()->li("addByTitle('Blue') verified");

$page->of(false);
$page->get($nameMulti)->removeByTitle('Blue');
$page->save($nameMulti);
$page = pages()->getFresh($page->id);
$val = $page->get($nameMulti);
if($val->hasTitle('Blue')) {
	throw new WireTestException("Expected Blue removed by title, got: " . $val->implode('|', 'title'));
}
wireTests()->li("removeByTitle('Blue') verified");

// Test: cast to string returns pipe-separated IDs
$page->set($nameMulti, [$redM->id, $greenM->id]);
$page->save($nameMulti);
$page = pages()->getFresh($page->id);
$str = (string) $page->get($nameMulti);
$ids = explode('|', $str);
if(count($ids) !== 2) {
	throw new WireTestException("Expected pipe-separated string of 2 IDs, got: " . var_export($str, true));
}
wireTests()->li("Cast to string returns pipe-separated IDs: '$str'");

// Selectors — single-select: set Green (has title, value, and known ID)
$page->set($name, 'Green');
$page->save($name);
$greenId = $field->getOptions()->getByTitle('Green')->id;
$selectors = [
	"template=test, $name=Green",
	"template=test, $name.title=Green",
	"template=test, $name.value=\"#00ff00\"",
	"template=test, $name.id=$greenId",
	"template=test, $name.count>0",
	"template=test, $name!=Red",
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

// Selectors — multi-select: set Red+Blue
$redId = $fieldMulti->getOptions()->getByTitle('Red')->id;
$blueId = $fieldMulti->getOptions()->getByTitle('Blue')->id;
$page->set($nameMulti, [$redId, $blueId]);
$page->save($nameMulti);
$selectors = [
	"template=test, $nameMulti=Red",
	"template=test, $nameMulti=Blue",
	"template=test, $nameMulti=Red|Blue|Green",
	"template=test, $nameMulti.count>1",
	"template=test, $nameMulti!=Green",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
