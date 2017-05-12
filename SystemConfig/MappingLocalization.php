<?php

namespace Synolia\Bundle\OroneoBundle\SystemConfig;

/**
 * Class MappingLocalization
 * @package   Synolia\Bundle\OroneoBundle\SystemConfig
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
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
     * @param string|null $akeneoLocalization
     * @param string|null $oroLocalization
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
     * @return string
     */
    public function getOroLocalization()
    {
        return $this->oroLocalization;
    }

    /**
     * @param string $oroLocalization
     *
     * @return $this
     */
    public function setOroLocalization($oroLocalization)
    {
        $this->oroLocalization = $oroLocalization;

        return $this;
    }
}
