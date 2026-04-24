<?php namespace ProcessWire;
/** @var TestPage $page */

$name = 'headline';
$field = fields()->get($name);

if(!$field) {
	// create new field if it does not already exist
	// use correct [Type]Field class, when available
	$field = new TextField();
	$field->name = $name;
	$field->type = modules()->get('FieldtypeText');
	$field->label = 'Headline';
	// other settings for field can be found in its [Type]Field.php file, for example:
	$field->textformatters = [ 'TextformatterEntities' ];
	// save the field
	$field->save();
	wireTests()->li("Created field: $field->name"); // Optional
}

// add field to template/fieldgroup
$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldgroup->name"); // Optional
}

// set value to $page
$value = 'Hello World ' . mt_rand();
wireTests()->li("Setting value to: " . htmlspecialchars($value)); // Optional
$page->set($name, $value); 
$page->save($name);

// verify the value saved by getting a fresh copy from the DB
$page = pages()->getFresh($page->id);
$freshValue = $page->get($name);

if($freshValue !== $value) {
	// throw WireTestException if test fails
	throw new WireTestException("Values don't match: '$freshValue' != '$value'");
}

// test finding from API and selectors
$selectors = [
	"$name='$value'", 
	"$name^=Hello", 
	"$name%^=Hello",
	"$name%=World", 
	"$name*=World", 
	"$name|title~=World", 
	"$name~|=Foo Bar World",
	"$name~|*=Wor War Woo",
	"template=test, $name!=Foobar", 
];

foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id === $page->id) {
		wireTests()->li("Selector passed: $selector"); 
	} else {
		throw new WireTestException("Selector failed: $selector ($p->id != $page->id)"); 
	}

	/*
	// test NOT selector
	$selector = "template=test, !$selector";
	$p2 = pages()->find($selector);
	if($p2->has($page)) {
		throw new WireTestException("NOT selector failed: $selector");
	} else {
		wireTests()->li("NOT selector passed: $selector");
	}
	*/
}

// test assumed to be successful if we reach this point