<?php

namespace Synolia\Bundle\OroneoBundle\SystemConfig;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Class MappingLocalization
 * @package Synolia\Bundle\OroneoBundle\SystemConfig
 */
class MappingLocalization
{
    /**
     * @var string $akeneoLocalization
     */
    protected $akeneoLocalization;

    /**
     * @var string $oroLocalization
     */
    protected $oroLocalization;

    /**
     * MappingLocalization constructor.
     *
     * @param string|null       $akeneoLocalization
     * @param Localization|null $oroLocalization
     */
    public function __construct($akeneoLocalization = null, $oroLocalization = null)
    {
        $this->akeneoLocalization = $akeneoLocalization;
        $this->oroLocalization    = $oroLocalization;
    }

    /**
     * @return string
     */
    public function getAkeneoLocalization()
    {
        return $this->akeneoLocalization;
    }

    /**
     * @param string $akeneoLocalization
     *
     * @return $this
     */
    public function setAkeneoLocalization($akeneoLocalization)
    {
        $this->akeneoLocalization = $akeneoLocalization;

        return $this;
    }

    /**
     * @return Localization
     */
    public function getOroLocalization()
    {
        return $this->oroLocalization;
    }

    /**
     * @param Localization $oroLocalization
     *
     * @return $this
     */
    public function setOroLocalization($oroLocalization)
    {
        $this->oroLocalization = $oroLocalization;

        return $this;
    }
}
