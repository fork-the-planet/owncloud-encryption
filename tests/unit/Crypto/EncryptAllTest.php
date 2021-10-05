<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Tests\Crypto;

use OC\Files\FileInfo;
use OC\Files\Storage\Common;
use OC\Files\View;
use OCA\Encryption\Crypto\EncryptAll;
use OCP\Files\Mount\IMountPoint;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;
use Test\Traits\UserTrait;
use OCP\Security\ISecureRandom;
use OCP\UserInterface;
use OCP\IL10N;
use OCP\Mail\IMailer;
use OCP\IConfig;
use OCP\IUserManager;
use OCA\Encryption\Util;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Users\Setup;

/**
 * Class EncryptAllTest
 *
 * @group  DB
 *
 * @package OCA\Encryption\Tests\Crypto
 */
class EncryptAllTest extends TestCase {
	use UserTrait;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCA\Encryption\KeyManager */
	protected $keyManager;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCA\Encryption\Util */
	protected $util;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCP\IUserManager */
	protected $userManager;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCA\Encryption\Users\Setup */
	protected $setupUser;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OC\Files\View */
	protected $view;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCP\IConfig */
	protected $config;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCP\Mail\IMailer */
	protected $mailer;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCP\IL10N */
	protected $l;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Helper\QuestionHelper */
	protected $questionHelper;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Input\InputInterface */
	protected $inputInterface;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Output\OutputInterface */
	protected $outputInterface;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCP\UserInterface */
	protected $userInterface;

	/** @var  \PHPUnit\Framework\MockObject\MockObject | \OCP\Security\ISecureRandom  */
	protected $secureRandom;

	/** @var  EncryptAll */
	protected $encryptAll;

	public function setUp(): void {
		parent::setUp();
		$this->setupUser = $this->getMockBuilder(Setup::class)
			->disableOriginalConstructor()->getMock();
		$this->keyManager = $this->getMockBuilder(KeyManager::class)
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder(Util::class)
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()->getMock();
		$this->view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()->getMock();
		$this->mailer = $this->getMockBuilder(IMailer::class)
			->disableOriginalConstructor()->getMock();
		$this->l = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')
			->disableOriginalConstructor()->getMock();
		$this->inputInterface = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
			->disableOriginalConstructor()->getMock();
		$this->outputInterface = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
			->disableOriginalConstructor()->getMock();
		$this->userInterface = $this->getMockBuilder(UserInterface::class)
			->disableOriginalConstructor()->getMock();

		$this->outputInterface->method('getFormatter')
			->willReturn($this->createMock('\Symfony\Component\Console\Formatter\OutputFormatterInterface'));

		$this->userManager->method('getBackends')->willReturn([$this->userInterface]);
		$this->userInterface->method('getUsers')->willReturn(['user1', 'user2']);

		$this->secureRandom = $this->getMockBuilder(ISecureRandom::class)->disableOriginalConstructor()->getMock();
		$this->secureRandom->method('getMediumStrengthGenerator')->willReturn($this->secureRandom);
		$this->secureRandom->method('getLowStrengthGenerator')->willReturn($this->secureRandom);
		$this->secureRandom->method('generate')->willReturn('12345678');

		$this->encryptAll = new EncryptAll(
			$this->setupUser,
			$this->userManager,
			$this->view,
			$this->keyManager,
			$this->util,
			$this->config,
			$this->mailer,
			$this->l,
			$this->questionHelper,
			$this->secureRandom
		);
	}

	public function testEncryptAll() {
		/** @var EncryptAll  | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->method('isMasterKeyEnabled')->willReturn(false);
		$encryptAll->expects($this->once())->method('createKeyPairs')->with();
		$encryptAll->expects($this->once())->method('encryptAllUsersFiles')->with();
		$encryptAll->expects($this->once())->method('outputPasswords')->with();

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);
	}

	public function testEncryptAllWithMasterKey() {
		/** @var EncryptAll  | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->method('isMasterKeyEnabled')->willReturn(true);
		$encryptAll->expects($this->never())->method('createKeyPairs');
		$this->keyManager->expects($this->once())->method('validateMasterKey');
		$encryptAll->expects($this->once())->method('encryptAllUsersFiles')->with();
		$encryptAll->expects($this->never())->method('outputPasswords');

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);
	}

	public function testCreateKeyPairs() {
		/** @var EncryptAll  | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['setupUserFS', 'generateOneTimePassword'])
			->getMock();

		// set protected property $output
		$this->invokePrivate($encryptAll, 'output', [$this->outputInterface]);

		$this->keyManager->expects($this->exactly(2))->method('userHasKeys')
			->willReturnCallback(
				function ($user) {
					if ($user === 'user1') {
						return false;
					}
					return true;
				}
			);

		$encryptAll->expects($this->once())->method('setupUserFS')->with('user1');
		$encryptAll->expects($this->once())->method('generateOneTimePassword')->with('user1')->willReturn('password');
		$this->setupUser->expects($this->once())->method('setupUser')->with('user1', 'password');

		$this->invokePrivate($encryptAll, 'createKeyPairs');

		$userPasswords = $this->invokePrivate($encryptAll, 'userPasswords');

		// we only expect the skipped user, because generateOneTimePassword which
		// would set the user with the new password was mocked.
		// This method will be tested separately
		$this->assertCount(1, $userPasswords);
		$this->assertSame('', $userPasswords['user2']);
	}

	public function testEncryptAllUsersFiles() {
		/** @var EncryptAll  | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['encryptUsersFiles'])
			->getMock();

		$this->util->method('isMasterKeyEnabled')->willReturn(false);

		// set protected property $output
		$this->invokePrivate($encryptAll, 'output', [$this->outputInterface]);
		$this->invokePrivate($encryptAll, 'userPasswords', [['user1' => 'pwd1', 'user2' => 'pwd2']]);

		$encryptAll
			->expects($this->exactly(2))
			->method('encryptUsersFiles')
			->withConsecutive(
				['user1'],
				['user2'],
			);

		$this->invokePrivate($encryptAll, 'encryptAllUsersFiles');
	}

	public function testEncryptUsersFiles() {
		/** @var EncryptAll  | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['encryptFile', 'setupUserFS'])
			->getMock();

		$this->util->method('isMasterKeyEnabled')->willReturn(false);

		$commonStorage = $this->createMock(Common::class);
		$mountPoint = $this->createMock(IMountPoint::class);
		$fileInfo1 = new FileInfo('/usr1/files/foo', $commonStorage, 'foo', ['name' => 'foo', 'type'=>'dir'], $mountPoint);
		$fileInfo2 = new FileInfo('/usr1/files/bar', $commonStorage, 'foo', ['name' => 'bar', 'type'=>'file'], $mountPoint);
		$fileInfo3 = new FileInfo('/usr1/files/foo/subfile', $commonStorage, 'foo', ['name' => 'subfile', 'type'=>'file'], $mountPoint);

		$this->view
			->expects($this->exactly(2))
			->method('getDirectoryContent')
			->withConsecutive(
				['/user1/files'],
				['/user1/files/foo'],
			)
			->willReturnOnConsecutiveCalls(
				[$fileInfo1, $fileInfo2],
				[$fileInfo3],
			);

		$this->view->method('is_dir')
			->willReturnCallback(
				function ($path) {
					if ($path === '/user1/files/foo') {
						return true;
					}
					return false;
				}
			);

		$encryptAll
			->expects($this->exactly(2))
			->method('encryptFile')
			->withConsecutive(
				['/user1/files/bar'],
				['/user1/files/foo/subfile'],
			);

		$progressBar = new ProgressBar(new NullOutput());

		self::invokePrivate($encryptAll, 'encryptUsersFiles', ['user1', $progressBar, '']);
	}

	public function testEncryptUsersFilesIncomingShares() {
		/** @var EncryptAll  | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['encryptFile', 'setupUserFS'])
			->getMock();

		$this->util->method('isMasterKeyEnabled')->willReturn(false);

		$commonStorage = $this->createMock(Common::class);
		$commonStorage->expects($this->once())
			->method('instanceOfStorage')
			->with('\OCA\Files_Sharing\ISharedStorage')
			->willReturn(true);
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->once())
			->method('getStorage')
			->willReturn($commonStorage);
		$this->view
			->method('getDirectoryContent')
			->with('/user1/files')
			->willReturn([$fileInfo]);

		$outputInterface = $this->createMock(OutputInterface::class);
		$progressBar = new ProgressBar($outputInterface);

		$result = $this->invokePrivate($encryptAll, 'encryptUsersFiles', ['user1', $progressBar, '']);
		$this->assertNull($result);
	}

	public function testEncryptFileFileId() {
		$userName = $this->getUniqueID('encrypt_test_user1_');

		$this->createUser($userName, 'user1');
		\OC::$server->getUserFolder($userName);

		$view = new View("/{$userName}/files");
		$view->touch('bar.txt');
		$oldFileInfo = $view->getFileInfo('bar.txt');

		/** @var EncryptAll  | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['setupUserFS'])
			->getMock();

		$result = $this->invokePrivate($encryptAll, 'encryptFile', ["/${userName}/files/bar.txt"]);
		$this->assertTrue($result);

		$view1 = new View('/');
		$fileInfo = $view1->getFileInfo("/{$userName}/files/bar.txt");
		$this->assertEquals($fileInfo->getId(), $oldFileInfo->getId());
	}

	public function testGenerateOneTimePassword() {
		$password = $this->invokePrivate($this->encryptAll, 'generateOneTimePassword', ['user1']);
		$this->assertIsString($password);
		$this->assertSame(8, \strlen($password));

		$userPasswords = $this->invokePrivate($this->encryptAll, 'userPasswords');
		$this->assertCount(1, $userPasswords);
		$this->assertSame($password, $userPasswords['user1']);
	}

	public function testEncryptAlreadyEncryptedFile() {
		$this->keyManager->method('getVersion')
			->with('/user1/files/bar.txt', $this->view)
			->willReturn(1);
		$result = $this->invokePrivate($this->encryptAll, 'encryptFile', ['/user1/files/bar.txt']);
		$this->assertFalse($result);
	}

	/**
	 * @dataProvider providesCreateMasterKeyData
	 * @param bool $isShareKeySet
	 * @param bool $isMasterkeySet
	 * @param bool $expectedResult
	 */
	public function testCreateMasterKey($isShareKeySet, $isMasterkeySet, $expectedResult) {
		$this->keyManager->expects($this->once())
			->method('setPublicShareKeyIDAndMasterKeyId');
		$this->keyManager->expects($this->once())
			->method('validateShareKey');
		$this->keyManager->expects($this->once())
			->method('validateMasterKey');
		$this->keyManager->method('getPublicShareKey')
			->willReturn($isShareKeySet);
		$this->keyManager->method('getPublicMasterKey')
			->willReturn($isMasterkeySet);
		$returnVal = $this->encryptAll->createMasterKey();
		$this->assertEquals($expectedResult, $returnVal);
	}

	public function providesCreateMasterKeyData() {
		return [
			[true, false, false],
			[true, true, true],
			[false, true, false],
			[false, false, false],
		];
	}
}
