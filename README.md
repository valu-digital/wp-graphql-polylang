# WPGraphQL Polylang Extension

[![Build Status](https://travis-ci.org/valu-digital/wp-graphql-polylang.svg?branch=master)](https://travis-ci.org/valu-digital/wp-graphql-polylang)

Extend [WPGraphQL](https://www.wpgraphql.com/) schema with language data from
the [Polylang](https://polylang.pro/) plugin.

## Features

For posts and terms (custom ones too!)

-   Adds `language` and `translations` fields
-   Filter with a `language` where argument
-   Set the language on create and update mutations
-   Show all translations in the api by default
    -   Polylang patches the WP Query to only list items with the current
        language. This plugin reverts that for the GraphQL api

Root queries

-   `defaultLanguage` get the current default language
-   `languages` list all configured languages

For details please refer to the generated docs in GraphiQL.

## Example

```graphql
query GET_EN_PAGES {
    pages(where: { language: EN }) {
        nodes {
            title
            language {
                name
                slug
            }
            translations {
                title
                language {
                    name
                }
            }
        }
    }
}
```

## Requirements

-   PHP 7.2. We're planning to relax this a bit though
-   [WPGraphQL][] 0.3.x (0.4.x support coming soon!)
-   Polylang 2.6.5 or later
    -   The free version is enough
    -   If you get the PRO version the pro features such as translated slugs will work too

[pll_context]: https://github.com/polylang/polylang/commit/2203b9e16532797fa530f9b73024b53885d728ef
[polylang-github]: https://github.com/polylang/polylang
[wpgraphql]: https://github.com/wp-graphql/wp-graphql/releases

## Slack

You can find us from the [WPGraphQL Slack][slack] on the `#polylang` channel.

[slack]: https://wpgql-slack.herokuapp.com/

## WPML

But I'm using WPML?!

AFAIK there's no WPML integration for WPGraphQL yet.
This plugin is probably a good reference on how to do it if you are interested in creating one.
Please let me know if you do so I can link to it here 😊

Meanwhile you might want to checkout migration docs

<https://polylang.pro/how-to-switch-from-wpml-to-polylang/>
