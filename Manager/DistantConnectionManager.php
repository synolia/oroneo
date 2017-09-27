<?php

namespace Synolia\Bundle\OroneoBundle\Manager;

use Gaufrette\Adapter\Ftp;
use Gaufrette\Adapter\Local;
use Gaufrette\Adapter\PhpseclibSftp;
use Gaufrette\Filesystem;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use phpseclib\Net\SFTP;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class DistantConnectionManager
 * @package   Synolia\Bundle\OroneoBundle\Manager
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class DistantConnectionManager
{
    const ZIP_MIMETYPE    = 'application/zip';
    const CSV_MIMETYPE    = 'text/csv';

    const SFTP_CONNECTION = 'SFTP';
    const FTP_CONNECTION  = 'FTP';

    /** @var  string $uploadDir */
    protected $uploadDir;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * DistantConnectionManager constructor.
     *
     * @param ConfigManager $configManager
     * @param string        $uploadDir
     */
    public function __construct(ConfigManager $configManager, $uploadDir)
    {
        $this->configManager = $configManager;
        $this->uploadDir     = $uploadDir;
    }

    /**
     * Tests the connection by trying to list the files in the root directory
     */
    public function testConnection()
    {
        $fileSystem = $this->getDistantFileSystem();
        $fileSystem->has('.');
    }

    /**
     * @param string $fileName
     *
     * @return UploadedFile
     */
    public function downloadFile($fileName)
    {
        $distantFileSystem = $this->getDistantFileSystem();
        $localFileSystem   = $this->getLocalFileSystem();

        $localFileName = basename($fileName);
        $distantFile = $distantFileSystem->read($fileName);
        $localFileSystem->write($localFileName, $distantFile, true);

        $mimeType = $this->getMimeType($fileName);

        return new UploadedFile($this->uploadDir . $localFileName, $localFileName, $mimeType);
    }

    /**
     * @return Filesystem
     */
    protected function getLocalFileSystem()
    {
        $adapter = new Local($this->uploadDir, true);

        return new Filesystem($adapter);
    }

    /**
     * @return Filesystem
     * @throws \Exception
     */
    protected function getDistantFileSystem()
    {
        $type = $this->configManager->get('synolia_oroneo.distant_connection_type');

        if ($type == self::FTP_CONNECTION) {
            $adapter = $this->getFtpFileSystem();
        } elseif ($type == self::SFTP_CONNECTION) {
            $adapter = $this->getSftpFileSystem();
        } else {
            throw new \RuntimeException('No implementation for '.$type.' connection');
        }

        return $adapter;
    }

    /**
     * @return Filesystem
     */
    protected function getFtpFileSystem()
    {
        $adapter = new Ftp('/', $this->configManager->get('synolia_oroneo.distant_host'), [
            'username'       => $this->configManager->get('synolia_oroneo.distant_username'),
            'password'       => $this->configManager->get('synolia_oroneo.distant_password'),
            'port'           => $this->configManager->get('synolia_oroneo.distant_port'),
            'passive'        => $this->configManager->get('synolia_oroneo.distant_passive'),
        ]);

        return new Filesystem($adapter);
    }

    /**
     * @return Filesystem
     */
    protected function getSftpFileSystem()
    {
        $sftp = new SFTP($this->configManager->get('synolia_oroneo.distant_host'), $this->configManager->get('synolia_oroneo.distant_port'));

        $isLogged = $sftp->login($this->configManager->get('synolia_oroneo.distant_username'), $this->configManager->get('synolia_oroneo.distant_password'));

        if (!$isLogged) {
            throw new \RuntimeException(sprintf('Could not login as %s.', $this->configManager->get('synolia_oroneo.distant_username')));
        }

        $adapter = new PhpseclibSftp($sftp);

        return new Filesystem($adapter);
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
}
