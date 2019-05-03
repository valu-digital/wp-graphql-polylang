# WPGraphQL Polylang Extension (ALPHA)

Extend [WPGraphQL](https://www.wpgraphql.com/) schema with language data from
the [Polylang](https://polylang.pro/) plugin.

This plugin currently require wp-graphq 0.2.x but 0.3.x is in the works [here](https://github.com/valu-digital/wp-graphql-polylang/pull/1)

## Features

For posts and terms (custom ones too!)

-   Adds `language` and `translations` fields
-   Filter with a `language` where argument
-   Set the language on create and update mutations
-   Show all translations in the api by default
    -   Polylang patches the WP Query to only list items with the current
        language. This plugin reverts that for the GraphQL api
    -   This a bit hack currently because Polylang doesn't have good API to
        customize the `$polylang` global
        ([yet](https://github.com/polylang/polylang/pull/340))

Root queries

-   `defaultLanguage` get the current default language
-   `languages` list all configured languages

For details please refer to the generated docs in GraphiQL.
