<?php

namespace Synolia\Bundle\OroneoBundle\ImportExport\Reader;

use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;

/**
 * Class ZipFileReader
 * @package Synolia\Bundle\OroneoBundle\ImportExport\Reader
 */
class ZipFileReader extends CsvFileReader
{
    /**
     * {@inheritdoc}
     */
    protected function getFile()
    {
        $file = $this->file;
        $fileInfo = $this->fileInfo;

        if ($fileInfo->getExtension() == 'zip') {
            $zipPath = substr($fileInfo->getRealPath(), 0, -4);
            $isExtracted = $this->unzip($fileInfo->getPathName(), $zipPath);
            if (!$isExtracted) {
                throw new \RuntimeException(sprintf('Archive %s can\'t be extracted', $fileInfo->getFilename()));
            }
            $file = new \SplFileObject($zipPath.'/product.csv');
            $fileInfo = new \SplFileInfo($zipPath.'/product.csv');
        } elseif ($fileInfo->getExtension() == 'csv' && !$file instanceof \SplFileObject) {
            $file = new \SplFileObject($fileInfo->getRealPath().'/product.csv');
            $fileInfo = new \SplFileInfo($fileInfo->getRealPath().'/product.csv');
        } else {
            return parent::getFile();
        }

        $file->setFlags(
            \SplFileObject::READ_CSV |
            \SplFileObject::READ_AHEAD |
            \SplFileObject::DROP_NEW_LINE
        );
        $file->setCsvControl(
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );
        if ($this->firstLineIsHeader && !$this->header) {
            $this->header = $file->fgetcsv();
        }

        $this->file = $file;
        $this->fileInfo = $fileInfo;

        return $this->file;
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
