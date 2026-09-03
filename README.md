# Yireo Webp2GraphQl

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-webp2-graph-ql)
<!-- badges.specs.end -->

**Magento 2 module for extending [Yireo Webp2](https://github.com/yireo/Yireo_Webp2) with GraphQL support**

### Installation
```bash
composer require yireo/magento2-webp2-graph-ql
bin/magento module:enable Yireo_Webp2GraphQl
```

## Usage in GraphQL
For every regular image URL, append a `_webp` variant to your query:
```graphql
query {
  products(search:"a", pageSize: 1){
    items {
      sku
      name
      media_gallery {
        url
        url_webp
      }
    }
  }
}
```

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Webp2GraphQl/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Webp2GraphQl/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Webp2GraphQl/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Webp2GraphQl/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_Webp2GraphQl/compile.yml?label=compile)
<!-- badges.test.end -->
