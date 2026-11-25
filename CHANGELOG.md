# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.6] - 25 November 2026
### Added
- Support for PHP 8.5

### Fixed
- Security: the `url_webp` GraphQL field no longer converts images on request, so anonymous visitors can no longer exhaust CPU and disk; it only returns an existing, up-to-date WebP URL
- `url_webp` returns null instead of throwing an error, so server paths no longer leak and the exception log is no longer flooded
- `url_webp` now respects the enabled flags of both Yireo_NextGenImages and Yireo_Webp2
- Image source and target paths are confined to pub/media and pub/static
- Missing dependencies on Magento_GraphQl, Magento_CatalogGraphQl and Yireo_NextGenImages are now declared in composer.json and module.xml

### Removed
- Support for PHP 8.1 and older, and for magento/framework 102 (PHP 8.2 - 8.5 and framework 103 are now required)

## [1.0.5] - 20 October 2025
### Fixed
- Copy generic CI/CD files
- PHP 8.4 compatibility

## [1.0.4]
- Earlier releases
