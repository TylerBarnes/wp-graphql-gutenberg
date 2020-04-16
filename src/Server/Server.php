<?php

namespace WPGraphQLGutenberg\Server;

use GraphQL\GraphQL;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\Visitor;
use WPGraphQLGutenberg\Blocks\Registry;

// use WPGraphQLGutenberg\Blocks\PostMeta;
// use WPGraphQLGutenberg\Blocks\Registry;
// use WPGraphQLGutenberg\PostTypes\BlockEditorPreview;
// use WPGraphQLGutenberg\Schema\Utils;


if (!defined('WP_GRAPHQL_GUTENBERG_SERVER_URL')) {
    define('WP_GRAPHQL_GUTENBERG_SERVER_URL', null);
}

if (!defined('WP_GRAPHQL_GUTENBERG_ENABLE_SERVER')) {
    define('WP_GRAPHQL_GUTENBERG_ENABLE_SERVER', !empty(WP_GRAPHQL_GUTENBERG_SERVER_URL));
}

class Server
{
    public function enabled()
    {
        return WP_GRAPHQL_GUTENBERG_ENABLE_SERVER && WP_GRAPHQL_GUTENBERG_SERVER_URL;
    }

    public function url()
    {
        return WP_GRAPHQL_GUTENBERG_SERVER_URL;
    }

    public function gutenberg_fields_in_query()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $context = ['result' => false];

        Visitor::visit(Parser::parse(new Source($this->query, 'GraphQL')), [
            NodeKind::FIELD => [
                'enter' => function ($definition) use (&$context) {
                    if (in_array($definition->name->value, ['blocks', 'previewBlocks', '__schema'])) {
                        $context['result'] = true;
                        return Visitor::stop();
                    }

                    return null;
                }
            ]
        ]);

        $cache = $context['result'];
        return $context['result'];
    }

    function __construct()
    {
        add_filter('graphql_request_data', function ($request_data) {
            $this->query = $request_data['query'];
            return $request_data;
        });
    }
}
