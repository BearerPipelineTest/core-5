<?php

namespace Test\Encryption;

use OC\Encryption\Util;
use Test\TestCase;

class UtilTest extends TestCase {

	/**
	 * block size will always be 8192 for a PHP stream
	 * @see https://bugs.php.net/bug.php?id=21641
	 * @var integer
	 */
	protected $headerSize = 8192;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $view;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $groupManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	private $config;

	/** @var  \OC\Encryption\Util */
	private $util;

	public function setUp(): void {
		parent::setUp();
		$this->view = $this->getMockBuilder('OC\Files\View')
			->disableOriginalConstructor()
			->getMock();

		$this->userManager = $this->getMockBuilder('OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager = $this->getMockBuilder('OC\Group\Manager')
			->disableOriginalConstructor()
			->getMock();

		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();

		$this->util = new Util(
			$this->view,
			$this->userManager,
			$this->groupManager,
			$this->config
		);
	}

	/**
	 * @dataProvider providesHeadersForEncryptionModule
	 */
	public function testGetEncryptionModuleId($expected, $header) {
		$id = $this->util->getEncryptionModuleId($header);
		$this->assertEquals($expected, $id);
	}

	public function providesHeadersForEncryptionModule() {
		return [
			['', []],
			['', ['1']],
			[2, ['oc_encryption_module' => 2]],
		];
	}

	/**
	 * @dataProvider providesHeaders
	 */
	public function testCreateHeader($expected, $header, $moduleId) {
		$em = $this->createMock('\OCP\Encryption\IEncryptionModule');
		$em->expects($this->any())->method('getId')->willReturn($moduleId);

		$result = $this->util->createHeader($header, $em);
		$this->assertEquals($expected, $result);
	}

	public function providesHeaders() {
		return [
			[\str_pad('HBEGIN:oc_encryption_module:0:HEND', $this->headerSize, '-', STR_PAD_RIGHT)
				, [], '0'],
			[\str_pad('HBEGIN:oc_encryption_module:0:custom_header:foo:HEND', $this->headerSize, '-', STR_PAD_RIGHT)
				, ['custom_header' => 'foo'], '0'],
		];
	}

	/**
	 */
	public function testCreateHeaderFailed() {
		$this->expectException(\OC\Encryption\Exceptions\EncryptionHeaderKeyExistsException::class);

		$header = ['header1' => 1, 'header2' => 2, 'oc_encryption_module' => 'foo'];

		$em = $this->createMock('\OCP\Encryption\IEncryptionModule');
		$em->expects($this->any())->method('getId')->willReturn('moduleId');

		$this->util->createHeader($header, $em);
	}

	/**
	 * @dataProvider providePathsForTestIsExcluded
	 */
	public function testIsExcluded($path, $keyStorageRoot, $expected) {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('core', 'encryption_key_storage_root', '')
			->willReturn($keyStorageRoot);
		$this->userManager
			->expects($this->any())
			->method('userExists')
			->will($this->returnCallback([$this, 'isExcludedCallback']));

		$this->assertSame(
			$expected,
			$this->util->isExcluded($path)
		);
	}

	public function providePathsForTestIsExcluded() {
		return [
			['/files_encryption', '', true],
			['files_encryption/foo.txt', '', true],
			['test/foo.txt', '', false],
			['/user1/files_encryption/foo.txt', '', true],
			['/user1/files/foo.txt', '', false],
			['/keyStorage/user1/files/foo.txt', 'keyStorage', true],
			['/keyStorage/files_encryption', '/keyStorage', true],
			['keyStorage/user1/files_encryption', '/keyStorage/', true],
			['/files_external/rootcerts.crt', '', true],

		];
	}

	public function isExcludedCallback() {
		$args = \func_get_args();
		if ($args[0] === 'user1') {
			return true;
		}

		return false;
	}

	/**
	 * @dataProvider dataTestIsFile
	 */
	public function testIsFile($path, $expected) {
		$this->assertSame(
			$expected,
			$this->util->isFile($path)
		);
	}

	public function dataTestIsFile() {
		return [
			['/user/files/test.txt', true],
			['/user/files', true],
			['/user/files_versions/test.txt', false],
			['/user/foo/files/test.txt', false],
			['/files/foo/files/test.txt', false],
			['/user', false],
			['/user/test.txt', false],
		];
	}
}
