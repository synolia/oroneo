<?php

namespace Synolia\Bundle\OroneoBundle\SystemConfig;

/**
 * Class MappingConfig
 * @package   Synolia\Bundle\OroneoBundle\SystemConfig
 * @author    Synolia <contact@synolia.com>
 * @copyright Open Software License v. 3.0 (https://opensource.org/licenses/OSL-3.0)
 */
class MappingConfig
{
    /**
     * @var string $akeneoField
     */
    protected $akeneoField;

    /**
     * @var string $oroField
     */
    protected $oroField;

    /**
     * @var string $oroEntityField
     */
    protected $oroEntityField;

    /**
     * @var bool $required
     */
    protected $required;

    /**
     * @var bool $translatable
     */
    protected $translatable;

    /**
     * @param string|null  $akeneoField
     * @param string|null  $oroField
     * @param string|null  $oroEntityField
     * @param null|boolean $required
     * @param null|boolean $translatable
     */
    public function __construct(
        $akeneoField = null,
        $oroField = null,
        $oroEntityField = null,
        $required = null,
        $translatable = null
    ) {
        $this->akeneoField    = $akeneoField;
        $this->oroField       = $oroField;
        $this->oroEntityField = $oroEntityField;
        $this->required       = $required;
        $this->translatable   = $translatable;
    }

    /**
     * @return string
     */
    public function getAkeneoField()
    {
        return $this->akeneoField;
    }

    /**
     * @param string $akeneoField
     *
     * @return $this
     */
    public function setAkeneoField($akeneoField)
    {
        $this->akeneoField = $akeneoField;

        return $this;
    }

    /**
     * @return string
     */
    public function getOroField()
    {
        return $this->oroField;
    }

    /**
     * @param string $oroField
     *
     * @return $this
     */
    public function setOroField($oroField)
    {
        $this->oroField = $oroField;

        return $this;
    }

    /**
     * @return string
     */
    public function getOroEntityField()
    {
        return $this->oroEntityField;
    }

    /**
     * @param string $oroEntityField
     *
     * @return $this
     */
    public function setOroEntityField($oroEntityField)
    {
        $this->oroEntityField = $oroEntityField;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $required
     *
     * @return $this
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isTranslatable()
    {
        return $this->translatable;
    }

    /**
     * @param boolean $translatable
     *
     * @return $this
     */
    public function setTranslatable($translatable)
    {
        $this->translatable = $translatable;

        return $this;
    }
}
