<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

/**
 * Class ProductFileStrategy
 */
class ProductFileStrategy extends ProductStrategy
{
    /** @var FieldConfigModel[][] */
    protected $fields;

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        $entity = parent::afterProcessEntity($entity);

        if ($entity) {
            $this->setProductFiles($entity);
        }

        return $entity;
    }

    /** {@inheritdoc} */
    protected function beforeProcessEntity($entity)
    {
        $existingEntity = $this->findExistingEntity($entity);
        if (!$existingEntity) {
            return null;
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param Product $entity
     */
    protected function setProductFiles($entity)
    {
        $itemData      = $this->context->getValue('itemData');
        $fields        = array_merge($this->getProductFieldsByType('image'), $this->getProductFieldsByType('file'));
        $configuration = $this->context->getConfiguration();
        $filePath      = substr($configuration['filePath'], 0, -4);

        foreach ($fields as $field) {
            /** @var FieldConfigModel $field */
            $fieldName = $field->getFieldName();
            if (isset($itemData[$fieldName])) {
                $file = null;

                if (!empty($itemData[$fieldName])) {
                    $componentFile = new ComponentFile($filePath.'/'.$itemData[$fieldName]);
                    $file          = $this->fieldHelper->getObjectValue($entity, $fieldName);

                    if ($file == null
                        || md5_file($file->getFile()->getPathname()) != md5_file($componentFile->getPathname())
                    ) {
                        $file = new File();
                        $file->setFile($componentFile);
                    }
                }
                $this->fieldHelper->setObjectValue($entity, $fieldName, $file);
            }
        }
    }

    /**
     * Get the product fields of a given type
     *
     * @param string $type
     *
     * @return FieldConfigModel[]
     */
    protected function getProductFieldsByType($type)
    {
        if (!isset($this->fields[$type])) {
            $product             = $this->configManager->getConfigEntityModel(Product::class);
            $this->fields[$type] = $product->getFields(function (FieldConfigModel $field) use ($type) {
                return $field->getType() == $type;
            })->toArray();
        }

        return $this->fields[$type];
    }
}
