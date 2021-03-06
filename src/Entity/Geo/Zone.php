<?php

namespace App\Entity\Geo;

use App\Entity\EntityTimestampableTrait;
use App\Entity\ReferentTag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Geo\ZoneRepository")
 * @ORM\Table(
 *     name="geo_zone",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="geo_zone_code_type_unique", columns={"code", "type"})
 *     }
 * )
 * @ORM\AttributeOverrides({
 *     @ORM\AttributeOverride(name="code", column=@ORM\Column(unique=false))
 * })
 */
class Zone implements GeoInterface
{
    use GeoTrait;
    use EntityTimestampableTrait;

    public const CUSTOM = 'custom';
    public const COUNTRY = 'country';
    public const REGION = 'region';
    public const DEPARTMENT = 'department';
    public const DISTRICT = 'district';
    public const CITY = 'city';
    public const BOROUGH = 'borough';
    public const CITY_COMMUNITY = 'city_community';
    public const CANTON = 'canton';
    public const FOREIGN_DISTRICT = 'foreign_district';
    public const CONSULAR_DISTRICT = 'consular_district';

    /**
     * @var string
     *
     * @ORM\Column(nullable=false)
     */
    private $type;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Geo\Zone", inversedBy="children")
     * @ORM\JoinTable(
     *     name="geo_zone_parent",
     *     joinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id")}
     * )
     */
    private $parents;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Geo\Zone", mappedBy="parents")
     */
    private $children;

    public function __construct(string $type, string $code, string $name, ReferentTag $referentTag = null)
    {
        $this->type = $type;
        $this->code = $code;
        $this->name = $name;
        $this->referentTag = $referentTag;
        $this->parents = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @return self[]
     */
    public function getParents(): array
    {
        return $this->parents->toArray();
    }

    public function addParent(self $zone): void
    {
        $this->parents->contains($zone) || $this->parents->add($zone);
    }

    public function clearParents(): void
    {
        $this->parents->clear();
    }

    /**
     * @return self[]
     */
    public function getChildren(): array
    {
        return $this->children->toArray();
    }
}
