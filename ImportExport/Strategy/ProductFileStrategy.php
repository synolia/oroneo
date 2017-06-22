<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Strategy;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Synolia\Bundle\OroneoBundle\ImportExport\Reader\ZipFileReader;

/**
 * Class ProductFileStrategy
 */
class ProductFileStrategy extends ProductStrategy
{
    /** @var ConfigManager */
    protected $globalConfigManager;

    /** @var AttachmentProvider */
    protected $attachmentProvider;

    /** @var FileManager */
    protected $fileManager;

    /** @var array */
    protected $imageTypes = [];

    /**
     * @param ConfigManager $globalConfigManager
     */
    public function setGlobalConfigManager(ConfigManager $globalConfigManager)
    {
        $this->globalConfigManager = $globalConfigManager;
    }

    /**
     * @param AttachmentProvider $attachmentProvider
     */
    public function setAttachmentProvider(AttachmentProvider $attachmentProvider)
    {
        $this->attachmentProvider = $attachmentProvider;
    }

    /**
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param string $imageType
     * @param bool   $isMultiple
     */
    public function addImageType($imageType, $isMultiple = false)
    {
        if (!isset($this->imageTypes[$imageType])) {
            $this->imageTypes[$imageType] = $isMultiple;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function afterProcessEntity($entity)
    {
        $entity = parent::afterProcessEntity($entity);

        if ($entity) {
            $this->updateEntityImages($entity);
            $this->updateEntityAttachments($entity);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        $existingEntity = $this->findExistingEntity($entity);
        if (!$existingEntity) {
            return null;
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * All non file fields are excluded during this import
     */
    protected function isFieldExcluded($entityName, $fieldName, $itemData = null)
    {
        if ($entityName == $this->productClass && $fieldName != 'images') {
            return true;
        }

        return parent::isFieldExcluded($entityName, $fieldName, $itemData);
    }

    /**
     * @param Product $entity
     */
    protected function updateEntityImages($entity)
    {
        /** @var ProductImage[] $oldImageHashes */
        $oldImageHashes = [];

        foreach ($entity->getImages() as $productImage) {
            $content = $this->fileManager->getFileContent($productImage->getImage()->getFilename());
            $hash    = md5($content);
            $oldImageHashes[$hash] = $productImage;
        }

        $mappings      = $this->getImageMappingsWithTypes();
        $itemData      = $this->context->getValue('itemData');
        $configuration = $this->context->getConfiguration();
        $filePath      = dirname($configuration['filePath']).DIRECTORY_SEPARATOR.ZipFileReader::EXTRACT_FOLDER_NAME;

        foreach ($mappings as $mapping => $types) {
            if (!isset($itemData[$mapping])) {
                continue;
            }

            $imagePath = $filePath.DIRECTORY_SEPARATOR.$itemData[$mapping];
            $hash      = md5_file($imagePath);

            if (isset($oldImageHashes[$hash])) {
                $productImage = $oldImageHashes[$hash];

                foreach ($productImage->getTypes() as $type) {
                    if (!isset($types[$type])) {
                        $productImage->removeType($type);
                    } else {
                        unset($types[$type]);
                    }
                }

                unset($oldImageHashes[$hash]);
            } else {
                $pathInfo = pathinfo($imagePath);

                $file = new File();
                $file->setFile(new UploadedFile($imagePath, $pathInfo['basename'], mime_content_type($imagePath)));

                $productImage = new ProductImage();
                $productImage->setImage($file);

                $entity->addImage($productImage);
            }

            foreach ($types as $type => $bool) {
                $productImage->addType($type);
            }
        }

        foreach ($oldImageHashes as $productImage) {
            $entity->removeImage($productImage);
        }
    }

    /**
     * @param Product $entity
     */
    protected function updateEntityAttachments($entity)
    {
        $attachmentManager = $this->strategyHelper->getEntityManager('OroAttachmentBundle:Attachment');

        $currentAttachments = $this->attachmentProvider->getEntityAttachments($entity);

        /** @var Attachment[] $currentAttachmentsHash */
        $currentAttachmentsHash = [];

        foreach ($currentAttachments as $currentAttachment) {
            $content = $this->fileManager->getFileContent($currentAttachment->getFile()->getFilename());
            $hash    = md5($content);
            $currentAttachmentsHash[$hash] = $currentAttachment;
        }

        $mappings = $this->globalConfigManager->get('synolia_oroneo.product_attachment');
        $mappings = explode(',', $mappings);
        $itemData      = $this->context->getValue('itemData');
        $configuration = $this->context->getConfiguration();
        $filePath      = dirname($configuration['filePath']).DIRECTORY_SEPARATOR.ZipFileReader::EXTRACT_FOLDER_NAME;

        foreach ($mappings as $mapping) {
            if (!isset($itemData[$mapping])) {
                continue;
            }

            $imagePath = $filePath.DIRECTORY_SEPARATOR.$itemData[$mapping];
            $hash      = md5_file($imagePath);

            if (isset($currentAttachmentsHash[$hash])) {
                $currentAttachmentsHash[$hash]->setTarget($entity);
                $attachmentManager->persist($currentAttachmentsHash[$hash]);
            } else {
                $pathInfo = pathinfo($imagePath);

                $file = new File();
                $file->setFile(new UploadedFile($imagePath, $pathInfo['basename'], mime_content_type($imagePath)));

                $attachment = new Attachment();
                $attachment
                    ->setFile($file)
                    ->setTarget($entity);

                $attachmentManager->persist($attachment);
            }
        }
    }

    /**
     * @return array
     */
    protected function getImageMappingsWithTypes()
    {
        $mappings = [];

        foreach ($this->imageTypes as $type => $isMultiple) {
            $mapping = $this->globalConfigManager->get('synolia_oroneo.product_image_'.$type);

            if (!$mapping) {
                continue;
            }

            if ($isMultiple) {
                $imageMappings = explode(',', $mapping);
            } else {
                $imageMappings = [$mapping];
            }

            foreach ($imageMappings as $imageMapping) {
                if (!isset($mappings[$imageMapping])) {
                    $mappings[$imageMapping] = [];
                }

                $mappings[$imageMapping][$type] = true;
            }
        }

        return $mappings;
    }
}
