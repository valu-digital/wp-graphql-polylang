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
        default language. This plugin reverts that for the GraphQL api

Root queries

-   `defaultLanguage` get the current default language
-   `languages` list all configured languages

Menu

-   Filter menu items by language

For details please refer to the generated docs in GraphiQL.

## Example

Example showing all features

```graphql
query PolylangExample {
    # Filter pages by language. If not set it defaults to ALL
    pages(where: { language: EN }) {
        nodes {
            title

            # Get language of each page
            language {
                code # Language code
                name # Human readable name of the language
            }

            # Get links to the translates versions of each page
            # This is an array of post objects
            translations {
                title
                link
                language {
                    code
                }
            }
        }
    }

    # Taxonomies such as tags can be filtered like post objects
    tags(where: { language: EN }) {
        nodes {
            name
            language {
                code
                name
            }
        }
    }

    # Get translated version of a given menu
    menuItems(where: { language: EN, location: FOOTER_MENU }) {
        nodes {
            url
        }
    }

    # Get the default language
    defaultLanguage {
        name
        code
    }

    # Get all configured languages
    languages {
        name
        code
    }

    # Get translations for ACF options pages
    # Requires additional plugin "ACF Options For Polylang"
    # https://github.com/BeAPI/acf-options-for-polylang
    siteSettings(language: EN) {
        siteSettings {
            footerTitle
        }
    }
}
```

## Requirements

-   PHP 7.2. We're planning to relax this a bit though
-   [WPGraphQL][] 0.3.x or later
-   Polylang 2.6.5 or later
    -   The free version is enough
    -   If you get the PRO version the pro features such as translated slugs will work too

[pll_context]: https://github.com/polylang/polylang/commit/2203b9e16532797fa530f9b73024b53885d728ef
[polylang-github]: https://github.com/polylang/polylang
[wpgraphql]: https://github.com/wp-graphql/wp-graphql/releases

## Installation

If you use composer you can install it from Packagist

    composer require valu/wp-graphql-polylang

Otherwise you can clone it from Github to your plugins using the stable branch

    cd wp-content/plugins
    git clone --branch stable https://github.com/valu-digital/wp-graphql-polylang.git

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

## Contributing

Checkout [CONTRIBUTING.md](CONTRIBUTING.md)
