<?php

namespace Synolia\Bundle\OroneoBundle\Tests\Unit\Manager;

use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Synolia\Bundle\OroneoBundle\Manager\OroFieldSelectManager;

class OroFieldSelectManagerTest extends WebTestCase
{
    /** @var OroFieldSelectManager */
    protected $fieldSelectManager;

    protected function setUp()
    {
        $entity = new EntityConfigModel();
        $entity->addField(new FieldConfigModel('firstField', 'string'));
        $entity->addField(new FieldConfigModel('secondField', 'string'));
        $entity->addField(new FieldConfigModel('id', 'string'));

        $onlyIdEntity = new EntityConfigModel();
        $onlyIdEntity->addField(new FieldConfigModel('id', 'string'));

        $emptyEntity = new EntityConfigModel();

        $localization = new Localization();
        $localization->setName('test');

        $categories = $this->generateCategories();

        $configManager = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager
            ->method('getConfigEntityModel')
            ->will($this->returnValueMap([
                ['entity',       $entity],
                ['onlyIdEntity', $onlyIdEntity],
                ['emptyEntity',  $emptyEntity],
            ]));

        $localizationHelper = $this
            ->getMockBuilder('Oro\Bundle\LocaleBundle\Helper\LocalizationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $localizationHelper
            ->method('getLocalizations')
            ->will($this->onConsecutiveCalls([], [$localization]));

        $categoryRepository = $this
            ->getMockBuilder('Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->setMethods(['getMasterCatalogRoot', 'getAllChildCategories'])
            ->disableOriginalConstructor()
            ->getMock();

        $categoryRepository
            ->method('getMasterCatalogRoot')
            ->will($this->returnValue($categories[0]));

        $categoryRepository
            ->method('getAllChildCategories')
            ->will($this->returnValue($categories[1]));

        $managerRegistry = $this
            ->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $managerRegistry
            ->method('getService')
            ->will($this->returnValueMap([]));

        $this->fieldSelectManager = new OroFieldSelectManager($configManager, $localizationHelper, $categoryRepository, $managerRegistry);
    }

    public function testGetChoices()
    {
        $this->assertEquals(
            [
                'firstField'  => 'firstField',
                'secondField' => 'secondField',
                'id'          => 'id',
            ],
            $this->fieldSelectManager->getChoices('entity')
        );

        $this->assertEquals(
            [
                'id' => 'id',
            ],
            $this->fieldSelectManager->getChoices('onlyIdEntity')
        );
        $this->assertEquals([], $this->fieldSelectManager->getChoices('emptyEntity'));
    }

    public function testGetLocalizationChoices()
    {
        $this->assertEquals(
            [
                'default' => 'synolia.oroneo.configuration_page.mapping.localization.default.label',
            ],
            $this->fieldSelectManager->getLocalizationChoices()
        );

        $this->assertEquals(
            [
                'default' => 'synolia.oroneo.configuration_page.mapping.localization.default.label',
                'test'    => 'test',
            ],
            $this->fieldSelectManager->getLocalizationChoices()
        );
    }

    public function testGetCategoriesChoices()
    {
        $this->assertEquals(
            [
                1 => 'root',
                2 => '-- title_1_1',
                3 => '---- title_1_1_1',
                4 => '-- title_1_2',
            ],
            $this->fieldSelectManager->getCategoriesChoices()
        );
    }

    protected function generateCategories()
    {
        $idReflection = new \ReflectionProperty(Category::class, 'id');
        $idReflection->setAccessible(true);

        $rootTitle = new LocalizedFallbackValue();
        $rootTitle->setString('root');

        $title_1_1 = new LocalizedFallbackValue();
        $title_1_1->setString('title_1_1');

        $title_1_1_1 = new LocalizedFallbackValue();
        $title_1_1_1->setString('title_1_1_1');

        $title_1_2 = new LocalizedFallbackValue();
        $title_1_2->setString('title_1_2');

        $rootCategory = new Category();
        $rootCategory->addTitle($rootTitle);
        $idReflection->setValue($rootCategory, 1);

        $category_1_1 = new Category();
        $category_1_1
            ->addTitle($title_1_1)
            ->setLevel(1);
        $idReflection->setValue($category_1_1, 2);

        $category_1_1_1 = new Category();
        $category_1_1_1
            ->addTitle($title_1_1_1)
            ->setLevel(2);
        $idReflection->setValue($category_1_1_1, 3);

        $category_1_2 = new Category();
        $category_1_2
            ->addTitle($title_1_2)
            ->setLevel(1);
        $idReflection->setValue($category_1_2, 4);

        return [$rootCategory, [$category_1_1, $category_1_1_1, $category_1_2]];
    }
}
