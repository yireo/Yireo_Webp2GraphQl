<?php declare(strict_types=1);

namespace Yireo\Webp2GraphQl\Test\Integration;

use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Yireo\IntegrationTestHelper\Test\Integration\GraphQlTestCase;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;

class GraphQlTest extends GraphQlTestCase
{
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        $fileDriver = $this->objectManager->get(FileDriver::class);
        foreach ($this->createdFiles as $createdFile) {
            if ($fileDriver->isExists($createdFile)) {
                $fileDriver->deleteFile($createdFile);
            }
        }

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple_with_media_gallery_entries.php
     * @magentoAppArea graphql
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoCache all disabled
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/convert_images 1
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/target_directory same_as_source
     * @magentoConfigFixture current_store yireo_webp2/settings/enabled 1
     * @magentoConfigFixture current_store dev/static/sign 0
     */
    public function testUrlWebpIsNullWhenNoWebpFileExists(): void
    {
        $imageData = $this->getFirstImageData();
        $this->assertNotEmpty($imageData['url']);
        $this->assertArrayHasKey('url_webp', $imageData);
        $this->assertNull($imageData['url_webp']);

        $webpPath = $this->getWebpPath($imageData['url']);
        $this->assertFalse(
            $this->objectManager->get(FileDriver::class)->isExists($webpPath),
            'GraphQL request should never convert an image: '.$webpPath
        );
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple_with_media_gallery_entries.php
     * @magentoAppArea graphql
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoCache all disabled
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/target_directory same_as_source
     * @magentoConfigFixture current_store yireo_webp2/settings/enabled 1
     * @magentoConfigFixture current_store dev/static/sign 0
     */
    public function testUrlWebpReturnsExistingWebpFile(): void
    {
        $imageData = $this->getFirstImageData();
        $webpPath = $this->getWebpPath($imageData['url']);

        $fileDriver = $this->objectManager->get(FileDriver::class);
        $fileDriver->createDirectory(dirname($webpPath));
        $fileDriver->filePutContents($webpPath, 'webp');
        $fileDriver->touch($webpPath, time() + 3600);
        $this->createdFiles[] = $webpPath;

        $imageData = $this->getFirstImageData();
        $this->assertIsString($imageData['url_webp']);
        $this->assertStringEndsWith('.webp', $imageData['url_webp']);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple_with_media_gallery_entries.php
     * @magentoAppArea graphql
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoCache all disabled
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     * @magentoConfigFixture current_store yireo_webp2/settings/enabled 0
     * @magentoConfigFixture current_store dev/static/sign 0
     */
    public function testUrlWebpIsNullWhenWebp2IsDisabled(): void
    {
        $imageData = $this->getFirstImageData();
        $this->assertNull($imageData['url_webp']);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple_with_media_gallery_entries.php
     * @magentoAppArea graphql
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoCache all disabled
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 0
     * @magentoConfigFixture current_store yireo_webp2/settings/enabled 1
     * @magentoConfigFixture current_store dev/static/sign 0
     */
    public function testUrlWebpIsNullWhenNextGenImagesIsDisabled(): void
    {
        $imageData = $this->getFirstImageData();
        $this->assertNull($imageData['url_webp']);
    }

    private function getFirstImageData(): array
    {
        $data = $this->getGraphQlQueryData(file_get_contents(__DIR__ . '/fixtures/product_media_gallery.graphql'));

        $this->assertArrayHasKey('data', $data, var_export($data, true));
        $this->assertArrayNotHasKey('errors', $data, var_export($data, true));
        $this->assertNotEmpty($data['data']['products']['items'], 'No product items found');

        $productData = $data['data']['products']['items'][0];
        $this->assertNotEmpty($productData['media_gallery'], var_export($productData, true));

        return $productData['media_gallery'][0];
    }

    private function getWebpPath(string $imageUrl): string
    {
        $sourceImage = $this->objectManager->get(ImageFactory::class)->createFromUrl($imageUrl);
        return $this->objectManager->get(TargetImageFactory::class)->create($sourceImage, 'webp')->getPath();
    }
}
