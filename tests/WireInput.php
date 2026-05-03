<?php namespace ProcessWire;

/** @var Page $page */
/** @var WireInput $input */
/** @var Config $config */

$originalGet = $input->get()->getArray();
$originalPost = $input->post()->getArray();
$originalCookie = $input->cookie()->getArray();
$originalWhitelist = $input->whitelist()->getArray();
$originalUrlSegments = $input->urlSegments();
$originalPageNum = $input->pageNum();
$originalWireInputOrder = $config->wireInputOrder;
$originalRequestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
$originalRequestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
$originalHttps = $config->https;

$replaceInputData = function(WireInputData $data, array $values) {
	$ref = new \ReflectionObject($data);
	$property = null;
	while($ref && !$property) {
		if($ref->hasProperty('data')) {
			$property = $ref->getProperty('data');
		} else {
			$ref = $ref->getParentClass();
		}
	}
	if($property) {
		$property->setAccessible(true);
		$property->setValue($data, $values);
	}
};

try {

	// ===== SETUP =====

	$input->get()->removeAll();
	$input->post()->removeAll();
	$replaceInputData($input->cookie(), []);
	$input->whitelist()->removeAll();
	$input->setUrlSegments([]);
	$input->setPageNum(1);

	$input->get()->setArray([
		'q' => '<b>Hello</b> World',
		'qty' => '42',
		'badQty' => 'not-a-number',
		'color' => 'blue',
		'badColor' => 'purple',
		'ids' => ['1', '2', 'three'],
		'empty' => '',
		'title_one' => '<b>One</b>',
		'title_two' => '<i>Two</i>',
		'nested' => ['a' => ['b' => 'too-deep']],
		'unsafe name' => 'remove',
		'clean' => 'safe value',
		'markup' => '<b>remove</b>',
		'long' => str_repeat('x', 40),
	]);

	$input->post()->setArray([
		'comments' => "Line 1\n<b>Line 2</b>",
		'price' => '12.50',
		'qty' => '7',
		'title_en' => '<b>English</b>',
		'title_es' => '<i>Spanish</i>',
		'summary' => 'ProcessWire CMS',
		'ids' => '1,2,foo',
	]);

	$replaceInputData($input->cookie(), [
		'foo' => '<b>bar</b>',
		'color' => 'red',
	]);

	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_SERVER['REQUEST_URI'] = rtrim($page->url, '/') . '/photos/large/page3/?q=<b>x</b>&clean=safe';

	// ===== QUICK-ACCESS PROPERTIES =====

	check("\$input->get is WireInputData", true, $input->get instanceof WireInputData);
	check("\$input->post is WireInputData", true, $input->post instanceof WireInputData);
	check("\$input->cookie is WireInputDataCookie", true, $input->cookie instanceof WireInputDataCookie);
	check("\$input->whitelist is WireInputData", true, $input->whitelist instanceof WireInputData);
	check("\$input->pageNum defaults to 1", 1, $input->pageNum);
	check("\$input->scheme returns http or https", true, in_array($input->scheme, ['http', 'https'], true));

	// ===== GET, POST AND COOKIE ACCESS =====

	check("get() with no key returns WireInputData", true, $input->get() instanceof WireInputData);
	check("get('q') returns raw unsanitized value", '<b>Hello</b> World', $input->get('q'));
	check("get('q', 'text') sanitizes with named sanitizer", 'Hello World', $input->get('q', 'text'));
	check("get('q', 'text,entities') chains sanitizers", 'Hello World', $input->get('q', 'text,entities'));
	check("get('color', whitelist) returns allowed value", 'blue', $input->get('color', ['red', 'blue', 'green']));
	check("get('badColor', whitelist, fallback) returns fallback", 'red', $input->get('badColor', ['red', 'blue', 'green'], 'red'));
	check("get('missing', 'int', fallback) returns fallback", 25, $input->get('missing', 'int', 25));
	check("get('active', callback) returns callback result", true, $input->get('qty', function($value) { return ((int) $value) > 0; }));
	check("get('ids[]', 'int') forces array and sanitizes values", [1, 2, 0], $input->get('ids[]', 'int'));
	check("get('qty', 42) accepts matching integer valid argument", 42, $input->get('qty', 42));
	check("get('qty', 99, fallback) rejects non-matching integer valid argument", 1, $input->get('qty', 99, 1));

	check("post() with no key returns WireInputData", true, $input->post() instanceof WireInputData);
	check("post('comments') returns raw value", "Line 1\n<b>Line 2</b>", $input->post('comments'));
	check("post('comments', 'textarea') sanitizes textarea", "Line 1\nLine 2", $input->post('comments', 'textarea'));
	check("post('qty', 'int', fallback) returns sanitized int", 7, $input->post('qty', 'int', 1));
	check("post('missing', null, fallback) returns fallback without sanitizer", 'fallback', $input->post('missing', null, 'fallback'));

	check("cookie() with no key returns WireInputDataCookie", true, $input->cookie() instanceof WireInputDataCookie);
	check("cookie('foo') returns raw cookie value", '<b>bar</b>', $input->cookie('foo'));
	check("cookie('foo', 'text') sanitizes cookie value", 'bar', $input->cookie('foo', 'text'));
	check("cookie('color', whitelist) returns allowed cookie value", 'red', $input->cookie('color', ['red', 'blue']));
	check("cookie('missing', 'text', fallback) returns fallback", 'fallback', $input->cookie('missing', 'text', 'fallback'));

	// ===== INLINE SANITIZATION =====

	check("\$input->get->text('q') sanitizes text", 'Hello World', $input->get->text('q'));
	check("\$input->post->textarea('comments') sanitizes textarea", "Line 1\nLine 2", $input->post->textarea('comments'));
	check("\$input->post->float('price') sanitizes float", 12.5, $input->post->float('price'));
	check("\$input->post->float('price', min, max, precision) applies numeric args", 10.0, $input->post->float('price', 0, 10, 2));
	check("\$input->post->int('qty', 1, 5) applies min/max args", 5, $input->post->int('qty', 1, 5));
	check("\$input->post->intArray('ids') sanitizes CSV to int array", [1, 2, 0], $input->post->intArray('ids'));
	check("\$input->cookie->text('foo') sanitizes cookie text", 'bar', $input->cookie->text('foo'));

	// ===== WHITELIST =====

	$input->whitelist('limit', 25);
	check("whitelist('limit', value) stores value", 25, $input->whitelist('limit'));
	$input->whitelist(['sort' => 'title', 'dir' => 'asc']);
	check("whitelist(array) stores multiple values", 'title', $input->whitelist('sort'));
	check("whitelist() returns WireInputData", true, $input->whitelist() instanceof WireInputData);
	check("whitelist queryString() includes stored values", 'limit=25&sort=title&dir=asc', $input->whitelist()->queryString());

	// ===== DIRECT INPUT ORDER LOOKUP =====

	$config->wireInputOrder = 'get post cookie whitelist';
	check("direct property lookup checks GET first", 'blue', $input->color);
	$config->wireInputOrder = 'cookie post get whitelist';
	check("direct property lookup respects wireInputOrder", 'red', $input->color);
	check("__isset() true when direct property exists", true, isset($input->color));
	check("__isset() false when direct property missing", false, isset($input->does_not_exist));

	// ===== URL SEGMENTS =====

	$input->setUrlSegments(['photos', 'sort-date', 'page-name']);
	check("urlSegments() returns 1-indexed URL segment array", [1 => 'photos', 2 => 'sort-date', 3 => 'page-name'], $input->urlSegments());
	check("urlSegment(1) returns first segment", 'photos', $input->urlSegment(1));
	check("urlSegment() defaults to first segment", 'photos', $input->urlSegment());
	check("urlSegment(2) returns second segment", 'sort-date', $input->urlSegment(2));
	check("urlSegment(-1) returns last segment", 'page-name', $input->urlSegment(-1));
	check("urlSegment('photos') returns matching index", 1, $input->urlSegment('photos'));
	check("urlSegment('missing') returns 0 when not found", 0, $input->urlSegment('missing'));
	check("urlSegment('photos=') returns following segment", 'sort-date', $input->urlSegment('photos='));
	check("urlSegment('=sort-date') returns previous segment", 'photos', $input->urlSegment('=sort-date'));
	check("urlSegment('sort-*') returns wildcard match", 'sort-date', $input->urlSegment('sort-*'));
	check("urlSegment('sort-(*)') returns wildcard capture", 'date', $input->urlSegment('sort-(*)'));
	check("urlSegment('/^sort-(.+)$/') returns regex capture", 'date', $input->urlSegment('/^sort-(.+)$/'));
	check("urlSegment1('photos') tests only first segment", true, $input->urlSegment1('photos'));
	check("urlSegment2('photos') returns false when focused segment does not match", false, $input->urlSegment2('photos'));
	check("urlSegmentFirst() returns first segment", 'photos', $input->urlSegmentFirst());
	check("urlSegmentLast() returns last segment", 'page-name', $input->urlSegmentLast());
	check("urlSegmentStr() joins current segments", 'photos/sort-date/page-name', $input->urlSegmentStr());
	check("urlSegmentStr(['segments' => ...]) uses override segments", 'alpha/beta', $input->urlSegmentStr(['segments' => ['alpha', 'beta']]));
	check("urlSegmentStr(['values' => ...]) converts key/value pairs", 'sort/date/page/2', $input->urlSegmentStr(['values' => ['sort' => 'date', 'page' => 2]]));
	check("\$input->urlSegmentStr property joins segments", 'photos/sort-date/page-name', $input->urlSegmentStr);
	check("\$input->urlSegment1 property returns first segment", 'photos', $input->urlSegment1);
	check("\$input->urlSegmentLast property returns last segment", 'page-name', $input->urlSegmentLast);
	$input->setUrlSegment(2, null);
	check("setUrlSegment(num, null) removes and reindexes segments", [1 => 'photos', 2 => 'page-name'], $input->urlSegments());
	$input->setUrlSegment(2, 'Needs Sanitizing!');
	check("setUrlSegment() sanitizes segment names", 'Needs_Sanitizing_', $input->urlSegment(2));

	// ===== PAGINATION =====

	$input->setPageNum(3);
	check("pageNum() returns current page number", 3, $input->pageNum());
	check("\$input->pageNum property returns current page number", 3, $input->pageNum);
	check("pageNumStr() returns current page number segment", $config->pageNumUrlPrefix . '3', $input->pageNumStr());
	check("pageNumStr(1) returns blank for page 1", '', $input->pageNumStr(1));
	check("pageNumStr(5) returns requested page number segment", $config->pageNumUrlPrefix . '5', $input->pageNumStr(5));

	// ===== URLS AND REQUEST INFO =====

	check("url() includes current URL segments", 'photos/Needs_Sanitizing_', $input->url(), '*=');
	check("url(['pageNum' => 2]) includes requested page number", $config->pageNumUrlPrefix . '2', $input->url(['pageNum' => 2]), '*=');
	check("url(true) includes query string", '?q=', $input->url(true), '*=');
	check("httpUrl() starts with httpHostUrl()", $input->httpHostUrl(), $input->httpUrl(), '^=');
	check("httpsUrl() starts with https host URL", $input->httpHostUrl(true), $input->httpsUrl(), '^=');
	check("httpHostUrl(true, host) forces https", 'https://example.com', $input->httpHostUrl(true, 'example.com'));
	check("httpHostUrl(false, host) forces http", 'http://example.com', $input->httpHostUrl(false, 'example.com'));
	check("httpHostUrl('', host) returns protocol-relative URL", '//example.com', $input->httpHostUrl('', 'example.com'));
	check("canonicalUrl() can force scheme, host, segments, pageNum, and query string", 'https://example.com', $input->canonicalUrl([
		'scheme' => 'https',
		'host' => 'example.com',
		'urlSegments' => ['alpha'],
		'pageNum' => 2,
		'queryString' => ['limit' => 25],
	]), '^=');
	check("canonicalUrl() includes override URL segment", '/alpha', $input->canonicalUrl([
		'scheme' => 'https',
		'host' => 'example.com',
		'urlSegments' => ['alpha'],
		'pageNum' => false,
		'queryString' => false,
	]), '*=');
	check("queryString() returns raw GET query string", 'q=%3Cb%3EHello%3C%2Fb%3E+World', $input->queryString(), '^=');
	check("queryString(overrides) overrides or adds values", 'qty=100', $input->queryString(['qty' => 100]), '*=');

	$cleanQuery = $input->queryStringClean([
		'values' => [
			'sort' => 'title',
			'bad name' => 'removed',
			'markup' => '<b>removed</b>',
			'limit' => '25',
		],
		'validNames' => ['sort', 'bad name', 'markup', 'limit'],
	]);
	check("queryStringClean() keeps valid sanitized values", 'sort=title', $cleanQuery, '*=');
	check("queryStringClean() removes names changed by sanitization", false, strpos($cleanQuery, 'bad'));
	check("queryStringClean() removes values changed by sanitization", false, strpos($cleanQuery, 'markup'));
	check("queryStringClean(entityEncode=false) can return unencoded separator", 'sort=title&limit=25', $input->queryStringClean([
		'values' => ['sort' => 'title', 'limit' => '25'],
		'entityEncode' => false,
	]));

	$config->https = false;
	check("scheme() returns http when config https is false", 'http', $input->scheme());
	$config->https = true;
	check("scheme() returns https when config https is true", 'https', $input->scheme());
	$config->https = $originalHttps;

	check("requestMethod() returns current request method", 'POST', $input->requestMethod());
	check("requestMethod('post') matches case-insensitively", true, $input->requestMethod('post'));
	check("is('post') aliases requestMethod()", true, $input->is('post'));
	check("is('get') returns false when request method differs", false, $input->is('get'));

	// ===== COOKIE MANAGEMENT =====

	$cookieOptions = $input->cookie->options();
	$input->cookie->options('age', 86400);
	check("cookie->options(key, value) stores default option", 86400, $input->cookie->options('age'));
	$input->cookie->options(['httponly' => true, 'samesite' => 'Strict']);
	check("cookie->options(array) stores multiple options", true, $input->cookie->options('httponly'));
	check("cookie->options() returns all runtime options", 'Strict', $input->cookie->options()['samesite']);
	$input->cookie->options($cookieOptions);

	$seedCookie = ['managed_cookie' => 'value', 'temp_one' => '1', 'temp_two' => '2'];
	$testCookie = $wire->wire(new WireInputDataCookie($seedCookie));
	check("WireInputDataCookie::set() stores value before init", 'updated', $testCookie->set('managed_cookie', 'updated')->get('managed_cookie'));
	$testCookie->temp_one = 'changed';
	check("WireInputDataCookie property assignment stores value before init", 'changed', $testCookie->get('temp_one'));

	// ===== WIREINPUTDATA SEARCHING AND ITERATION =====

	$foundTitles = $input->post->find('title_*');
	check("WireInputData::find() wildcard finds matching names", ['title_en' => '<b>English</b>', 'title_es' => '<i>Spanish</i>'], $foundTitles);
	$foundSanitized = $input->post->find('title_*', ['sanitizer' => 'text']);
	check("WireInputData::find() can sanitize found values", ['title_en' => 'English', 'title_es' => 'Spanish'], $foundSanitized);
	$foundByValue = $input->post->find('/wire/i', ['type' => 'value']);
	check("WireInputData::find() can match by value regex", true, isset($foundByValue['summary']));
	check("WireInputData::findOne() returns first matching value", '<b>English</b>', $input->post->findOne('title_*'));
	check("WireInputData::findOne() returns null when not found", null, $input->post->findOne('does_not_exist_*'));
	check("WireInputData implements Countable", 7, count($input->post));
	check("WireInputData supports array-style read", '7', $input->post['qty']);
	$input->post['array_style'] = 'value';
	check("WireInputData supports array-style write", 'value', $input->post('array_style'));
	unset($input->post['array_style']);
	check("WireInputData supports array-style unset", null, $input->post('array_style'));
	check("WireInputData getArray() returns plain PHP array", true, is_array($input->post->getArray()));
	$iterated = [];
	foreach($input->post as $name => $value) {
		$iterated[$name] = $value;
	}
	check("WireInputData implements IteratorAggregate", $input->post->getArray(), $iterated);
	$input->post->remove('summary');
	check("WireInputData::remove() removes one variable", null, $input->post('summary'));
	$input->post->removeAll();
	check("WireInputData::removeAll() clears all variables", 0, count($input->post));

} finally {
	$input->get()->removeAll()->setArray($originalGet);
	$input->post()->removeAll()->setArray($originalPost);
	$replaceInputData($input->cookie(), $originalCookie);
	$input->whitelist()->removeAll()->setArray($originalWhitelist);
	$input->setUrlSegments(array_values($originalUrlSegments));
	$input->setPageNum($originalPageNum);
	$config->wireInputOrder = $originalWireInputOrder;
	$config->https = $originalHttps;
	if($originalRequestMethod === null) {
		unset($_SERVER['REQUEST_METHOD']);
	} else {
		$_SERVER['REQUEST_METHOD'] = $originalRequestMethod;
	}
	if($originalRequestUri === null) {
		unset($_SERVER['REQUEST_URI']);
	} else {
		$_SERVER['REQUEST_URI'] = $originalRequestUri;
	}
}
