<?php declare(strict_types=1);

namespace Yireo\Webp2GraphQl\Test\Integration\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Product\MediaGallery\Url as UrlResolver;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\NextGenImages\Image\Image;
use Yireo\NextGenImages\Config\Config as NextGenImagesConfig;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Util\File;
use Yireo\NextGenImages\Util\UrlConvertor;
use Yireo\Webp2\Config\Config as Webp2Config;
use Yireo\Webp2GraphQl\Resolver\WebpUrl;

class WebpUrlTest extends TestCase
{
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        $fileDriver = Bootstrap::getObjectManager()->get(FileDriver::class);
        foreach (array_reverse($this->createdFiles) as $createdFile) {
            if (is_link($createdFile) || $fileDriver->isExists($createdFile)) {
                $fileDriver->deleteFile($createdFile);
            }
        }
    }

    /**
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/target_directory same_as_source
     * @magentoConfigFixture current_store yireo_webp2/settings/enabled 1
     */
    #[DataProvider('getOutsidePaths')]
    public function testUrlWebpIsNullForPathOutsidePublicMedia(string $relativeFolder): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $folder = $objectManager->get(DirectoryList::class)->getRoot().'/'.$relativeFolder;
        $this->createFile($folder.'/webp2graphql-test.webp');

        $this->assertNull($this->resolve($folder.'/webp2graphql-test.jpg'));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getOutsidePaths(): array
    {
        return [
            ['pub/media/../../var'],
        ];
    }

    /**
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/target_directory same_as_source
     * @magentoConfigFixture current_store yireo_webp2/settings/enabled 1
     */
    public function testUrlWebpIsNullForSymlinkPointingOutsidePublicMedia(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $root = $objectManager->get(DirectoryList::class)->getRoot();
        $targetFolder = $root.'/var/webp2graphql-test';
        $this->createFile($targetFolder.'/webp2graphql-test.webp');

        $fileDriver = $objectManager->get(FileDriver::class);
        $link = $root.'/pub/media/webp2graphql-test-link';
        if ($fileDriver->isExists($link)) {
            $fileDriver->deleteFile($link);
        }

        $fileDriver->symlink($targetFolder, $link);
        $this->createdFiles[] = $link;

        $this->assertNull($this->resolve($link.'/webp2graphql-test.jpg'));
    }

    /**
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/enabled 1
     * @magentoConfigFixture current_store yireo_nextgenimages/settings/target_directory same_as_source
     * @magentoConfigFixture current_store yireo_webp2/settings/enabled 1
     */
    public function testUrlWebpIsReturnedForPathInsidePublicMedia(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $folder = $objectManager->get(DirectoryList::class)->getRoot().'/pub/media/webp2graphql-test';
        $this->createFile($folder.'/webp2graphql-test.webp');

        $this->assertStringEndsWith('webp2graphql-test.webp', (string)$this->resolve($folder.'/webp2graphql-test.jpg'));
    }

    private function resolve(string $sourcePath): ?string
    {
        $objectManager = Bootstrap::getObjectManager();
        $sourceImage = $objectManager->create(Image::class, ['path' => $sourcePath, 'url' => 'http://localhost/test.jpg']);

        $urlResolver = $this->createMock(UrlResolver::class);
        $urlResolver->method('resolve')->willReturn('http://localhost/test.jpg');

        $imageFactory = $this->getMockBuilder(ImageFactory::class)
            ->setConstructorArgs([
                $objectManager,
                $objectManager->get(UrlConvertor::class),
            ])
            ->onlyMethods(['createFromUrl'])
            ->getMock();
        $imageFactory->method('createFromUrl')->willReturn($sourceImage);

        $resolver = new WebpUrl(
            $urlResolver,
            $objectManager->get(NextGenImagesConfig::class),
            $objectManager->get(Webp2Config::class),
            $imageFactory,
            $objectManager->get(TargetImageFactory::class),
            $objectManager->get(File::class),
            $objectManager->get(DirectoryList::class),
            $objectManager->get(FileDriver::class)
        );

        return $resolver->resolve($this->createMock(Field::class), null, $this->createMock(ResolveInfo::class));
    }

    private function createFile(string $path): void
    {
        $fileDriver = Bootstrap::getObjectManager()->get(FileDriver::class);
        $fileDriver->createDirectory(dirname($path));
        $fileDriver->filePutContents($path, 'webp');
        $this->createdFiles[] = $path;
    }
}
