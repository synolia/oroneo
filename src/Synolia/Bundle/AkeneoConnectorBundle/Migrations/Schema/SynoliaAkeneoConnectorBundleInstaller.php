<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Class SynoliaAkeneoConnectorBundleInstaller
 */
class SynoliaAkeneoConnectorBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

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
