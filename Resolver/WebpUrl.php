<?php declare(strict_types=1);

namespace Yireo\Webp2GraphQl\Resolver;

use LogicException;
use Magento\CatalogGraphQl\Model\Resolver\Product\MediaGallery\Url as UrlResolver;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Yireo\NextGenImages\Config\Config as NextGenImagesConfig;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\NextGenImages\Image\TargetImageFactory;
use Yireo\NextGenImages\Util\File;
use Yireo\Webp2\Config\Config as Webp2Config;

class WebpUrl implements ResolverInterface
{
    /**
     * @param UrlResolver $urlResolver
     * @param NextGenImagesConfig $nextGenImagesConfig
     * @param Webp2Config $webp2Config
     * @param ImageFactory $imageFactory
     * @param TargetImageFactory $targetImageFactory
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FileDriver $fileDriver
     */
    public function __construct(
        private readonly UrlResolver $urlResolver,
        private readonly NextGenImagesConfig $nextGenImagesConfig,
        private readonly Webp2Config $webp2Config,
        private readonly ImageFactory $imageFactory,
        private readonly TargetImageFactory $targetImageFactory,
        private readonly File $file,
        private readonly DirectoryList $directoryList,
        private readonly FileDriver $fileDriver
    ) {
    }

    /**
     * Return the URL of an existing and up-to-date WebP image, without converting anything
     *
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return string|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (!$this->nextGenImagesConfig->enabled() || !$this->webp2Config->enabled()) {
            return null;
        }

        try {
            $imageUrl = $this->urlResolver->resolve($field, $context, $info, $value, $args);
            if (!is_string($imageUrl) || $imageUrl === '') {
                return null;
            }

            $sourceImage = $this->imageFactory->createFromUrl($imageUrl);
            if (!$this->isAllowedPath($sourceImage->getPath(), ['jpg', 'jpeg', 'png'])) {
                return null;
            }

            $webpImage = $this->targetImageFactory->create($sourceImage, 'webp');
            if (!$this->isAllowedPath($webpImage->getPath(), ['webp'])) {
                return null;
            }

            if (!$this->file->fileExists($webpImage->getPath())
                || $this->file->needsConversion($sourceImage->getPath(), $webpImage->getPath())) {
                return null;
            }

            return $webpImage->getUrl();
        } catch (ConvertorException|LocalizedException|LogicException $exception) {
            return null;
        }
    }

    /**
     * Check whether a path has an allowed extension and is located inside the public media or static folder
     *
     * @param string $path
     * @param string[] $allowedExtensions
     * @return bool
     * @throws FileSystemException
     */
    private function isAllowedPath(string $path, array $allowedExtensions): bool
    {
        if ($path === '' || in_array('..', explode('/', $path), true)) {
            return false;
        }

        $extension = strtolower(substr((string)strrchr($path, '.'), 1));
        if (!in_array($extension, $allowedExtensions, true)) {
            return false;
        }

        // phpcs:ignore
        $realFolder = $this->fileDriver->getRealPath(dirname($path));
        if (!is_string($realFolder) || $realFolder === '') {
            return false;
        }

        foreach ($this->getAllowedFolders() as $allowedFolder) {
            $allowedFolder = $this->fileDriver->getRealPath($allowedFolder);
            if (!is_string($allowedFolder) || $allowedFolder === '') {
                continue;
            }

            $allowedFolder = rtrim($allowedFolder, '/');
            if ($realFolder === $allowedFolder || str_starts_with($realFolder, $allowedFolder.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the folders that images are allowed to be located in
     *
     * @return string[]
     * @throws FileSystemException
     */
    private function getAllowedFolders(): array
    {
        return [
            $this->directoryList->getPath(DirectoryList::MEDIA),
            $this->directoryList->getPath(DirectoryList::STATIC_VIEW),
            $this->directoryList->getRoot().'/pub/media',
            $this->directoryList->getRoot().'/pub/static',
        ];
    }
}
