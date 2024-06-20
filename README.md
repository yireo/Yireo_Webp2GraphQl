# Yireo Webp2GraphQl
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
