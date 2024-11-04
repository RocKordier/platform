<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class LoadUserData extends AbstractLoadUserData
{
    public const USER_NAME = 'user_wo_permissions';
    public const USER_PASSWORD = 'user_api_key';

    public const USER_NAME_2 = 'system_user_2';
    public const USER_PASSWORD_2 = 'system_user_2_api_key';

    #[\Override]
    protected function getUsersData(): array
    {
        return [
            [
                'username' => 'user_wo_permissions',
                'email' => 'simple@example.com',
                'firstName' => 'Simple',
                'lastName' => 'User',
                'plainPassword' => 'user_api_key',
                'apiKey' => 'user_api_key',
                'reference' => self::USER_NAME,
                'enabled' => true,
                'role' => 'PUBLIC_ACCESS',
                'group' => 'Administrators',
            ],
            [
                'username' => 'system_user_2',
                'email' => 'system_user_2@example.com',
                'firstName' => 'Giffard',
                'lastName' => 'Gray',
                'plainPassword' => 'system_user_2_api_key',
                'apiKey' => 'system_user_2_api_key',
                'reference' => self::USER_NAME_2,
                'enabled' => true,
                'role' => LoadRolesData::ROLE_USER,
                'group' => 'Administrators',
            ]
        ];
    }
}
