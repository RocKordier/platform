<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailSuggestionData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * @group search
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailContextTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadEmailActivityData::class, LoadEmailSuggestionData::class, LoadUser::class]);
        // do the reindex because by some unknown reasons the search index is empty
        // after upgrade from old application version
        $indexer = self::getContainer()->get('oro_search.search.engine.indexer');
        $indexer->reindex(User::class);
    }

    private static function filterResponseContent(Response $response): array
    {
        $entityTypes = ['users'];
        $responseContent = self::jsonToArray($response->getContent());
        $filteredResponseContent = ['data' => []];
        foreach ($responseContent['data'] as $item) {
            $entityType = $item['relationships']['entity']['data']['type'];
            if (in_array($entityType, $entityTypes, true)) {
                $filteredResponseContent['data'][] = $item;
            }
        }
        if (isset($responseContent['included'])) {
            $filteredResponseContent['included'] = [];
            foreach ($responseContent['included'] as $item) {
                $entityType = $item['type'];
                if (in_array($entityType, $entityTypes, true)) {
                    $filteredResponseContent['included'][] = $item;
                }
            }
        }

        return $filteredResponseContent;
    }

    private static function assertResponseContent(
        array $expectedContent,
        array $content,
        bool $strictOrder = false
    ): void {
        try {
            self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false, $strictOrder));
        } catch (ExpectationFailedException $e) {
            // add the response data to simplify finding an error when a test is failed
            throw new ExpectationFailedException($e->getMessage() . "\nResponse Data:\n" . Yaml::dump($content, 8));
        }
    }

    private function getUser(string $reference): User
    {
        return $this->getReference($reference);
    }

    private function getUserUrl(int $userId): string
    {
        return $this->getUrl('oro_user_view', ['id' => $userId], true);
    }

    private function getUserData(string $reference, bool $isContext): array
    {
        $user = $this->getUser($reference);

        return [
            'type'          => 'emailcontext',
            'id'            => 'users-' . $user->getId(),
            'links'         => ['entityUrl' => $this->getUserUrl($user->getId())],
            'attributes'    => [
                'entityName' => trim($user->getFirstName() . ' ' . $user->getLastName()),
                'isContext'  => $isContext
            ],
            'relationships' => [
                'entity' => ['data' => ['type' => 'users', 'id' => (string)$user->getId()]]
            ]
        ];
    }

    private function getUserIncludeData(string $reference): array
    {
        $user = $this->getUser($reference);

        return [
            'type'       => 'users',
            'id'         => (string)$user->getId(),
            'attributes' => [
                'username' => $user->getUserIdentifier()
            ]
        ];
    }

    public function testGetListWithMessageIdForExistingEmail(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter[messageId]' => 'email1@orocrm-pro.func-test', 'page[size]' => -1]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_11', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithMessageIdForNotExistingEmail(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter[messageId]' => 'unknown@example.com']
        );
        self::assertResponseCount(0, $response);
    }

    public function testGetListWithSeveralMessageIds(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter[messageId]' => 'email1@orocrm-pro.func-test,email2@orocrm-pro.func-test', 'page[size]' => -1]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_4', true),
                $this->getUserData('user_11', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithMessageIdForExistingEmailAndWithFromToAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId' => 'email1@orocrm-pro.func-test',
                    'from'      => 'email1@orocrm-pro.func-test',
                    'to'        => 'richard_bradley@example.com',
                    'cc'        => ['brenda_brock@example.com', 'lucas_thornton@example.com']
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_11', false),
                $this->getUserData('user_10', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithMessageIdForNotExistingEmailAndWithFromToAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId' => 'unknown@example.com',
                    'from'      => 'email1@orocrm-pro.func-test',
                    'to'        => 'richard_bradley@example.com',
                    'cc'        => ['brenda_brock@example.com', 'lucas_thornton@example.com']
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_10', false),
                $this->getUserData('user_2', false),
                $this->getUserData('user_1', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithSeveralMessageIdsAndWithFromToAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId' => 'email1@orocrm-pro.func-test,email2@orocrm-pro.func-test',
                    'from'      => 'email1@orocrm-pro.func-test',
                    'to'        => 'richard_bradley@example.com',
                    'cc'        => ['brenda_brock@example.com', 'lucas_thornton@example.com']
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_4', true),
                $this->getUserData('user_11', false),
                $this->getUserData('user_10', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListBySpecifiedEntityTypes(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'entities' => 'users']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_11', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListBySearchTextWhenFoundEntityIsNotInContext(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListBySearchTextWhenFoundEntityIsInContext(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Bradley']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithInclude(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['include' => 'entity', 'filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'entities' => 'users']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data'     => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_11', false)
            ],
            'included' => [
                $this->getUserIncludeData('user_1'),
                $this->getUserIncludeData('user_2'),
                $this->getUserIncludeData('user_3'),
                $this->getUserIncludeData('user_11')
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithIsContextFalse(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'isContext' => 'no']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_11', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithIsContextTrue(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'isContext' => 'yes']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithExcludeCurrentUserFalse(): void
    {
        $currentUser = $this->getUser('user');
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId'          => 'unknown@example.com',
                    'excludeCurrentUser' => 'no',
                    'from'               => 'email1@orocrm-pro.func-test',
                    'to'                 => ['richard_bradley@example.com', $currentUser->getEmail()]
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', false),
                $this->getUserData('user', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithExcludeCurrentUserTrue(): void
    {
        $currentUser = $this->getUser('user');
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId'          => 'unknown@example.com',
                    'excludeCurrentUser' => 'yes',
                    'from'               => 'email1@orocrm-pro.func-test',
                    'to'                 => ['richard_bradley@example.com', $currentUser->getEmail()]
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testGetListWithFromTonAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId' => 'email1@orocrm-pro.func-test'
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_11', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testTryToGetListWithoutMessageId(): void
    {
        $response = $this->cget(['entity' => 'emailcontext'], [], [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The Message-ID is required.',
                'source' => ['parameter' => 'filter[messageId]']
            ],
            $response
        );
    }

    public function testTryToGetListWithEmptyMessageId(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => ' ']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected not empty string value. Given " ".',
                'source' => ['parameter' => 'filter[messageId]']
            ],
            $response
        );
    }

    public function testTryToGetListWhenMessageIdContainsEmptyItems(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test, ']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected an array of not empty strings. Given "email1@orocrm-pro.func-test, ".',
                'source' => ['parameter' => 'filter[messageId]']
            ],
            $response
        );
    }

    public function testTryToGetListBySearchTextAndFrom(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'from' => 'a@a.com']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToGetListBySearchTextAndTo(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'to' => 'a@a.com']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToGetListBySearchTextAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'cc' => 'a@a.com']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToGetListBySearchTextAndIsContext(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'isContext' => 'yes']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToGetListBySearchTextAndExcludeCurrentUser(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId'          => 'email1@orocrm-pro.func-test',
                    'searchText'         => 'Doe',
                    'excludeCurrentUser' => 'yes'
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'emailcontext', 'id' => 'users-1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'emailcontext'],
            ['data' => ['type' => 'emailcontext', 'id' => 'users-1']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'emailcontext', 'id' => 'users-1'],
            ['data' => ['type' => 'emailcontext', 'id' => 'users-1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'emailcontext', 'id' => 'users-1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'emailcontext'],
            ['filter' => ['id' => 'users-1']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
