<?php
namespace Multisite_JSON_API;

@include_once 'PHPUnit/Framework/TestCase.php';

class EndpointTest extends \PHPUnit_Framework_TestCase {
	public $api;
	public static $plugin_is_active = true;
	public static $is_multisite = true;
	public static $is_subdomain = true;
	
	protected function setUp() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		self::$plugin_is_active = true;
		self::$is_multisite = true;
		self::$is_subdomain = true;
		$this->api = new Endpoint();
	}

	public function testErrorConformsToHerokuErrors(){
		$this->expectOutputString("{\n    \"id\": \"error_id\",\n    \"message\": \"Error!\",\n    \"url\": \"http://github.com/remkade/multisite-json-api/wiki\"\n}");
		$this->api->error("Error!", "error_id", 400);
	}

	/**
	 * @dataProvider authenticateProvider
	 */
	public function testAuthenticate($username, $password, $result) {
		$_SERVER['HTTP_USER'] = $username;
		$_SERVER['HTTP_PASSWORD'] = $password;

		$this->assertEquals($this->api->authenticate(), $result);
	}

	public function authenticateProvider() {
		return array(
			array('invalid', 'invalid', false),
			array('fakeuser', 'password', false),
			array('user', 'not the right password', false),
			array('admin', 'password', get_user_by('login', 'admin')),
			// THis will return false since its not an admin and can't manage sites
			array('user', 'password', false)
		);
	}

	/**
	 * @dataProvider sitenameProvider
	 */
	public function testIsValidSiteName($expected, $sitename) {
		$this->assertEquals($this->api->is_valid_sitename($sitename), $expected);
	}

	public function sitenameProvider() {
		return array(
			array(true, 'potatoes'),
			array(true, 'dashes-are-ok'),
			array(false, 'Odds & Ends * not $ok'),
			array(false, 'No spaces')
		);
	}

	/**
	 * @dataProvider emailProvider
	 */
	public function testIsEmail($expected, $email) {
		$this->assertEquals($this->api->is_valid_email($email), $expected);
	}

	public function emailProvider() {
		return array(
			array(true, 'joe@awesome.com'),
			array(true, 'valid@bbc.co.uk'),
			array(true, 'valid+tag@gmail.com'),
			array(true, 'newproviders@email.email', true),
			array(true, 'testing@email.ninja'),
			array(false, 'notanemail'),
			array(false, 'notanemail.com')
		);
	}

	public function testIsValidSiteTitle(){
		// Ensure that we have at least 1 character and that all characters are alphanumeric spaces or dashes
		$this->assertFalse($this->api->is_valid_site_title(''));
		$this->assertFalse($this->api->is_valid_site_title('!First character is not valid'));
		$this->assertFalse($this->api->is_valid_site_title('?Que?'));

		// Valid examples
		$this->assertTrue($this->api->is_valid_site_title('a1'));
		$this->assertTrue($this->api->is_valid_site_title('123'));
		$this->assertTrue($this->api->is_valid_site_title('singleword'));
		$this->assertTrue($this->api->is_valid_site_title('This is valid'));
		$this->assertTrue($this->api->is_valid_site_title('Hyphens-are-ok'));
	}

	/**
	 * @dataProvider fullDomainWithSubdomainsProvider
	 */
	public function testFullDomainWithSubdomains($current_site, $sitename, $expected) {
		self::$is_subdomain = true;
		$this->assertEquals($expected, $this->api->full_domain($sitename, $current_site));
	}

	public function fullDomainWithSubdomainsProvider() {
		return array(
			array(null, 'potato', 'potato.example.com'),
			array(null, 'test-domain', 'test-domain.example.com'),
			array((object)array('domain'=>'multisite.com'), 'api', 'api.multisite.com')
		);
	}

	/**
	 * @dataProvider fullDomainWithSubdirectoryProvider
	 */
	public function testFullDomainWithSubdirectory($current_site, $sitename, $expected) {
		self::$is_subdomain = false;
		$this->assertEquals($expected, $this->api->full_domain($sitename, $current_site));
	}

	public function fullDomainWithSubdirectoryProvider() {
		return array(
			array(null, 'potato', 'example.com'),
			array(null, 'test-domain', 'example.com'),
			array((object)array('domain'=>'www.example.com'), 'test-domain', 'www.example.com'),
			array((object)array('domain'=>'api.multisite.com'), 'api', 'api.multisite.com')
		);
	}

	/**
	 * @dataProvider fullPathWithSubdirectoryProvider
	 */
	public function testFullPathWithSubdirectory($current_site, $sitename, $expected) {
		self::$is_subdomain = false;
		$this->assertEquals($expected, $this->api->full_path($sitename, $current_site));
	}

	public function fullPathWithSubdirectoryProvider() {
		return array(
			array(null, 'potato', '/potato/'),
			array(null, 'test-domain', '/test-domain/'),
			array((object)array('domain'=>'www.example.com', 'path' => '/sub/'), 'test-site', '/sub/test-site/'),
			array((object)array('domain'=>'api.multisite.com', 'path' => '/blog-with-dashes/'), 'coolsite', '/blog-with-dashes/coolsite/')
		);
	}

	/**
	 * @dataProvider fullPathWithSubdomainProvider
	 */
	public function testFullPathWithSubdomain($current_site, $sitename, $expected) {
		self::$is_subdomain = true;
		$this->assertEquals($expected, $this->api->full_path($sitename, $current_site));
	}

	public function fullPathWithSubdomainProvider() {
		return array(
			array(null, 'potato', '/'),
			array(null, 'test-domain', '/'),
			array((object)array('domain'=>'www.example.com', 'path' => '/sub/'), 'test-site', '/sub/'),
			array((object)array('domain'=>'api.multisite.com', 'path' => '/blog-with-dashes/'), 'coolsite', '/blog-with-dashes/')
		);
	}
}
?>
