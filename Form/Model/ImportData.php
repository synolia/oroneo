<?php

namespace Synolia\Bundle\OroneoBundle\Form\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ImportData
 * @package   Synolia\Bundle\OroneoBundle\Form\Model
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class ImportData
{
    /**
     * @var UploadedFile
     *
     * @Assert\File(mimeTypes = {"text/plain", "text/csv", "application/zip"})
     */
    protected $file;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    protected $processorAlias;

    /**
     * @param UploadedFile $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $processorAlias
     */
    public function setProcessorAlias($processorAlias)
    {
        $this->processorAlias = $processorAlias;
    }

    /**
     * @return string
     */
    public function getProcessorAlias()
    {
        return $this->processorAlias;
    }
}
