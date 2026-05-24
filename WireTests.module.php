<?php namespace ProcessWire;

/**
 * ProcessWire Tests
 *
 * Simple framework for running ProcessWire tests from CLI
 *
 * ProcessWire 3.x, Copyright 2026 by Ryan Cramer
 * https://processwire.com
 *
 */
class WireTests extends WireData implements Module, ConfigurableModule, CliModule {

	/**
	 * Current test name
	 *
	 * @var string
	 *
	 */
	protected $testName = '';

	/**
	 * The /test/ page
	 *
	 * @var Page|null
	 *
	 */
	protected $testPage = null;

	/**
	 * Path where tests are located
	 *
	 * @var string
	 *
	 */
	protected $testsPath = '';

	/**
	 * Test timer
	 *
	 * @var string|null
	 *
	 */
	protected $timer = null;

	/**
	 * Number of tests passed
	 *
	 * @var int
	 *
	 */
	protected $passed = 0;

	/**
	 * Number of tests failed
	 *
	 * @var int
	 *
	 */
	protected $failed = 0;

	/**
	 * Are we in CLI mode?
	 *
	 * @var bool
	 *
	 */
	protected $cli = false;

	/**
	 * Output string for http mode
	 *
	 * @var string
	 *
	 */
	protected $out = '';

	/**
	 * Current directory
	 *
	 * @var string
	 *
	 */
	protected $cwd = '';

	/**
	 * Construct
	 *
	 */
	public function __construct() {
		$this->testsPath = __DIR__ . '/tests/';
		parent::__construct();
	}

	/**
	 * Wired to API
	 *
	 */
	public function wired() {
		parent::wired();
		wireTests($this);
		$this->cli = php_sapi_name() === 'cli';
	}

	/**
	 * Execute (for CliModule interface)
	 *
	 * @param array $args
	 *
	 */
	public function executeCli($args) {
		if(empty($args)) {
			$this->line("\nNo test specified");
		} else {
			$this->runTests($args[0]);
		}
	}

	/**
	 * Initialize new test
	 *
	 * @param string $name
	 *
	 */
	public function initTest($name) {
		$this->testName = $name;
		$this->timer = Debug::timer();
		$this->line('');
		$this->line("-----------------------------------");
		$this->line("TEST: $name:");
	}

	/**
	 * Output a line of text
	 *
	 * @param string $line
	 *
	 */
	public function line($line) {
		if($this->cli) {
			echo "$line\n";
		} else {
			$this->out .= "$line\n";
		}
	}

	/**
	 * Output a list item
	 *
	 * @param string $line
	 *
	 */
	public function li($line) {
		if(strpos($line, "\n")) $line = str_replace("\n", "\n  ", $line);
		$this->line("- $line");
	}

	/**
	 * Output an OK item
	 *
	 * @param string $line
	 *
	 */
	public function ok($line) {
		$this->li("OK: $line");
	}

	/**
	 * Output a note
	 *
	 * @param string $note
	 *
	 */
	public function note($note) {
		$this->line($note);
	}

	/**
	 * Indicate test success
	 *
	 * @param string $note Optional note
	 *
	 */
	public function success($note = '') {
		$this->passed++;
		$this->li("👍 SUCCESS $note " . $this->getElapsed());
	}

	/**
	 * Indicate test fail
	 *
	 * @param string $note Optional note
	 *
	 */
	public function fail($note = '') {
		$this->failed++;
		$this->li("👎 FAIL $note " . $this->getElapsed());
	}

	/**
	 * Assert that $expectValue and $actualValue satisfy $operator, output ok() on pass or throw on fail
	 *
	 * Supported operators: ===, !==, ==, !=, <, <=, >, >=
	 * String operators (actual vs. expected): *= (contains), ^= (starts with), $= (ends with)
	 *
	 * @param string $testName
	 * @param mixed $expectValue
	 * @param mixed $actualValue
	 * @param string $operator
	 * @throws WireTestException
	 *
	 */
	public function check($testName, $expectValue, $actualValue, $operator = '===') {
		$message = '';
		if($operator === '===') {
			$ok = $expectValue === $actualValue;
		} else if($operator === '!==') {
			$ok = $expectValue !== $actualValue;
		} else if($operator === '==') {
			$ok = $expectValue == $actualValue;
		} else if($operator === '!=') {
			$ok = $expectValue != $actualValue;
		} else if($operator === '<') {
			$ok = $expectValue < $actualValue;
		} else if($operator === '<=') {
			$ok = $expectValue <= $actualValue;
		} else if($operator === '>') {
			$ok = $expectValue > $actualValue;
		} else if($operator === '>=') {
			$ok = $expectValue >= $actualValue;
		} else if($operator === '*=') {
			$ok = strpos((string) $actualValue, (string) $expectValue) !== false;
			$message = "$testName: " . var_export($actualValue, true) . " does not contain " . var_export($expectValue, true);
		} else if($operator === '^=') {
			$ok = str_starts_with((string) $actualValue, (string) $expectValue);
			$message = "$testName: " . var_export($actualValue, true) . " does not start with " . var_export($expectValue, true);
		} else if($operator === '$=') {
			$ok = str_ends_with((string) $actualValue, (string) $expectValue);
			$message = "$testName: " . var_export($actualValue, true) . " does not end with " . var_export($expectValue, true);
		} else {
			throw new WireTestException("Operator '$operator' not supported");
		}
		if(!$ok) {
			if(!$message) $message = "$testName: Expected: " . var_export($expectValue, true) . ", Received: " . var_export($actualValue, true);
			throw new WireTestException($message);
		}
		$this->ok("$testName");
	}

	/**
	 * Output summary of test(s)
	 *
	 */
	public function summary() {
		$total = $this->passed + $this->failed;
		if($total < 2) return;
		$this->line('');
		$this->line("===================================");
		if($this->failed === 0) {
			$this->line("ALL $total TESTS PASSED 👍");
		} else {
			$this->line("RESULTS: {$this->passed} passed, {$this->failed} failed of $total tests");
		}
		$this->line("===================================");
	}

	/**
	 * Get elapsed time of last test
	 *
	 * @return string
	 *
	 */
	protected function getElapsed() {
		return $this->timer ? '(' . Debug::timer($this->timer) . 's)' : '';
	}

	/**
	 * Get test files corresponding to given name
	 *
	 * @param string $name Name of test, path, path+name, or all
	 * @return array|string[]
	 *
	 */
	protected function getTestFiles($name) {

		$config = $this->wire()->config;
		$slashPos = strpos($name, '/');
		$ext = pathinfo($name, PATHINFO_EXTENSION);

		if($name === 'all') {
			// run all tests in default path
			$path = $this->getTestsPath();
			$testFiles = $this->getTestFilesFromPath($path);

		} else if(strtolower($ext) === 'php') {
			// custom test file specified
			$testFile = $slashPos === 0 ? $name : $config->paths->root . $name;
			$name = basename($name, ".$ext");
			if(!file_exists($testFile)) {
				$this->fail("Test file not found: $testFile");
				return [];
			}
			$testFiles = [$name => $testFile];
			$path = dirname($testFile) . '/';

		} else if($slashPos === 0) {
			// run all in specified absolute path
			$path = rtrim($name, '/') . '/';
			$testFiles = is_dir($path) ? $this->getTestFilesFromPath($path) : [];

		} else if($slashPos) {
			// run all in specified dir relative to install root
			$path = $config->paths->root . trim($name, '/') . '/';
			$testFiles = is_dir($path) ? $this->getTestFilesFromPath($path) : [];

		} else {
			// default path/tests
			$path = $this->getTestsPath();
			$testFiles = $this->getTestFilesFromPath($path);
			if(!isset($testFiles[$name])) {
				$this->fail("Test not found: $name");
				return [];
			}
			$testFiles = [$name => $testFiles[$name]];
		}

		if(is_dir($path)) {
			$this->setTestsPath($path);
		} else {
			$this->fail("Test path not found: $path");
			$testFiles = [];
		}

		return $testFiles;
	}

	/**
	 * Run tests
	 *
	 * @param string $name Test name or omit for 'all'
	 *
	 */
	public function runTests($name = 'all') {
		if($this->cli) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}

		$this->testName = $name;
		$this->cwd = getcwd();
		$testFiles = $this->getTestFiles($name);
		$numTests = count($testFiles);

		if(!count($testFiles)) {
			$this->fail("No tests to run");
			return;
		}

		$path = $this->getTestsPath();
		chdir($path);

		$fuel = $this->wire()->fuel->getArray();
		extract($fuel); // place API variables in scope

		$this->line("Running $numTests test(s) in $path");

		foreach($testFiles as $testName => $testFile) {

			$page = $this->getTestPage(); // get again just in case a test overwrote it
			$page->of(false); // reset output formatting before each test

			$className = $testName;
			if(!$modules->isInstalled($className)) {
				// Also allow tests for core classes (e.g. Sanitizer) that aren't installable modules
				$coreClass = __NAMESPACE__ . "\\$className";
				if(!class_exists($coreClass) && !class_exists($className)) {
					$this->line("Skipping '$className' - not installed");
					continue;
				}
			} else {
				if(!$this->wire()->modules->isInstalled($className)) {
					$this->line("Skipping '$className' - not available");
					continue;
				}
			}

			$testInstance = null;
			$success = false;

			try {
				$wireTestClassName = __NAMESPACE__ . "\\WireTest_$className";
				$this->initTest($className);
				include($testFile);
				if(class_exists($wireTestClassName)) {
					/** @var WireTest $testInstance */
					$testInstance = new $wireTestClassName($this);
					if(!$testInstance->allow()) {
						$this->line("Skipping '$className' - not supported");
						continue;
					}
					$testInstance->init();
					$testInstance->execute();
					$success = true;
				}
				$success = true;

			} catch(WireTestException $e) {
				$this->fail($e->getMessage());

			} catch(\Throwable $t) {
				$this->fail($t->getMessage());

			} finally {
				if($testInstance) {
					try {
						$testInstance->finish();
					} catch(\Throwable $e) {
						$this->fail("Failed to finish/cleanup: " . $e->getMessage());
						$success = false;
					}
				}
			}

			if($success) $this->success();
		}

		$this->summary();

		chdir($this->cwd);
	}

	/**
	 * Get available commands (for CliModule interface)
	 *
	 * @return array
	 *
	 */
	public function getCliCommands() {
		$commands = [ 'all' => 'Run all tests' ];
		foreach(array_keys($this->getTestFilesFromPath()) as $name) {
			$commands[$name] = "Test $name";
		}
		ksort($commands);
		$commands['/path/to/myfile.php'] = "Run custom test in /path/to/myfile.php";
		$commands['dir/to/myfile.php'] = "Run custom test file (relative to installation root)";
		return $commands;
	}

	/**
	 * Get path where tests are located
	 *
	 * @return string
	 *
	 */
	public function getTestsPath() {
		return $this->testsPath;
	}

	/**
	 * Set the path where tests are located
	 *
	 * @param string $path
	 *
	 */
	public function setTestsPath($path) {
		$this->testsPath = $path;
	}

	/**
	 * Get all test files in given path
	 *
	 * @param string $path optional path if something other than the default
	 * @return array
	 *
	 */
	public function getTestFilesFromPath($path = '') {
		if(empty($path)) $path = $this->getTestsPath();
		$dir = new \DirectoryIterator($path);
		$tests = [];
		foreach($dir as $file) {
			if($file->isDir() || $file->isDot()) continue;
			if($file->getExtension() !== 'php') continue;
			$tests[$file->getBasename('.php')] = $file->getPathname();
		}
		return $tests;
	}

	/**
	 * Get all WireTest class instances
	 *
	 * @param string $path
	 * @return array
	 *
	 */
	public function getWireTestInstances($path = '') {
		$testFiles = $this->getTestFilesFromPath($path);
		$instances = [];
		foreach($testFiles as $basename => $testFile) {
			$s = file_get_contents($testFile);
			if(stripos($s, "WireTest_$basename") === false) continue;
			include_once($testFile);
			$class = __NAMESPACE__ . "\\WireTest_$basename";
			/** @var WireTest $instance */
			$instance = new $class($this);
			if(!$instance->allow()) continue;
			$instances[$basename] = $instance;
		}
		return $instances;
	}

	/**
	 * Get (or create) the template file used by the test page
	 *
	 * @param bool $create Create it if it doesn't exist?
	 * @return Template|false
	 *
	 */
	protected function getTestTemplate($create = true) {
		$name = 'test';
		$fields = $this->wire()->fields;
		$templates = $this->wire()->templates;
		$template = $templates->get($name);
		if(!$template) {
			if(!$create) return false;
			$template = $templates->new($name);
			$template->noParents = -1;
			$template->save();
		} else if(!$template->fieldgroup) {
			$template->save();
		}
		$field = $fields->get('title');
		if(!$template->hasField($field)) {
			$template->fieldgroup->add($fields->get('title'));
			$template->fieldgroup->save();
		}
		return $template;
	}

	/**
	 * Get the /test/ page
	 *
	 * @param bool $create Create it if it doesn't exist?
	 * @return Page|false
	 *
	 */
	public function getTestPage($create = true) {
		if($this->testPage) return $this->testPage;
		$pages = $this->wire()->pages;
		$template = $this->getTestTemplate();
		$page = $pages->get("name=test, template=$template->name");
		if($page->id) {
			if($page->isHidden() || $page->isUnpublished()) {
				$page->removeStatus('hidden');
				$page->removeStatus('unpublished');
				$page->save();
			}
			return $page;
		}
		if(!$create) return false;
		if(!$page->id) $page = $pages->new([
			'template' => $template,
			'parent' => '/',
			'status' => 'hidden',
			'name' => 'test',
			'title' => 'ProcessWire Tests'
		]);
		$this->testPage = $page;
		return $page;
	}

	/**
	 * Install module
	 *
	 */
	public function install() {
		$this->getTestPage();
	}

	/**
	 * Uninstall module
	 *
	 */
	public function uninstall() {
		foreach($this->getWireTestInstances() as $wireTest) {
			try {
				$wireTest->uninstall();
			} catch(\Exception $e) {
				$this->error($e->getMessage());
			}
		}
		$page = $this->getTestPage(false);
		if($page && $page->id) {
			$this->wire()->pages->delete($page);
		}
		$template = $this->getTestTemplate(false);
		if($template) {
			$this->wire()->templates->delete($template);
		}
		$fieldgroup = $this->wire()->fieldgroups->get('test');
		if($fieldgroup) {
			$this->wire()->fieldgroups->delete($fieldgroup);
		}
	}

	/**
	 * Module config
	 *
	 * @param InputfieldWrapper $inputfields
	 *
	 */
	public function getModuleConfigInputfields(InputfieldWrapper $inputfields) {
		$input = $this->wire()->input;
		$session = $this->wire()->session;
		$tests = array_keys($this->getTestFilesFromPath());
		$lastTestName = $session->getFor($this, 'testName');

		$f = $inputfields->InputfieldSelect;
		$f->attr('name', '_test_name');
		$f->label = 'Select test to run';
		$f->addOption('all', 'All');
		$f->description = "You can also use from CLI:\n`php index.php test ModuleName`";
		foreach($tests as $test) $f->addOption($test, $test);
		if($lastTestName) $f->val($lastTestName);
		$inputfields->add($f);

		$testName = $input->post('_test_name');
		$results = $session->getFor($this, 'results');

		if($testName && ($testName === 'all' || in_array($testName, $tests, true))) {
			$this->runTests($testName);
			$session->setFor($this, 'results', $this->out);
			$session->setFor($this, 'testName', $testName);
		} else if($results) {
			$f = $inputfields->InputfieldMarkup;
			$f->attr('name', '_test_results');
			$f->label = 'Test results';
			$f->val('<pre>' . htmlspecialchars(ltrim($results, "\n-")) . '</pre>');
			$inputfields->add($f);
		}
	}
}

/**
 * Get the WireTests module instance
 *
 * @param WireTests|null $wireTests Specify to set WireTests instance
 * @return WireTests
 *
 */
function wireTests(?WireTests $wireTests = null) {
	static $module = null;
	if($wireTests !== null) $module = $wireTests;
	if(!$module) $module = wire()->modules->get('WireTests');
	return $module;
}

/**
 * Assert that $expectValue and $actualValue satisfy $operator, output ok() on pass or throw on fail
 *
 * Supported operators: ===, !==, ==, !=, <, <=, >, >=
 * String operators (actual vs. expected): *= (contains), ^= (starts with), $= (ends with)
 *
 * @param string $testName
 * @param mixed $expectValue
 * @param mixed $actualValue
 * @param string $operator
 * @throws WireTestException
 *
 */
function check($testName, $expectValue, $actualValue, $operator = '===') {
	wireTests()->check($testName, $expectValue, $actualValue, $operator);
}

/**
 * Exception thrown by tests on failed test
 *
 */
class WireTestException extends WireException { }

include(__DIR__ . '/WireTest.php');
