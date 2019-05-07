# WPGraphQL Polylang Extension (ALPHA)

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

## Requirements

-   [WPGraphlQL][] 0.3.x
-   [Polylang][] from the github master
    -   Stable releases will work once [pll_context][] filter ships
    -   The free version is enough

[pll_context]: https://github.com/polylang/polylang/commit/2203b9e16532797fa530f9b73024b53885d728ef
[polylang]: https://github.com/polylang/polylang
[wpgraphlql]: https://github.com/wp-graphql/wp-graphql/releases

## Slack

You can find us from the [WPGraphQL Slack][slack] on the `#polylang` channel.

[slack]: https://wpgql-slack.herokuapp.com/
