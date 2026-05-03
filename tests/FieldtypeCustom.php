<?php namespace ProcessWire;

/** @var TestPage $page */
/** @var Modules $modules */
/** @var Config $config */

class WireTest_FieldtypeCustom extends WireTest {
	
	protected $name = 'test_custom';
	protected $file = '';
	protected $renamedSubfield = false;
	
	/**
	 * Construct
	 *
	 * @param WireTests $tests
	 *
	 */
	public function __construct(WireTests $tests) {
		parent::__construct($tests);
		$this->file = $this->wire()->config->paths->templates . "custom-fields/$this->name.php";
	}

	/**
	 * Allow this test?
	 *
	 * @return bool
	 *
	 */
	public function allow() {
		$modules = $this->wire()->modules;
		if(!$modules->isInstalled('FieldtypeCustom')) return false;
		$version = $modules->getModuleInfoProperty('FieldtypeCustom', 'version');
		if($version < 5) {
			$this->ok("FieldtypeCustom: version=$version, required version=5");
			return false;
		}
		return true;
	}
	
	/**
	 * Setup test before execute
	 *
	 */
	public function init() {
		$page = $this->getTestPage();
		
		// Create field if it doesn't exist
		/** @var CustomField $field */
		$field = $this->wire()->fields->get($this->name);
		if(!$field) {
			$field = new CustomField();
			$field->type = $this->wire()->modules->get('FieldtypeCustom');
			$field->name = $this->name;
			$field->label = 'Test custom';
			$field->save();
			$this->ok("Created field: $this->name");
		}

		// Add field to test template/fieldgroup
		$fieldgroup = $page->template->fieldgroup;
		if(!$fieldgroup->hasField($field)) {
			$fieldgroup->add($field);
			$fieldgroup->save();
			$this->ok("Added field to fieldgroup: $fieldgroup->name");
		}
		
		$path = dirname($this->file);
		if(!is_dir($path)) $this->wire()->files->mkdir($path);
		if(!is_file($this->file)) {
			$this->wire()->files->copy(__DIR__ . '/FieldtypeCustom/test_custom.php', $this->file); 
		}
	}
	
	/**
	 * Execute/run test
	 *
	 */
	public function execute() {
		// Set values via property access
		$name = $this->name;
		$page = $this->getTestPage();
		$field = $this->wire()->fields->get($name);
		
		$page->of(false);
		$page->$name->first_name = 'Ada';
		$page->$name->age = 36;
		$page->$name->color = 'g';
		$page->save($name);

		// Verify with fresh page
		$fresh = $this->wire()->pages->getFresh($page->id);
		$fresh->of(false);
		
		/** @var CustomWireData $data */
		$data = $fresh->get($name);
		
		if(!($data instanceof CustomWireData)) {
			$this->fail("Expected CustomWireData, got: " . get_class($data));
		}
		
		if($data->first_name !== 'Ada') {
			$this->fail("first_name mismatch: '{$data->first_name}' != 'Ada'");
		}
		
		if((int) $data->age !== 36) {
			$this->fail("age mismatch: '{$data->age}' != 36");
		}
		
		if($data->color !== 'g') {
			$this->fail("color mismatch: '{$data->color}' != 'g'");
		}
		
		$this->ok("Set/get values verified (first_name, age, color)");

		// Test hasValue()
		if(!$data->hasValue('first_name')) {
			$this->fail("hasValue('first_name') returned false");
		}
		$this->ok("hasValue() works");

		// Test getArray()
		$arr = $data->getArray();
		if(!is_array($arr) || !array_key_exists('first_name', $arr)) {
			$this->fail("getArray() did not return expected array");
		}
		$this->ok("getArray() works");

		// Test label() for subfield label
		$label = $data->label('first_name');
		if($label !== 'First name') {
			$this->fail("label('first_name') returned '$label', expected 'First name'");
		}
		$this->ok("label() works: '$label'");

		// Test optionLabel() for select subfield
		$optLabel = $data->optionLabel('color', 'g');
		if($optLabel !== 'Green') {
			$this->fail("optionLabel('color', 'g') returned '$optLabel', expected 'Green'");
		}
		$this->ok("optionLabel() works: '$optLabel'");

		// Test label() shorthand for option label
		$optLabel2 = $data->label('color', 'g');
		if($optLabel2 !== 'Green') {
			$this->fail("label('color', 'g') returned '$optLabel2', expected 'Green'");
		}
		$this->ok("label() option shorthand works");

		// Test setArray()
		$page->of(false);
		$page->$name->setArray(['first_name' => 'Grace', 'age' => 85, 'color' => 'b']);
		$page->save($name);
		$fresh2 = $this->wire()->pages->getFresh($page->id);
		$fresh2->of(false);
		$data2 = $fresh2->get($name);
		if($data2->first_name !== 'Grace') {
			$this->fail("setArray() first_name mismatch: '{$data2->first_name}'");
		}
		if($data2->color !== 'b') {
			$this->fail("setArray() color mismatch: '{$data2->color}'");
		}
		$this->ok("setArray() works");

		// Test set() via array
		$page->of(false);
		$page->set($name, ['first_name' => 'Ada', 'age' => 36, 'color' => 'g']);
		$page->save($name);
		$fresh3 = $this->wire()->pages->getFresh($page->id);
		$fresh3->of(false);
		$data3 = $fresh3->get($name);
		if($data3->first_name !== 'Ada') {
			$this->fail("set() via array mismatch: '{$data3->first_name}'");
		}
		$this->ok("set() via array works");

		// Iterate subfield values
		$iterKeys = [];
		foreach($fresh3->get($name) as $key => $value) {
			$iterKeys[] = $key;
		}
		if(!in_array('first_name', $iterKeys) || !in_array('color', $iterKeys)) {
			$this->fail("foreach iteration missing expected keys: " . implode(', ', $iterKeys));
		}
		$this->ok("foreach iteration works");

		// Test selectors
		$selectors = [
			"template=test, $name.first_name=Ada",
			"template=test, $name.first_name!=\"\"",
			"template=test, $name.first_name%=Ada",
			"template=test, $name.first_name^=Ada",
			"template=test, $name.color=g",
			"template=test, $name.color!=r",
			"template=test, $name.age>30",
			"template=test, $name.age>=36",
			"template=test, $name*=Ada",
		];
		
		foreach($selectors as $selector) {
			$p = $this->wire()->pages->findOne($selector);
			if($p->id === $page->id) {
				$this->ok("Selector passed: $selector");
			} else {
				$this->fail("Selector failed: $selector");
			}
		}

		// Test renameSubfieldData() — migrate stored data from old key to new key
		// At this point first_name='Ada' is saved on $page
		$tools = $field->type->tools();
		$n = $tools->renameSubfieldData($field, 'first_name', 'first_name_new');
		if($n < 1) $this->fail("renameSubfieldData() returned $n, expected >= 1");
		$this->renamedSubfield = true;
		$this->ok("renameSubfieldData() updated $n row(s)");
		
		$freshR = $this->wire()->pages->getFresh($page->id);
		$freshR->of(false);
		$dataR = $freshR->getUnformatted($name)->getArray();
		if(!array_key_exists('first_name_new', $dataR)) {
			$this->fail("New key 'first_name_new' not found after rename; keys: " . implode(', ', array_keys($dataR)));
		}
		if($dataR['first_name_new'] !== 'Ada') {
			$this->fail("Value under new key is '{$dataR['first_name_new']}', expected 'Ada'");
		}
		if(array_key_exists('first_name', $dataR) && $dataR['first_name'] !== '') {
			$this->fail("Old key 'first_name' still has a value after rename: '{$dataR['first_name']}'");
		}
		$this->ok("Data accessible under new key 'first_name_new' after rename");
	}
	
	public function finish() {
		if(!$this->renamedSubfield) return;
		
		$field = $this->wire()->fields->get($this->name);
		$tools = $field->type->tools();
		$page = $this->getTestPage();
		
		// Rename back so subsequent test runs start clean
		$tools->renameSubfieldData($field, 'first_name_new', 'first_name');
		$freshR2 = $this->wire()->pages->getFresh($page->id);
		$freshR2->of(false);
		$dataR2 = $freshR2->getUnformatted($this->name)->getArray();
		if(!array_key_exists('first_name', $dataR2) || $dataR2['first_name'] !== 'Ada') {
			$this->fail("Rename-back failed; first_name='{$dataR2['first_name']}'");
		}
		$this->ok("Rename-back to 'first_name' verified — data intact");
	}
	
	/**
	 * Called when module uninstalled
	 *
	 * This should undo anything that teardown() doesn't undo
	 */
	public function uninstall() {
		$page = $this->getTestPage();
		$fields = $this->wire()->fields;
		$field = $fields->get($this->name);
		$fieldgroup = $page->template->fieldgroup;
		if($fieldgroup->hasField($field)) {
			$fieldgroup->remove($field);
			$fieldgroup->save();
		}
		$fields->delete($field);
		if(is_file($this->file)) {
			$this->wire()->files->unlink($this->file);
		}
	}
}