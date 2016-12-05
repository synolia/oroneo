<?php

namespace Synolia\Bundle\OroneoBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Class SynoliaOroneoBundleInstaller
 */
class SynoliaOroneoBundleInstaller implements Installation
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
        $table = $schema->getTable('oro_catalog_category');
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
