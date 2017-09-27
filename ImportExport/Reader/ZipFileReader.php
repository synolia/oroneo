<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;

/**
 * Class ZipFileReader
 * @package   Synolia\Bundle\OroneoBundle\ImportExport\Reader
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class ZipFileReader extends CsvFileReader
{
    const EXTRACT_FOLDER_NAME = 'product_file_import';

    /**
     * {@inheritdoc}
     */
    protected function getFile()
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        if ($finfo->file($this->fileInfo->getPathname()) == 'application/zip') {
            $isExtracted = false;
            $zipPath     = $this->fileInfo->getPath().DIRECTORY_SEPARATOR.self::EXTRACT_FOLDER_NAME;
            if (!is_dir($zipPath)) {
                @mkdir($zipPath);
            }
            if (null === $this->stepExecution) {
                $isExtracted = $this->unzip($this->fileInfo->getPathName(), $zipPath);
            }

            if (!$isExtracted && null === $this->stepExecution) {
                throw new \RuntimeException(sprintf('Archive %s can\'t be extracted', $this->fileInfo->getFilename()));
            }

            $filename = $this->getCsvFilename($zipPath);
            $this->fileInfo = new \SplFileInfo($zipPath.'/'.$filename);
        }

        return parent::getFile();
    }

    /**
     * @param string $file
     * @param string $path
     *
     * @return bool
     * @throws \RuntimeException
     */
    protected function unzip($file, $path)
    {
        $zip = new \ZipArchive();
        $res = $zip->open($file);

        $zipErrors = [
            \ZipArchive::ER_EXISTS => 'File already exists.',
            \ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
            \ZipArchive::ER_INVAL  => 'Invalid argument.',
            \ZipArchive::ER_MEMORY => 'Malloc failure.',
            \ZipArchive::ER_NOENT  => 'No such file.',
            \ZipArchive::ER_NOZIP  => 'Not a zip archive.',
            \ZipArchive::ER_OPEN   => 'Can\'t open file.',
            \ZipArchive::ER_READ   => 'Read error.',
            \ZipArchive::ER_SEEK   => 'Seek error.',
        ];

        if ($res !== true) {
            throw new \RuntimeException($zipErrors[$res], $res);
        }

        $isExtracted = $zip->extractTo($path);
        if (!$isExtracted) {
            throw new \RuntimeException(sprintf('Pack %s can\'t be extracted', $file));
        }

        $isClosed = $zip->close();
        if (!$isClosed) {
            throw new \RuntimeException(sprintf('Pack %s can\'t be closed', $file));
        }

        return true;
    }

    /**
     * Retrieve the CSV file name from the folder.
     *
     * @param string $path
     *
     * @return string
     */
    protected function getCsvFilename($path)
    {
        $files = scandir($path);
        foreach ($files as $file) {
            $fileInfo = pathinfo($path.$file);
            if (isset($fileInfo['extension']) && $fileInfo['extension'] === 'csv') {
                return $file;
            }
        }

        return '';
    }
}
