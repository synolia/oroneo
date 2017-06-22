<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;

/**
 * Class ZipFileReader
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Reader
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
            $zipPath     = $this->fileInfo->getPath().DIRECTORY_SEPARATOR.self::EXTRACT_FOLDER_NAME;
            $isExtracted = $this->unzip($this->fileInfo->getPathName(), $zipPath);

            if (!$isExtracted) {
                throw new \RuntimeException(sprintf('Archive %s can\'t be extracted', $this->fileInfo->getFilename()));
            }

            $this->fileInfo = new \SplFileInfo($zipPath.'/product.csv');
        } elseif ($this->fileInfo->getExtension() == 'csv' && !$this->file instanceof \SplFileObject) {
            $this->fileInfo = new \SplFileInfo($this->fileInfo->getRealPath().'/product.csv');
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
}
