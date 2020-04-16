<?php

namespace WPGraphQLGutenberg\Schema\Types\InterfaceType;

use WPGraphQLGutenberg\Schema\Utils;

use GraphQL\Error\ClientAware;
use WPGraphQLGutenberg\Blocks\PostMeta;
use WPGraphQLGutenberg\PostTypes\BlockEditorPreview;

class StaleContentException extends \Exception implements ClientAware
{
    public function isClientSafe()
    {
        return true;
    }

    public function getCategory()
    {
        return 'gutenberg';
    }
}

class BlockEditorContentNode
{
    private static function validate($model, $data)
    {
        if (empty($data)) {
            throw new StaleContentException(__('Blocks content is not sourced.', 'wp-graphql-gutenberg'));
        }

        if (PostMeta::is_data_stale($model, $data)) {
            throw new StaleContentException(__('Blocks content is stale.', 'wp-graphql-gutenberg'));
        }
    }

    public static function get_config($type_registry)
    {
        return             [
            'description' => __(
                'Gutenberg post interface',
                'wp-graphql-gutenberg'
            ),
            'fields' => [
                'blocks' => [
                    'type' => [
                        'list_of' => ['non_null' => 'Block']
                    ],
                    'description' => 'Gutenberg blocks',
                    'resolve' => function ($model) {
                        $data = \WPGraphQLGutenberg\Blocks\PostMeta::get_post($model->ID);
                        self::validate($model, $data);

                        return $data['blocks'];
                    }
                ],
                'blocksJSON' => [
                    'type' => 'String',
                    'description' => 'Gutenberg blocks as json string',
                    'resolve' => function ($model) {
                        $data = \WPGraphQLGutenberg\Blocks\PostMeta::get_post($model->ID);
                        self::validate($model, $data);
                        return json_encode($data['blocks']);
                    }
                ],
                'previewBlocks' => [
                    'type' => [
                        'list_of' => ['non_null' => 'Block']
                    ],
                    'description' => 'Gutenberg blocks as previewed',
                    'resolve' => function ($model, $args, $context, $info) {

                        $a = $context->config['gutenberg']['server']->gutenberg_fields_in_query();

                        $id = BlockEditorPreview::get_preview_id($model->ID, $model->ID);

                        if (!empty($id)) {
                            return PostMeta::get_post($id)['blocks'];
                        }

                        return null;
                    }
                ],
                'previewBlocksJSON' => [
                    'type' => 'String',
                    'description' => 'Gutenberg blocks as previewed as json string',
                    'resolve' => function ($model) {
                        $id = BlockEditorPreview::get_preview_id($model->ID, $model->ID);

                        if (!empty($id)) {
                            return json_encode(PostMeta::get_post($id)['blocks']);
                        }

                        return null;
                    }
                ]
            ],
            'resolveType' => function ($model) use ($type_registry) {
                return $type_registry->get_type(Utils::get_post_graphql_type($model, $type_registry));
            }
        ];
    }

    public static function register_type($type_registry)
    {
        register_graphql_interface_type(
            'BlockEditorContentNode',
            self::get_config($type_registry)
        );
    }
}
