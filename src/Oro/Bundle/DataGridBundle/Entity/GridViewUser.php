<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

/**
* Entity that represents Grid View User
*
*/
#[ORM\Entity(repositoryClass: GridViewUserRepository::class)]
class GridViewUser extends AbstractGridViewUser
{
    #[ORM\ManyToOne(targetEntity: GridView::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'grid_view_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?AbstractGridView $gridView = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    #[\Override]
    public function getUser()
    {
        return $this->user;
    }

    #[\Override]
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }
}
