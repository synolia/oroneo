<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Synolia\Bundle\AkeneoConnectorBundle\Service\ImportService;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('synolia_akeneo_connector');

        SettingsBuilder::append(
            $rootNode,
            [
                'delimiter'            => ['value' => ';'],
                'enclosure'            => ['value' => '"'],
                'localization_mapping' => ['type' => 'array', 'value' => []],
                'category_mapping'     => ['type' => 'array', 'value' => []],
                'attribute_mapping'    => ['type' => 'array', 'value' => []],
                'option_mapping'       => ['type' => 'array', 'value' => []],
                'product_channel'      => ['value' => 'ecommerce'],
                'product_mapping'      => ['type' => 'array', 'value' => []],
                'jobs' => [
                    'type'  => 'array',
                    'value' => [
                        'category'  => [
                            'import_file' => 'app/Resources/imports/category.csv',
                            'processor'   => ImportService::CATEGORY_PROCESSOR,
                        ],
                        'attribute' => [
                            'import_file' => 'app/Resources/imports/attribute.csv',
                            'batch_job'   => ImportService::ATTRIBUTE_JOB,
                            'processor'   => ImportService::ATTRIBUTE_PROCESSOR,
                        ],
                        'option'    => [
                            'import_file' => 'app/Resources/imports/option.csv',
                            'processor'   => ImportService::OPTION_PROCESSOR,
                        ],
                        'product'   => [
                            'import_file' => 'app/Resources/imports/product.csv',
                            'processor'   => ImportService::PRODUCT_PROCESSOR,
                        ],
                        'product-file'   => [
                            'import_file'  => 'app/Resources/imports/imports.zip',
                            'batch_job'    => ImportService::PRODUCT_FILE_JOB,
                            'processor'    => ImportService::PRODUCT_FILE_PROCESSOR,
                            'input_format' => 'zip',
                        ],
                    ],
                ]
            ]
        );

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
