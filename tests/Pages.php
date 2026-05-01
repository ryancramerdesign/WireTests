<?php namespace ProcessWire;
/** @var Page $page */

// ===== SETUP =====
// Create a child template for test pages (with no restrictions)
$childTemplateName = 'pages-test-child';
$childTemplate = $templates->get($childTemplateName);
$createdTemplate = false;
if(!$childTemplate) {
	$childTemplate = $templates->new($childTemplateName);
	$childTemplate->save();
	$createdTemplate = true;
	wireTests()->li("Created template: $childTemplateName");
}
// Ensure the template has a valid fieldgroup (saving creates one if missing)
if(!$childTemplate->fieldgroup) $childTemplate->save();
// Always ensure title field is in the fieldgroup (idempotent)
$titleField = $fields->get('title');
if($titleField && !$childTemplate->hasField($titleField)) {
	$childTemplate->fieldgroup->add($titleField);
	$childTemplate->fieldgroup->save();
}

// Delete any leftover child pages from previous runs
foreach($pages->find("template=$childTemplateName, parent={$page->id}, include=all") as $leftover) {
	$pages->delete($leftover, true);
}

// ===== FINDING PAGES =====

// get() by ID
check("get() by ID returns correct page", $page->id, $pages->get($page->id)->id);

// get() by path
check("get() by path returns correct page", $page->id, $pages->get($page->path)->id);

// get() by selector
$bySelector = $pages->get("name={$page->name}, template={$page->template->name}");
check("get() by selector returns correct page", $page->id, $bySelector->id);

// get() returns NullPage for no match
$noMatch = $pages->get("name=nonexistent-page-xyz-12345");
check("get() returns NullPage when not found", 0, $noMatch->id);
check("get() not-found result is NullPage instance", true, $noMatch instanceof NullPage);

// findOne() — applies access/status filtering
$found = $pages->findOne("id={$page->id}");
check("findOne() returns correct page", $page->id, $found->id);

$notFound = $pages->findOne("name=nonexistent-page-xyz-12345");
check("findOne() returns NullPage when not found", 0, $notFound->id);

// find() — returns PageArray
$results = $pages->find("id={$page->id}");
check("find() returns PageArray", true, $results instanceof PageArray);
check("find() PageArray contains the test page", true, $results->has($page));

// count() — counts without loading pages
$n = $pages->count("id={$page->id}");
check("count() returns int", true, is_int($n));
check("count() finds 1 for known page ID", 1, $n);
check("count() returns 0 for no match", 0, $pages->count("name=nonexistent-page-xyz-12345"));

// has() — returns first matching page ID or 0
check("has() returns page ID when found", $page->id, $pages->has("id={$page->id}"));
check("has() returns 0 when not found", 0, $pages->has("name=nonexistent-page-xyz-12345"));

// findIDs() — IDs only, faster than find()
$ids = $pages->findIDs("id={$page->id}");
check("findIDs() returns array", true, is_array($ids));
check("findIDs() contains page ID", true, in_array($page->id, $ids));

// findIDs() verbose mode — returns id/parent_id/templates_id per result
$idsVerbose = $pages->findIDs("id={$page->id}", true);
check("findIDs(verbose=true) returns nested array", true, is_array($idsVerbose));
$firstVerbose = reset($idsVerbose);
check("findIDs(verbose=true) has 'id' key", true, isset($firstVerbose['id']));
check("findIDs(verbose=true) has 'templates_id' key", true, isset($firstVerbose['templates_id']));

// getRaw() — raw DB values, no Page objects
// Note: passing int ID is buggy (PagesRaw::findIDs early-return returns wrong format); use string selector
// Passing a string field returns a scalar; passing an array of fields returns an array
$rawScalar = $pages->getRaw("id={$page->id}", 'name');
check("getRaw(selector, 'field') returns scalar value", $page->name, $rawScalar);

$rawArray = $pages->getRaw("id={$page->id}", ['name']);
check("getRaw(selector, ['field']) returns array", true, is_array($rawArray));
check("getRaw() array has 'name' key", true, isset($rawArray['name']));
check("getRaw() 'name' matches page name", $page->name, $rawArray['name']);

// findRaw() — raw DB values for multiple pages, indexed by page ID
$rawResults = $pages->findRaw("id={$page->id}", ['name']);
check("findRaw() returns array indexed by page ID", true, isset($rawResults[$page->id]));
check("findRaw() value has 'name' key", true, isset($rawResults[$page->id]['name']));
check("findRaw() 'name' matches page name", $page->name, $rawResults[$page->id]['name']);

// getFresh() — non-cached copy from DB
$fresh = $pages->getFresh($page->id);
check("getFresh(id) returns correct page", $page->id, $fresh->id);
$fresh2 = $pages->getFresh($page);
check("getFresh(Page) returns correct page", $page->id, $fresh2->id);

// ===== CREATING INSTANCES =====

// newPage() — unsaved, id=0
$unsaved = $pages->newPage();
check("newPage() returns Page instance", true, $unsaved instanceof Page);
check("newPage() has id=0 (unsaved)", 0, $unsaved->id);

// newPage() with template/parent pre-set
$unsavedWithTemplate = $pages->newPage(['template' => $childTemplateName, 'parent' => $page]);
check("newPage(['template']) returns Page with template set", $childTemplateName, $unsavedWithTemplate->template->name);
check("newPage(['parent']) returns Page with parent set", $page->id, $unsavedWithTemplate->parent->id);

// newPageArray() — empty PageArray
$pa = $pages->newPageArray();
check("newPageArray() returns PageArray", true, $pa instanceof PageArray);
check("newPageArray() is empty", 0, $pa->count());

// newNullPage() — shared NullPage instance
$null1 = $pages->newNullPage();
$null2 = $pages->newNullPage();
check("newNullPage() returns NullPage", true, $null1 instanceof NullPage);
check("newNullPage() id=0", 0, $null1->id);
check("newNullPage() returns same shared instance", true, $null1 === $null2);
check("newNullPage(true) returns a fresh instance", true, $pages->newNullPage(true) !== $null1);

// ===== CREATING PAGES =====

// add() — creates and saves to DB immediately
$child1 = $pages->add($childTemplateName, $page, [
	'name' => 'pages-test-child-a',
	'title' => 'Pages Test Child A',
	'status' => Page::statusHidden,
]);
check("add() returns Page with id > 0", true, $child1->id > 0);
check("add() page has correct template", $childTemplateName, $child1->template->name);
check("add() page has correct parent", $page->id, $child1->parent->id);
check("add() page has correct name", 'pages-test-child-a', $child1->name);

// Verify persisted to DB
check("add() page persists to DB", $child1->id, $pages->getFresh($child1->id)->id);

// new() with array — saves to DB immediately
$child2 = $pages->new([
	'template' => $childTemplateName,
	'parent' => $page,
	'name' => 'pages-test-child-b',
	'title' => 'Pages Test Child B',
	'status' => Page::statusHidden,
]);
check("new(array) returns saved Page", true, $child2->id > 0);
check("new(array) page has correct name", 'pages-test-child-b', $child2->name);

// ===== SAVING PAGES =====

// save() — save all changed fields
$child1->of(false);
$child1->title = 'Pages Test Child A — Updated';
$pages->save($child1);
check("save() persists title change", 'Pages Test Child A — Updated', $pages->getFresh($child1->id)->title);

// saveField() — save a single field
$child1->of(false);
$child1->title = 'Pages Test Child A — saveField';
$pages->saveField($child1, 'title');
check("saveField() persists single field", 'Pages Test Child A — saveField', $pages->getFresh($child1->id)->title);

// saveFields() — save multiple fields at once (CSV string form)
$child1->of(false);
$child1->title = 'Pages Test Child A — saveFields';
$pages->saveFields($child1, 'title');
check("saveFields() persists via CSV string", 'Pages Test Child A — saveFields', $pages->getFresh($child1->id)->title);

// saveFields() — array form
$child1->of(false);
$child1->title = 'Pages Test Child A — saveFields array';
$pages->saveFields($child1, ['title']);
check("saveFields() persists via array", 'Pages Test Child A — saveFields array', $pages->getFresh($child1->id)->title);

// touch() — update modified time (>= since same-second possible)
$beforeModified = $pages->getFresh($child1->id)->modified;
$pages->touch($child1);
check("touch() updated or preserved modified timestamp", true, $pages->getFresh($child1->id)->modified >= $beforeModified);

// ===== CLONE =====

$cloned = $pages->clone($child1);
check("clone() returns a Page with new id", true, $cloned->id > 0 && $cloned->id !== $child1->id);
check("clone() page has same parent", $child1->parent->id, $cloned->parent->id);
check("clone() page has same template", $child1->template->name, $cloned->template->name);

// ===== CACHE =====

// uncache() — remove from memory cache (no error expected)
$pages->uncache($child1);
$pages->uncache([$child1->id, $child2->id]);

// uncacheAll() — clear entire cache (no error expected)
$pages->uncacheAll();

// Pages still loadable after cache clear
check("get() still works after uncacheAll()", $child1->id, $pages->get($child1->id)->id);

// ===== SORT =====

// sort() — set position among siblings
$pages->sort($child1, 0);
check("sort() does not throw", true, true);

// insertAfter() — move child1 to after child2
$pages->insertAfter($child1, $child2);
check("insertAfter() does not throw", true, true);

// insertBefore() — move child1 to before child2
$pages->insertBefore($child1, $child2);
check("insertBefore() does not throw", true, true);

// ===== TRASH AND RESTORE =====

// Ensure non-negative sort so the trash name encodes a valid parseable sort value.
// clone() can produce sort=-1; $cloned->sortPrevious must also be null for trash()
// to use sort (not sortPrevious) when building the restorable name.
$pages->sort($cloned, 5);
$cloned->set('sortPrevious', null);

// trash() — move to trash
$pages->trash($cloned);
$freshCloned = $pages->getFresh($cloned->id);
check("trash() page is now in trash", true, $freshCloned->isTrash());

// isTrash false before trash (verify child1 not trashed)
check("isTrash() false for non-trashed page", false, $child1->isTrash());

// restore() — recover from trash
$pages->restore($cloned);
$pages->uncacheAll();
$freshCloned2 = $pages->getFresh($cloned->id);
check("restore() page is no longer in trash", false, $freshCloned2->isTrash());

// ===== DELETE =====

$clonedId = $cloned->id;
$pages->delete($cloned);
check("delete() page no longer findable by ID", 0, $pages->get($clonedId)->id);

// findMany() — chunked iteration (basic smoke test)
$manyCount = 0;
foreach($pages->findMany("template={$childTemplateName}, parent={$page->id}, include=all") as $p) {
	$manyCount++;
}
check("findMany() iterates pages without error", true, $manyCount >= 2);

// ===== CLEANUP =====

$pages->delete($child1);
$pages->delete($child2);
check("cleanup: child1 deleted", 0, $pages->get($child1->id)->id);
check("cleanup: child2 deleted", 0, $pages->get($child2->id)->id);

if($createdTemplate) {
	$templates->delete($childTemplate);
	$fieldgroups->delete($fieldgroups->get($childTemplateName));
	wireTests()->li("Deleted template: $childTemplateName");
}
