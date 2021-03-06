<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\Entity()
 */
class Audit extends AbstractAudit
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @var string $loggedAt
     *
     * @ORM\Column(name="logged_at", type="datetime", nullable=true)
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     *
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @ORM\Column(name="object_class", type="string", length=255)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $objectClass;

    /**
     * @var string $objectName
     *
     * @ORM\Column(name="object_name", type="string", length=255, nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $objectName;

    /**
     * @var integer $version
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $version;

    /**
     * @var string $username
     *
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $username;

    /**
     * @var AbstractUser[] $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     * @Type("string")
     * @SerializedName("username")
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get user name
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getUser() ? $this->getUser()->getUsername() : '';
    }
}
