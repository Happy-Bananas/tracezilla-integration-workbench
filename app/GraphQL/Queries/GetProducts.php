<?php

namespace App\GraphQL\Queries;

class GetProducts
{
    public const QUERY = <<<'GRAPHQL'
query Products($first: Int!) {
  products(first: $first) {
    nodes {
      id
      title
      handle

      featuredImage {
        url
        altText
      }

      images(first: 10) {
        nodes {
          url
          altText
        }
      }

      variants(first: 100) {
        nodes {
          id
          sku
          barcode

          media(first: 1) {
            nodes {
              preview {
                image {
                  url
                  altText
                }
              }
            }
          }
        }
      }
    }
  }
}
GRAPHQL;
}