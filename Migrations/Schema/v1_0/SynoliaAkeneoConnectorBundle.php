<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Class SynoliaAkeneoConnectorBundle
 */
class SynoliaAkeneoConnectorBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_catalog_category');
        $table->addColumn(
            "akeneoCategoryCode",
            "string",
            [
                'length' => 255,
                "oro_options" => [
                    "extend" => ["is_extend" => true, "owner" => ExtendScope::OWNER_CUSTOM],
                    "datagrid" => ["is_visible" => false],
                    'importexport' => ['identity' => true],
                ],
            ]
        );
        $table->addUniqueIndex(['akeneoCategoryCode']);
    }
}
