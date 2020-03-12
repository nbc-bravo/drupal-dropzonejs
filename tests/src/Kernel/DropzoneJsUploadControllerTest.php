<?php

namespace Drupal\Tests\dropzonejs\Kernel;

use Drupal\dropzonejs\Controller\UploadController;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests dropzoneJs upload controller.
 *
 * @group DropzoneJs
 */
class DropzoneJsUploadControllerTest extends KernelTestBase {

  /**
   * Temporary file (location + name).
   *
   * @var string
   */
  protected $tmpFile = '';

  /**
   * Temp dir.
   *
   * @var string
   */
  protected $filesDir = '';

  /**
   * Testfile prefix.
   *
   * @var string
   */
  protected $testfilePrefix = 'dropzonejstest_';

  /**
   * Testfile data.
   *
   * @var string
   */
  protected $testfileData = 'DropzoneJs test file data';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'file', 'user', 'dropzonejs', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('dropzonejs');
    $this->installEntitySchema('user');

    $this->filesDir = $this->siteDirectory . '/files';
    $this->setSetting('file_temp_path', $this->filesDir);

    $this->tmpFile = tempnam('', $this->testfilePrefix);
    file_put_contents($this->tmpFile, $this->testfileData);
  }

  /**
   * Test that dropzoneJs correctly handles uploads.
   */
  public function testDropzoneJsUploadController() {
    $this->container->get('router.builder')->rebuild();

    $language = ConfigurableLanguage::createFromLangcode('ru');
    $language->save();
    $this->config('system.site')->set('default_langcode', $language->getId())->save();

    $unicode_emoticon = json_decode('"\uD83D\uDE0E"');

    $uploaded_file = new UploadedFile($this->tmpFile, "{$this->testfilePrefix}controller-Капля   a,A;1{$unicode_emoticon}.jpg");
    $file_bag = new FileBag();
    $file_bag->set('file', $uploaded_file);

    $request = new Request();
    $request->files = $file_bag;

    $upload_handler = $this->container->get('dropzonejs.upload_handler');
    $controller = new UploadController($upload_handler, $request);
    $controller_result = $controller->handleUploads();
    $this->assertInstanceOf(JsonResponse::class, $controller_result);

    $result = json_decode($controller_result->getContent());
    $result_file = $this->filesDir . '/' . $result->result;
    $this->assertStringEndsWith('-kaplya_aa1.jpg.txt', $result_file);
    $this->assertFileExists($result_file);
    $this->assertEquals(file_get_contents($result_file), $this->testfileData);
  }

}
