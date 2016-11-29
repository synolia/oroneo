<?php

namespace Synolia\Bundle\AkeneoConnectorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="akeneo_localization_mapping",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="locale_idx", columns={"akeneoLocalization", "localization_id"})}
 * )
 */
class LocalizationMapping
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $akeneoLocalization;

    /**
     * @var Localization
     * @ORM\OneToOne(targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization")
     * @ORM\JoinColumn(name="localization_id", referencedColumnName="id", onDelete="cascade")
     */
    protected $oroLocalization;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return string
     */
    public function getLocalizationName()
    {
        return $this->oroLocalization ? $this->oroLocalization->getName() : 'default';
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
