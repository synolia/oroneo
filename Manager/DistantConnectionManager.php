<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Synolia\Bundle\OroneoBundle\Helper\FtpHelper;
use Synolia\Bundle\OroneoBundle\Helper\SftpHelper;

/**
 * Class DistantConnectionManager
 * @package   Synolia\Bundle\OroneoBundle\Manager
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class DistantConnectionManager
{
    const ZIP_MIMETYPE = 'application/zip';
    const CSV_MIMETYPE = 'text/csv';

    /** @var  FtpHelper $ftpHelper */
    protected $ftpHelper;

    /** @var  SftpHelper $sftpHelper */
    protected $sftpHelper;

    /** @var  string $uploadDir */
    protected $uploadDir;

    /**
     * DistantConnectionManager constructor.
     *
     * @param FtpHelper  $ftpHelper
     * @param SftpHelper $sftpHelper
     * @param string     $uploadDir
     */
    public function __construct(FtpHelper $ftpHelper, SftpHelper $sftpHelper, $uploadDir)
    {
        $this->ftpHelper  = $ftpHelper;
        $this->sftpHelper = $sftpHelper;
        $this->uploadDir  = $uploadDir;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param string $port
     * @param string $filename
     *
     * @return null|File
     * @throws \Exception
     */
    public function ftpImport($username, $password, $host, $port, $filename)
    {
        $connection = $this->ftpHelper->setParameters(
            $username,
            $password,
            $host,
            $port,
            '.',
            null,
            true
        );

        try {
            // load distant content.
            $connection->getFolderContent();

            // Create local dir if doesn't exist.
            $this->checkLocalDir();

            // Retrieve filename config depending on the processorAlias.
            $isFile = $connection->read($filename, $this->uploadDir.$filename);

            if (!$isFile) {
                return null;
            }

            $mimeType = $this->getMimeType($filename);

            return new UploadedFile($this->uploadDir.$filename, $filename, $mimeType);
        } catch (\Exception $exception) {
            throw new \Exception('Error with FTP connection: '.$exception->getMessage());
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param string $port
     * @param string $filename
     *
     * @return null|File
     * @throws \Exception
     */
    public function sftpImport($username, $password, $host, $port, $filename)
    {
        $connection = $this->sftpHelper->setParameters(
            $username,
            $password,
            $host,
            $port,
            '.',
            null,
            false
        );

        try {
            // load distant content.
            $connection->getFolderContent();

            // Create local dir if doesn't exist.
            $this->checkLocalDir();

            // Retrieve filename config depending on the processorAlias.
            $isFile = $connection->isImportedFile($filename, $this->uploadDir.$filename);

            if (!$isFile) {
                throw new \Exception('File "'.$filename.'" not found on the remote server.');
            }

            $mimeType = $this->getMimeType($filename);

            return new UploadedFile($this->uploadDir.$filename, $filename, $mimeType);
        } catch (\Exception $exception) {
            throw new \Exception('Error with SFTP connection: '.$exception->getMessage());
        }
    }

    /**
     * Get filename extension to define the correct mimeType.
     *
     * @param string $filename
     *
     * @return string MimeType 'text/csv' by default.
     */
    protected function getMimeType($filename)
    {
        if (is_string($filename)) {
            $extension =  substr(strrchr($filename, '.'), 1);
            if ($extension == ImportManager::ZIP_FORMAT) {
                return self::ZIP_MIMETYPE;
            }
        }

        return self::CSV_MIMETYPE;
    }

    /**
     * Check if Oroneo upload dir exists.
     * If it does not, then create it.
     */
    protected function checkLocalDir()
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir);
        }
    }
}
