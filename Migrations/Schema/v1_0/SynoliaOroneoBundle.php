<?php

namespace Synolia\Bundle\OroneoBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Class SynoliaOroneoBundle
 * @package   Synolia\Bundle\OroneoBundle\Migrations\Schema\v1_0
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class SynoliaOroneoBundle implements Migration
{
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
