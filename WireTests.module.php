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
	 * Command line ready
	 * 
	 * @param array $args
	 * 
	public function cliReady($args) {
		if(count($args)) {
		} else {
			$this->line("ProcessWireTests Usage:"); 
			foreach($this->getTestFiles() as $name => $file) {
				$this->li("php index.php test $name"); 
			}
		}
	}
	 */
	
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
		return '(' . Debug::timer($this->timer) . 's)';
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
		$runAll = ($name === 'all'); // capture before test files can overwrite $name
		$path = $this->getTestsPath();

		chdir($path);

		$fuel = $this->wire()->fuel->getArray();
		extract($fuel); // place API variables in scope

		foreach($this->getTestFiles($path) as $testName => $testFile) {

			if(!$runAll && $name !== $testName) continue;
		
			$page = $this->getTestPage(); // get again just in case a test overwrote it
			$page->of(false); // reset output formatting before each test
		
			$className = $testName;
			if(!$modules->isInstalled($className)) {
				$this->line("Skipping '$className' - not installed");
				continue;
			}
			
			$module = $this->wire()->modules->getModule($className);
			if(!$module) {
				$this->line("Skipping '$className' - not available");
				continue;
			}
			
			try {
				$this->initTest($className);
				include($testFile);
				$this->success();
				
			} catch(WireTestException $e) {
				$this->fail($e->getMessage());
				
			} catch(\Throwable $t) {
				$this->fail($t->getMessage());
			}
			
			if(!$runAll) break;
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
		foreach(array_keys($this->getTestFiles()) as $name) {
			$commands[$name] = "Test $name module";
		}
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
	public function getTestFiles($path = '') {
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
		if($page->id) return $page;
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
		$tests = array_keys($this->getTestFiles());
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
 * Exception thrown by tests on failed test
 *
 */
class WireTestException extends WireException { }

