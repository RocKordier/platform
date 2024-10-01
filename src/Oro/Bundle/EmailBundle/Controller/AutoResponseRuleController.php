<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseRuleType;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseTemplateType;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for the auto response rule functionality.
 */
#[Route(path: '/autoresponserule')]
class AutoResponseRuleController extends AbstractController
{
    /**
     * @param Request $request
     * @param Mailbox|null $mailbox
     * @return array
     */
    #[Route(path: '/create/{mailbox}')]
    #[Template('@OroEmail/AutoResponseRule/dialog/update.html.twig')]
    #[Acl(
        id: 'oro_email_autoresponserule_create',
        type: 'entity',
        class: AutoResponseRule::class,
        permission: 'CREATE'
    )]
    public function createAction(Request $request, Mailbox $mailbox = null)
    {
        $rule = new AutoResponseRule();
        if ($mailbox) {
            $rule->setMailbox($mailbox);
        }

        return $this->update($request, $rule);
    }

    /**
     * @param AutoResponseRule $rule
     * @param Request $request
     * @return array
     */
    #[Route(path: '/update/{id}', requirements: ['id' => '\d+'])]
    #[Template('@OroEmail/AutoResponseRule/dialog/update.html.twig')]
    #[Acl(id: 'oro_email_autoresponserule_update', type: 'entity', class: AutoResponseRule::class, permission: 'EDIT')]
    public function updateAction(AutoResponseRule $rule, Request $request)
    {
        if ($request->isMethod('POST')) {
            $params = $request->request->get(AutoResponseRuleType::NAME);
            if (!$params['template']['existing_entity'] && $rule->getTemplate()) {
                $oldTemplate = $rule->getTemplate();
                if (!$oldTemplate->isVisible()) {
                    $em = $this->getAutoResponseRuleManager();
                    $em->remove($oldTemplate);
                }
                $rule->setTemplate(new EmailTemplate());
            }
        }

        return $this->update($request, $rule);
    }

    /**
     * @param EmailTemplate $template
     * @return array
     */
    #[Route(path: '/template/{id}', options: ['expose' => true])]
    #[Template('@OroEmail/AutoResponseRule/editTemplate.html.twig')]
    #[AclAncestor('oro_email_emailtemplate_update')]
    public function editTemplateAction(EmailTemplate $template)
    {
        $form = $this->createForm(AutoResponseTemplateType::class, $template);

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @param Request $request
     * @param AutoResponseRule $rule
     *
     * @return array
     */
    protected function update(Request $request, AutoResponseRule $rule)
    {
        $form = $this->createForm(AutoResponseRuleType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getAutoResponseRuleManager();
            $em->persist($rule);
            $em->flush();

            $this->clearAutoResponses();
        }

        /** @var AutoResponseManager $autoResponseManager */
        $autoResponseManager = $this->container->get('oro_email.autoresponserule_manager');
        $entity = $autoResponseManager->createEmailEntity();

        return [
            'form'  => $form->createView(),
            'saved' => $form->isSubmitted() && $form->isValid(),
            'emailEntityData' => $entity,
            'metadata' => $this->container->get(Manager::class)->getMetadata('string')
        ];
    }

    /**
     * Cleans old unassigned auto response rules
     */
    private function clearAutoResponses()
    {
        $this->getEventDispatcher()->addListener(
            'kernel.terminate',
            [$this->getAutoResponseRuleRepository(), 'clearAutoResponses']
        );
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->container->get(EventDispatcherInterface::class);
    }

    /**
     * @return AutoResponseRuleRepository
     */
    protected function getAutoResponseRuleRepository()
    {
        return $this->container->get('doctrine')->getRepository(AutoResponseRule::class);
    }

    /**
     * @return EntityManager
     */
    protected function getAutoResponseRuleManager()
    {
        return $this->container->get('doctrine')->getManagerForClass(AutoResponseRule::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'oro_email.autoresponserule_manager' => AutoResponseManager::class,
                Manager::class,
                EventDispatcherInterface::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
