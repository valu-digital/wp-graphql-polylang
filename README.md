# WPGraphQL Polylang Extension (ALPHA)

Extend [WPGraphQL](https://www.wpgraphql.com/) schema with language data from
the [Polylang](https://polylang.pro/) plugin.

## Features

For posts and terms (custom ones too!)

-   Adds `language` and `translations` fields
-   Filter with a `language` where argument
-   Set the language on create and update mutations
-   Show all translations in the api by default
    -   Polylang patches the WP Query to only list items with the default
        language. This plugin reverts that for the GraphQL api

Root queries

-   `defaultLanguage` get the current default language
-   `languages` list all configured languages

For details please refer to the generated docs in GraphiQL.
