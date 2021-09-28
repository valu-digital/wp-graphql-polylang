<?php

class BasicCest
{
    public function testCanSendGET(FunctionalTester $I)
    {
        $query = '
        {
            languages {
                code
            }
        }';

        $query_vars = http_build_query([
            'query' => $query,
        ]);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendGET("/graphql?{$query_vars}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'data' => [
                'languages' => [
                    [
                        'code' => 'EN',
                    ],
                    [
                        'code' => 'FI',
                    ],
                    [
                        'code' => 'SV',
                    ],
                ],
            ],
        ]);
    }

    public function testCanSendPOST(FunctionalTester $I)
    {
        $query = '
        {
            languages {
                code
            }
        }';

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/graphql', [
            'query' => $query,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'data' => [
                'languages' => [
                    [
                        'code' => 'EN',
                    ],
                    [
                        'code' => 'FI',
                    ],
                    [
                        'code' => 'SV',
                    ],
                ],
            ],
        ]);
    }

    /**
     * wp-graphql sends graphql requests to index.php?graphql
     */
    public function testCanSendPOSTToGraphiql(FunctionalTester $I)
    {
        $query = '
        {
            languages {
                code
            }
        }';

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/index.php?graphql', [
            'query' => $query,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'data' => [
                'languages' => [
                    [
                        'code' => 'EN',
                    ],
                    [
                        'code' => 'FI',
                    ],
                    [
                        'code' => 'SV',
                    ],
                ],
            ],
        ]);
    }

    public function testCanQuerySingleLanguageSwedish(FunctionalTester $I)
    {
        $query = '
        {
            posts(where: {language: SV}) {
                nodes {
                    language {
                      code
                    }
                    title
                }
              }
        }';

        $query_vars = http_build_query([
            'query' => $query,
        ]);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendGET("/graphql?{$query_vars}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'data' => [
                'posts' => [
                    'nodes' => [
                        [
                            'language' => [
                                'code' => 'SV',
                            ],
                            'title' => 'Svenska',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testCanQueryByMultipleLanguages(FunctionalTester $I)
    {
        $query = '
        {
            posts(where: {languages: [FI, SV]}) {
                nodes {
                    language {
                      code
                    }
                    title
                }
              }
        }';

        $query_vars = http_build_query([
            'query' => $query,
        ]);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendGET("/graphql?{$query_vars}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'data' => [
                'posts' => [
                    'nodes' => [
                        [
                            'language' => [
                                'code' => 'SV',
                            ],
                            'title' => 'Svenska',
                        ],
                        [
                            'language' => [
                                'code' => 'FI',
                            ],
                            'title' => 'Suomalainen',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
