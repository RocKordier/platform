<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ConfigBundle\Config\UserScopeManager;

class UserScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserScopeManager
     */
    protected $object;

    /**
     * @var ObjectRepository
     */
    protected $repository;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var array
     */
    protected $settings = array(
        'oro_user' => array(
            'level'    => array(
                'value' => 20,
                'type'  => 'scalar',
            )
        )
    );

    protected function setUp()
    {
        $repo = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('loadSettings');
        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->om->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $this->object = new UserScopeManager($this->om);

        $this->security   = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->group1     = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');
        $this->group2     = $this->getMock('Oro\Bundle\UserBundle\Entity\Group');

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $user  = new User();

        $this->security
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->group1
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));

        $this->group2
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));

        $token
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $user
            ->setId(1)
            ->addGroup($this->group1)
            ->addGroup($this->group2);

        $this->object = new UserScopeManager($this->om);
    }

    public function testSecurity()
    {
        $object = $this->getMock(
            'Oro\Bundle\ConfigBundle\Config\UserScopeManager',
            array('loadStoredSettings'),
            array($this->om)
        );

        $object->expects($this->exactly(3))
            ->method('loadStoredSettings');

        $object->setSecurity($this->security);

        $this->assertEquals('user', $object->getScopedEntityName());
    }

    public function testGetScopedEntityName()
    {
        $this->assertEquals('user', $this->object->getScopedEntityName());
    }

    public function testSetScopeId()
    {
        $object = clone $this->object;
        $object->setSecurity($this->security);
        $object->setScopeId();
        $this->assertEquals(1, $object->getScopeId());
    }

    public function testGetScopeId()
    {
        $object = clone $this->object;
        $object->setSecurity($this->security);
        $this->assertEquals(1, $object->getScopeId());
    }
}
