<?php namespace ProcessWire;
/** @var TestPage $page */

$version = $modules->getModuleInfoProperty('FieldtypeCombo', 'version');
if($version < 18) {
	wireTests()->li("Skipping test (version=$version, required version=18)");
	return;
}

$name = 'test_combo';

/** @var ComboField $field */
$field = fields()->get($name);

if(!$field) {
	// Use newField() (wires without saving) so we can add all subfields before the first save
	/** @var ComboField $field */
	$field = fields()->newField('FieldtypeCombo', $name, 'Test combo');

	$field->addSubfield($field->newSubfield('Text', 'city', 'City'));
	$field->addSubfield($field->newSubfield('Integer', 'zip', 'ZIP'));

	$sf = $field->newSubfield('Select', 'state', 'State');
	$sf->options = "ga=Georgia\nfl=Florida\ntx=Texas";
	$field->addSubfield($sf);

	fields()->save($field);
	wireTests()->li("Created field: $name (city, zip, state)");
}

// Add to test template
$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name");
}

// Set values (OF=off required for saving)
$page->of(false);
$page->$name->city  = 'Atlanta';
$page->$name->zip   = 30301;
$page->$name->state = 'ga';
$page->save($name);

// Verify with fresh page (OF=off)
$fresh = pages()->getFresh($page->id);
$fresh->of(false);
/** @var ComboValue $data */
$data = $fresh->get($name);

if(!($data instanceof ComboValue)) {
	throw new WireTestException("Expected ComboValue (OF=off), got: " . get_class($data));
}
if($data->city !== 'Atlanta') {
	throw new WireTestException("city: '{$data->city}' != 'Atlanta'");
}
if((int) $data->zip !== 30301) {
	throw new WireTestException("zip: '{$data->zip}' != 30301");
}
if($data->state !== 'ga') {
	throw new WireTestException("state (OF=off): '{$data->state}' != 'ga'");
}
wireTests()->li("Set/get values verified (city, zip, state, OF=off)");

// Test getUnformatted() always returns ComboValue regardless of OF state
$fresh->of(true);
$dataRaw = $fresh->getUnformatted($name);
if(!($dataRaw instanceof ComboValue)) {
	throw new WireTestException("getUnformatted() expected ComboValue, got: " . get_class($dataRaw));
}
if($dataRaw->state !== 'ga') {
	throw new WireTestException("getUnformatted() state: '{$dataRaw->state}' != 'ga'");
}
wireTests()->li("getUnformatted() returns ComboValue with raw value regardless of OF state");

// Test OF=on: ComboValueFormatted, select returns ComboSelectedValue
$dataFmt = $fresh->get($name);
if(!($dataFmt instanceof ComboValueFormatted)) {
	throw new WireTestException("Expected ComboValueFormatted (OF=on), got: " . get_class($dataFmt));
}
$stateVal = $dataFmt->state;
if(!($stateVal instanceof ComboSelectedValue)) {
	throw new WireTestException("Expected ComboSelectedValue for state (OF=on), got: " . get_class($stateVal));
}
if((string) $stateVal !== 'Georgia') {
	throw new WireTestException("ComboSelectedValue cast to string: '$stateVal', expected 'Georgia'");
}
if($stateVal->value !== 'ga') {
	throw new WireTestException("ComboSelectedValue->value: '{$stateVal->value}', expected 'ga'");
}
if($stateVal->label !== 'Georgia') {
	throw new WireTestException("ComboSelectedValue->label: '{$stateVal->label}', expected 'Georgia'");
}
wireTests()->li("ComboSelectedValue (OF=on): value='{$stateVal->value}', label='{$stateVal->label}'");

// Test ComboValue utility methods (using OF=off data)
$fresh->of(false);
$data = $fresh->get($name);

$subfields = $data->getSubfields();
if(!is_array($subfields) || !isset($subfields['city'])) {
	throw new WireTestException("getSubfields() did not return expected array");
}
wireTests()->li("getSubfields() works (" . count($subfields) . " subfields)");

$cityLabel = $data->getLabel('city');
if($cityLabel !== 'City') {
	throw new WireTestException("getLabel('city'): '$cityLabel' != 'City'");
}
wireTests()->li("getLabel() works: '$cityLabel'");

$sfSettings = $data->getSubfieldSettings('city');
if(!($sfSettings instanceof ComboSubfield)) {
	throw new WireTestException("getSubfieldSettings('city') did not return ComboSubfield");
}
if($sfSettings->type !== 'Text') {
	throw new WireTestException("getSubfieldSettings type: '{$sfSettings->type}' != 'Text'");
}
wireTests()->li("getSubfieldSettings() works (type={$sfSettings->type})");

// Test field config API
/** @var ComboField $field */
$field = fields()->get($name);

if($field->qty < 3) {
	throw new WireTestException("field->qty={$field->qty}, expected >= 3");
}
wireTests()->li("field->qty={$field->qty}");

$citySubfield = $field->getSubfield('city');
if(!($citySubfield instanceof ComboSubfield)) {
	throw new WireTestException("getSubfield('city') did not return ComboSubfield");
}
if($citySubfield->label !== 'City') {
	throw new WireTestException("getSubfield->label: '{$citySubfield->label}' != 'City'");
}
wireTests()->li("field->getSubfield('city') works");

$colName = $field->colName('city');
if(empty($colName)) {
	throw new WireTestException("colName('city') returned empty string");
}
wireTests()->li("colName('city')='$colName'");

// Test addSubfield() / deleteSubfield()
$field->addSubfield($field->newSubfield('Text', 'country', 'Country'));
$field->save();
$fresh4 = pages()->getFresh($page->id);
$fresh4->of(false);
if(!isset($fresh4->get($name)->getSubfields()['country'])) {
	throw new WireTestException("country subfield not found after addSubfield()");
}
wireTests()->li("addSubfield() works");

$field->deleteSubfield('country');
$field->save();
wireTests()->li("deleteSubfield() works");

// Test renameSubfield() — save a value first, rename, verify data carries over
$page->of(false);
$page->$name->city = 'Savannah';
$page->save($name);

$field->renameSubfield('city', 'city_name');
$field->save();

$fresh5 = pages()->getFresh($page->id);
$fresh5->of(false);
$data5 = $fresh5->get($name);
if($data5->city_name !== 'Savannah') {
	throw new WireTestException("renameSubfield() failed: city_name='{$data5->city_name}', expected 'Savannah'");
}
wireTests()->li("renameSubfield() works: city→city_name, value preserved");

// Rename back so subsequent runs start clean
$field->renameSubfield('city_name', 'city');
$field->save();
$fresh6 = pages()->getFresh($page->id);
$fresh6->of(false);
if($fresh6->get($name)->city !== 'Savannah') {
	throw new WireTestException("Rename-back failed: city='{$fresh6->get($name)->city}'");
}
wireTests()->li("Rename-back to 'city' verified — data intact");

// Test selectors
$page->of(false);
$page->$name->city = 'Atlanta';
$page->save($name);

$selectors = [
	"template=test, $name.city=Atlanta",
	"template=test, $name.city!=''",
	"template=test, $name.city*=Atlanta",
	"template=test, $name.city^=Atl",
	"template=test, $name.zip=30301",
	"template=test, $name.zip>30000",
	"template=test, $name.state=ga",
	"template=test, $name.state!=fl",
];

foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id === $page->id) {
		wireTests()->li("Selector passed: $selector");
	} else {
		throw new WireTestException("Selector failed: $selector");
	}
}
