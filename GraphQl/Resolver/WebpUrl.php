<?php declare(strict_types=1);

namespace Yireo\Webp2GraphQl\GraphQl\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Product\MediaGallery\Url as UrlResolver;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Yireo\NextGenImages\Config\Config;
use Yireo\NextGenImages\Exception\ConvertorException;
use Yireo\NextGenImages\Image\ImageFactory;
use Yireo\Webp2\Convertor\Convertor;

class WebpUrl implements ResolverInterface
{
    /**
     * @var UrlResolver
     */
    private $urlResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var Convertor
     */
    private $convertor;

    /**
     * @param UrlResolver $urlResolver
     * @param Config $config
     * @param ImageFactory $imageFactory
     * @param Convertor $convertor
     */
    public function __construct(
        UrlResolver $urlResolver,
        Config $config,
        ImageFactory $imageFactory,
        Convertor $convertor
    ) {
        $this->urlResolver = $urlResolver;
        $this->config = $config;
        $this->imageFactory = $imageFactory;
        $this->convertor = $convertor;
    }

    /**
     * Resolve this GraphQL request
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed|string
     * @throws ConvertorException
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $imageUrl = $this->urlResolver->resolve($field, $context, $info, $value, $args);

        if (!$this->config->enabled()) {
            return '';
        }

        $sourceImage = $this->imageFactory->createFromUrl($imageUrl);
        return $this->convertor->convertImage($sourceImage)->getUrl();
    }
}
