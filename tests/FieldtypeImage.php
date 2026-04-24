<?php namespace ProcessWire;
/** @var TestPage $page */

$fieldtype = modules()->get('FieldtypeImage');
$testsDir = __DIR__ . '/';

$imgJpg  = $testsDir . 'images/test1.jpg';
$imgPng  = $testsDir . 'images/test2.png';
$imgGif  = $testsDir . 'images/GIF-google.gif';
$imgInvalid = $testsDir . 'images/invalid-image.jpg';
$imgJpgName = basename($imgJpg);

foreach([$imgJpg, $imgPng, $imgGif, $imgInvalid] as $f) {
	if(!file_exists($f)) throw new WireTestException("Test image not found: $f");
}

$name = 'test_image';
/** @var ImageField $field */
$field = fields()->get($name);
if(!$field) {
	$field = new ImageField();
	$field->name = $name;
	$field->type = $fieldtype;
	$field->label = 'Test Image';
	$field->extensions = 'jpg jpeg png gif';
	$field->maxFiles = 0; // no limit
	$field->outputFormat = FieldtypeFile::outputFormatArray; // always Pageimages
	$field->save();
	wireTests()->li("Created field: $field->name");
}

$fieldgroup = $page->template->fieldgroup;
if(!$fieldgroup->hasField($field)) {
	$fieldgroup->add($field);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $field->name");
}

$page->of(false);

// Clean slate
$page->get($name)->deleteAll();
$page->save($name);

// Test: add a JPG
$page->get($name)->add($imgJpg);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$images = $page->get($name);
if(!($images instanceof Pageimages)) {
	throw new WireTestException("Expected Pageimages, got: " . get_class($images));
}
if($images->count() !== 1) {
	throw new WireTestException("Expected 1 image, got: " . $images->count());
}
wireTests()->li("Add JPG verified, count=1");

// Test: Pageimage properties (width, height, ratio, ext)
$img = $images->first();
if($img->ext !== 'jpg') {
	throw new WireTestException("Expected ext 'jpg', got: " . var_export($img->ext, true));
}
if($img->width < 1 || $img->height < 1) {
	throw new WireTestException("Expected width/height > 0, got: {$img->width}x{$img->height}");
}
$expectedRatio = round($img->width / $img->height, 2);
if(round($img->ratio, 2) !== $expectedRatio) {
	throw new WireTestException("Expected ratio ~$expectedRatio, got: {$img->ratio}");
}
wireTests()->li("Pageimage properties verified: {$img->width}x{$img->height}, ratio={$img->ratio}, ext={$img->ext}");

// Test: description
$page->of(false);
$page->get($name)->first()->description = 'foo bar baz';
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->first()->description !== 'foo bar baz') {
	throw new WireTestException("Description mismatch: " . var_export($page->get($name)->first()->description, true));
}
wireTests()->li("Image description verified");

// Test: add PNG and GIF
$page->of(false);
$page->get($name)->add($imgPng);
$page->get($name)->add($imgGif);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 3) {
	throw new WireTestException("Expected 3 images (jpg+png+gif), got: " . $page->get($name)->count());
}
wireTests()->li("JPG + PNG + GIF added, count=3");

// Test: get by name
$page->of(false);
$img1 = $page->get($name)->get(basename($imgJpg));
if(!$img1) {
	throw new WireTestException("Expected to find image by name '" . basename($imgJpg) . "'");
}
wireTests()->li("Get image by name verified: {$img1->name}");

// Test: size() creates a variation
$variation = $img1->size(100, 100);
if(!$variation || !file_exists($variation->filename)) {
	throw new WireTestException("Expected size() to create a variation file");
}
if($variation->width !== 100 || $variation->height !== 100) {
	throw new WireTestException("Expected 100x100 variation, got: {$variation->width}x{$variation->height}");
}
wireTests()->li("size(100,100) variation created: {$variation->name} ({$variation->width}x{$variation->height})");

// Test: width() proportional resize
$wide = $img1->width(50);
if(!$wide || $wide->width !== 50) {
	throw new WireTestException("Expected width(50) variation, got: " . ($wide ? $wide->width : 'null'));
}
wireTests()->li("width(50) proportional resize verified: {$wide->width}x{$wide->height}");

// Test: invalid image is rejected (not added to Pageimages)
$page->of(false);
$countBefore = $page->get($name)->count();
try {
	$page->get($name)->add($imgInvalid);
	$page->save($name);
	$page = pages()->getFresh($page->id);
	$page->of(false);
	$countAfter = $page->get($name)->count();
	if($countAfter > $countBefore) {
		throw new WireTestException("Expected invalid image to be rejected, but count went from $countBefore to $countAfter");
	}
	wireTests()->li("Invalid image rejected (count unchanged at $countAfter)");
} catch(WireException $e) {
	// Also acceptable — exception thrown on invalid image
	wireTests()->li("Invalid image threw WireException (also acceptable): " . $e->getMessage());
}

// Test: deleteAll cleans up
$page->of(false);
$page->get($name)->deleteAll();
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
if($page->get($name)->count() !== 0) {
	throw new WireTestException("Expected 0 images after deleteAll, got: " . $page->get($name)->count());
}
wireTests()->li("deleteAll() verified, count=0");

// Test: single-image field (maxFiles=1, outputFormat=single)
$nameSingle = 'test_image_single';
/** @var ImageField $fieldSingle */
$fieldSingle = fields()->get($nameSingle);
if(!$fieldSingle) {
	$fieldSingle = new ImageField();
	$fieldSingle->name = $nameSingle;
	$fieldSingle->type = $fieldtype;
	$fieldSingle->label = 'Test Image Single';
	$fieldSingle->extensions = 'jpg jpeg png gif';
	$fieldSingle->maxFiles = 1;
	$fieldSingle->outputFormat = FieldtypeFile::outputFormatSingle;
	$fieldSingle->save();
	wireTests()->li("Created field: $fieldSingle->name");
}
if(!$fieldgroup->hasField($fieldSingle)) {
	$fieldgroup->add($fieldSingle);
	$fieldgroup->save();
	wireTests()->li("Added field to fieldgroup: $fieldSingle->name");
}

$page->of(false);
$page->get($nameSingle)->deleteAll();
$page->save($nameSingle);

$page->of(true);
$val = $page->get($nameSingle);
if($val !== null) {
	throw new WireTestException("Expected null for empty single-image field (OF on), got: " . var_export($val, true));
}
wireTests()->li("Empty single-image field returns null (OF on) verified");

$page->of(false);
$page->get($nameSingle)->add($imgJpg);
$page->save($nameSingle);
$page = pages()->getFresh($page->id);
$page->of(true);
$val = $page->get($nameSingle);
if(!($val instanceof Pageimage)) {
	throw new WireTestException("Expected Pageimage for single-image field (OF on), got: " . gettype($val));
}
wireTests()->li("Single-image field returns Pageimage (OF on) verified: {$val->name}");

// Clean up
$page->of(false);
$page->get($nameSingle)->deleteAll();
$page->save($nameSingle);

// Selectors — re-add Chef.jpg with description for selector testing
$page->of(false);
$page->get($name)->add($imgJpg);
$page->save($name);
$page = pages()->getFresh($page->id);
$page->of(false);
$page->get($name)->first()->description = 'foo bar baz';
$page->save($name);
$img = $page->get($name)->first();
$wLess = $img->width - 1;
$hLess = $img->height - 1;
$selectors = [
	"template=test, $name=$imgJpgName",
	"template=test, $name%=" . basename($imgJpgName, '.jpg'),
	"template=test, $name.description%=bar",
	"template=test, $name.width>$wLess",
	"template=test, $name.height>$hLess",
	"template=test, $name.ratio<1",
	"template=test, $name.filesize>0",
	"template=test, $name.count>0",
	"template=test, $name!=\"\"",
];
foreach($selectors as $selector) {
	$p = pages()->findOne($selector);
	if($p->id !== $page->id) throw new WireTestException("Selector failed: $selector");
	wireTests()->li("Selector passed: $selector");
}
// Blank selector
$page->get($name)->deleteAll();
$page->save($name);
$p = pages()->findOne("template=test, $name=\"\"");
if($p->id !== $page->id) throw new WireTestException("Selector failed: $name=\"\"");
wireTests()->li("Selector passed: $name=\"\"");
