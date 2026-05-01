<?php namespace ProcessWire;
/** @var Page $page */

$moduleClass = 'TestModule';
$alreadyInstalled = $modules->isInstalled($moduleClass);

// ===== INSTALL LIFECYCLE =====

if(!$alreadyInstalled) {
	check("isInstallable() true when module not yet installed", true, $modules->isInstallable($moduleClass));
	$installed = $modules->install($moduleClass);
	check("install() returns Module instance", true, $installed instanceof Module);
	wireTests()->li("Installed $moduleClass");
}

check("isInstalled() true after install", true, $modules->isInstalled($moduleClass));

// ===== GETTING MODULES =====

$m = $modules->get($moduleClass);
check("get() returns Module instance", true, $m instanceof Module);
check("get() className() matches", $moduleClass, $m->className());

// Property-style access
$m2 = $modules->$moduleClass;
check("property access returns Module instance", true, $m2 instanceof Module);

// get() returns null for a non-existent module
$mNull = $modules->get('NonExistentModuleXyz123');
check("get() returns null for non-existent module", null, $mNull);

// ===== MODULE INFO =====

$info = $modules->getModuleInfo($moduleClass);
check("getModuleInfo() returns array", true, is_array($info));
check("info['name'] matches class", $moduleClass, $info['name']);
check("info['title'] is non-empty string", true, is_string($info['title']) && strlen($info['title']) > 0);
check("info['version'] is set", true, isset($info['version']));
check("info['installed'] is true", true, (bool) $info['installed']);

// formatVersion — version int 1 → "0.0.1"
$versionStr = $modules->formatVersion($info['version']);
check("formatVersion(1) returns '0.0.1'", '0.0.1', $versionStr);

// getModuleInfoProperty — single property lookup
$titleProp = $modules->getModuleInfoProperty($moduleClass, 'title');
check("getModuleInfoProperty() returns title", $info['title'], $titleProp);

// Verbose info
$infoV = $modules->getModuleInfoVerbose($moduleClass);
check("getModuleInfoVerbose() has 'versionStr'", true, isset($infoV['versionStr']));
check("getModuleInfoVerbose() 'versionStr' matches formatVersion()", $versionStr, $infoV['versionStr']);
check("getModuleInfoVerbose() has non-empty 'file'", true, !empty($infoV['file']));
check("getModuleInfoVerbose() 'core' is falsy for non-core module", false, (bool) $infoV['core']);

// getModuleInfo('*') — all installed modules indexed by module ID
$all = $modules->getModuleInfo('*');
check("getModuleInfo('*') returns non-empty array", true, count($all) > 0);
$allFirst = reset($all);
check("getModuleInfo('*') values are arrays with 'name' key", true, is_array($allFirst) && isset($allFirst['name']));

// getModuleInfo('info') — blank info template
$infoTpl = $modules->getModuleInfo('info');
check("getModuleInfo('info') returns array", true, is_array($infoTpl));
check("getModuleInfo('info') template has 'title' key", true, array_key_exists('title', $infoTpl));
check("getModuleInfo('info') template has 'version' key", true, array_key_exists('version', $infoTpl));

// ===== STATUS CHECKS =====

check("isAutoload() false for TestModule", false, (bool) $modules->isAutoload($m));
check("isSingular() false for TestModule", false, (bool) $modules->isSingular($m));
check("isConfigurable() true for TestModule", true, (bool) $modules->isConfigurable($moduleClass));

// ===== FINDING MODULES =====

// findByPrefix — returns [$name => $name] keyed by module name
$byPrefix = $modules->findByPrefix('Inputfield');
check("findByPrefix('Inputfield') returns non-empty array", true, count($byPrefix) > 0);
check("findByPrefix keys start with the given prefix", true, strpos(array_key_first($byPrefix), 'Inputfield') === 0);

$byPrefixTest = $modules->findByPrefix('TestModule');
check("findByPrefix('TestModule') finds TestModule", true, isset($byPrefixTest[$moduleClass]));
$byPrefixLoaded = $modules->findByPrefix('TestModule', true);
check("findByPrefix(load=true) returns Module instances", true, reset($byPrefixLoaded) instanceof Module);

// findByFlag — flagsCli set for modules implementing CliModule with 'cli' in info
$cliModules = $modules->findByFlag(Modules::flagsCli);
check("findByFlag(flagsCli) returns non-empty array", true, count($cliModules) > 0);
check("findByFlag(flagsCli) includes TestModule", true, isset($cliModules[$moduleClass]));

// findByInfo — non-empty property form
$autoloaders = $modules->findByInfo('autoload');
check("findByInfo('autoload') returns non-empty array (core has autoload modules)", true, count($autoloaders) > 0);

// findByInfo — selector string form
$byName = $modules->findByInfo('name=' . $moduleClass);
check("findByInfo(name=TestModule) finds module", true, isset($byName[$moduleClass]));

// findByInfo — array form
$byArray = $modules->findByInfo(['name' => $moduleClass]);
check("findByInfo(['name'=>...]) finds module", true, isset($byArray[$moduleClass]));

// findByInfo — load=true returns Module instances
$byNameLoaded = $modules->findByInfo('name=' . $moduleClass, true);
check("findByInfo(load=true) returns Module instances", true, reset($byNameLoaded) instanceof Module);

// ===== CONFIGURATION =====

$configData = $modules->getConfig($moduleClass);
check("getConfig() returns array", true, is_array($configData));

// Save single config property (key/value form)
$origValue = $configData['testValue'] ?? 'Hello World';
$newValue = 'WireTestsModified_' . mt_rand(1000, 9999);
$modules->saveConfig($moduleClass, 'testValue', $newValue);
check("getConfig(module, key) returns saved value", $newValue, $modules->getConfig($moduleClass, 'testValue'));

// Save entire config data array
$saveData = $modules->getConfig($moduleClass);
$saveData['testValue'] = $origValue;
$modules->saveConfig($moduleClass, $saveData);
check("saveConfig(module, array) restores original value", $origValue, $modules->getConfig($moduleClass, 'testValue'));

// getModuleEditUrl
$editUrl = $modules->getModuleEditUrl($moduleClass);
check("getModuleEditUrl() returns non-empty string", true, strlen($editUrl) > 0);

// ===== HELPER CLASS PROPERTIES =====

check("\$modules->info is ModulesInfo instance", true, $modules->info instanceof ModulesInfo);
check("\$modules->configs is ModulesConfigs instance", true, $modules->configs instanceof ModulesConfigs);
check("\$modules->flags is ModulesFlags instance", true, $modules->flags instanceof ModulesFlags);

// ===== UNINSTALL LIFECYCLE =====

if(!$alreadyInstalled) {
	$modules->uninstall($moduleClass);
	check("isInstalled() false after uninstall", false, $modules->isInstalled($moduleClass));
	check("isInstallable() true after uninstall (file still on disk)", true, $modules->isInstallable($moduleClass));
	wireTests()->li("Uninstalled $moduleClass");
}
