<?php

namespace Oro\Bundle\UserBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Form\Handler\UserHandler;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenType;
use Oro\Bundle\UserBundle\Form\Type\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This controller covers CRUD functionality for User entity.
 * Also includes user profile management functionality.
 */
class UserController extends AbstractController
{
    /**
     * @param User $user
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_user_view', requirements: ['id' => '\d+'])]
    #[Template('@OroUser/User/view.html.twig')]
    #[Acl(id: 'oro_user_user_view', type: 'entity', class: User::class, permission: 'VIEW')]
    public function viewAction(User $user)
    {
        return $this->view($user, $user === $this->getUser());
    }

    #[Route(path: '/profile/view', name: 'oro_user_profile_view')]
    #[Template('@OroUser/User/view.html.twig')]
    public function viewProfileAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->view($this->getUser(), true);
    }

    #[Route(path: '/profile/edit', name: 'oro_user_profile_update')]
    #[Template('@OroUser/User/Profile/update.html.twig')]
    #[AclAncestor('update_own_profile')]
    public function updateProfileAction(Request $request)
    {
        return $this->update($this->getUser(), $request);
    }

    /**
     *
     * @param User $user
     * @return JsonResponse|Response
     */
    #[Route(path: '/apigen/{id}', name: 'oro_user_apigen', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function apigenAction(User $user)
    {
        if (!$this->isUserApiGenAllowed($user)) {
            throw $this->createAccessDeniedException();
        }
        $userApi = $this->getUserApi($user);
        $form = $this->createForm(UserApiKeyGenType::class, $userApi);

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->getMethod() === 'POST') {
            $userApi->setApiKey($userApi->generateKey());
            $form->setData($userApi);
            $form->handleRequest($request);

            $responseData = ['data' => [], 'status' => 'success'];
            $status = Response::HTTP_OK;
            if ($form->isSubmitted() && $form->isValid()) {
                $this->saveUserApi($user, $userApi);
                $responseData['data'] = ['apiKey' => $userApi->getApiKey()];
            } else {
                $status = Response::HTTP_BAD_REQUEST;
                $responseData['status'] = 'error';
                $responseData['errors'] = $form->getErrors();
            }

            return new JsonResponse($responseData, $status);
        }

        return $this->render(
            '@OroUser/User/widget/apiKeyGen.html.twig',
            ['form' => $form->createView(), 'user' => $user]
        );
    }

    /**
     * Create user form
     */
    #[Route(path: '/create', name: 'oro_user_create')]
    #[Template('@OroUser/User/update.html.twig')]
    #[Acl(id: 'oro_user_user_create', type: 'entity', class: User::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        $user = $this->container->get(UserManager::class)->createUser();

        return $this->update($user, $request);
    }

    /**
     * Edit user form
     *
     *
     * @param User $entity
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_user_update', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template('@OroUser/User/update.html.twig')]
    #[Acl(id: 'oro_user_user_update', type: 'entity', class: User::class, permission: 'EDIT')]
    public function updateAction(User $entity, Request $request)
    {
        return $this->update($entity, $request);
    }

    #[Route(
        path: '/{_format}',
        name: 'oro_user_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template('@OroUser/User/index.html.twig')]
    #[AclAncestor('oro_user_user_view')]
    public function indexAction()
    {
        return [
            'entity_class' => User::class
        ];
    }

    /**
     * @param User $entity
     * @param Request $request
     *
     * @return RedirectResponse|array
     */
    private function update(User $entity, Request $request)
    {
        if ($this->container->get(UserHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.user.controller.user.message.saved')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity'       => $entity,
            'form'         => $this->container->get(UserType::class)->createView(),
            'allow_delete' => $entity->getId() && $this->isDeleteGranted($entity)
        ];
    }

    /**
     * @param User $entity
     * @param bool $isProfileView
     * @return array
     */
    private function view(User $entity, $isProfileView = false)
    {
        return [
            'entity'        => $entity,
            'allow_delete'  => $this->isDeleteGranted($entity),
            'isProfileView' => $isProfileView
        ];
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     */
    #[Route(path: '/widget/info/{id}', name: 'oro_user_widget_info', requirements: ['id' => '\d+'])]
    #[Template('@OroUser/User/widget/info.html.twig')]
    public function infoAction(Request $request, User $user)
    {
        $isViewProfile = (bool)$request->query->get('viewProfile', false);

        if (!(($isViewProfile && $this->getUser()->getId() === $user->getId())
            || $this->isGranted('oro_user_user_view', $user))
        ) {
            throw new AccessDeniedException();
        }

        return [
            'entity'      => $user,
            'userApi'     => $this->getUserApi($user),
            'viewProfile' => $isViewProfile
        ];
    }

    #[Route(path: '/login-attempts', name: 'oro_user_login_attempts')]
    #[Template('@OroUser/User/loginAttempts.html.twig')]
    #[AclAncestor('oro_view_user_login_attempt')]
    public function loginAttemptsAction()
    {
        return [];
    }

    /**
     * Returns current UserApi or creates new one
     *
     * @param User $user
     *
     * @return UserApi
     */
    private function getUserApi(User $user)
    {
        $userManager  = $this->container->get(UserManager::class);
        if (!$userApi = $userManager->getApi($user, $this->getOrganization())) {
            $userApi = new UserApi();
            $userApi->setUser($user);
        }

        return $userApi;
    }

    /**
     * Returns current organization
     *
     * @return Organization
     */
    private function getOrganization()
    {
        /** @var UsernamePasswordOrganizationToken $token */
        $token = $this->container->get(TokenStorageInterface::class)->getToken();

        return $token->getOrganization();
    }

    private function isDeleteGranted(User $entity): bool
    {
        return $this->container->get(EntityDeleteHandlerRegistry::class)
            ->getHandler(User::class)
            ->isDeleteGranted($entity);
    }

    /**
     * @param User $entity
     *
     * @return bool
     */
    private function isUserApiGenAllowed(User $entity)
    {
        return $this->container->get(TokenAccessorInterface::class)->getUserId() === $entity->getId()
               || $this->isGranted('MANAGE_API_KEY', $entity);
    }

    private function saveUserApi(User $user, UserApi $userApi)
    {
        $em = $this->container->get('doctrine')->getManagerForClass(User::class);

        $userApi
            ->setUser($user)
            ->setOrganization($this->getOrganization());

        $em->persist($userApi);
        $em->flush();
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                TokenAccessorInterface::class,
                UserManager::class,
                EntityDeleteHandlerRegistry::class,
                UserType::class,
                UserHandler::class,
                TokenStorageInterface::class,
                'doctrine' => ManagerRegistry::class
            ]
        );
    }
}
