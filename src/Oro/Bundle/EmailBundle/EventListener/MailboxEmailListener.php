<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

/**
 * This can be used to build various mailbox email listeners (e.g. auto-responders).
 * @see AutoResponseListener
 * @see MailboxProcessTriggerListener
 */
abstract class MailboxEmailListener
{
    /** @var EmailBody[] */
    protected $emailBodies = [];

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $emails = [];
        foreach ($uow->getScheduledEntityInsertions() as $oid => $entity) {
            if ($entity instanceof EmailUser) {
                /*
                 * Collect already flushed emails with bodies and later check
                 * if there is new binding to mailbox.
                 * (email was sent from the system and now mailbox is synchronized)
                 */
                $email = $entity->getEmail();
                if ($email && $email->getId() && $email->getEmailBody() && $entity->getMailboxOwner()) {
                    $emails[$email->getId()] = $email;
                }
            } elseif ($entity instanceof EmailBody) {
                $this->emailBodies[$oid] = $entity;
            }
        }

        if ($emails) {
            $emailsToProcess = $this->filterEmailsWithNewlyBoundMailboxes($em, $emails);
            foreach ($emailsToProcess as $email) {
                $this->emailBodies[spl_object_hash($email->getEmailBody())] = $email->getEmailBody();
            }
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param Email[] $emails
     *
     * @return Email[]
     */
    protected function filterEmailsWithNewlyBoundMailboxes(EntityManagerInterface $em, array $emails): array
    {
        $qb = $em->getRepository(EmailUser::class)->createQueryBuilder('eu');
        $emailIdsWithAlreadyBoundMailboxesResult = $qb->select('e.id')
            ->andWhere($qb->expr()->in('e.id', ':ids'))
            ->join('eu.mailboxOwner', 'mo')
            ->join('eu.email', 'e')
            ->setParameter('ids', array_keys($emails))
            ->getQuery()
            ->getResult();

        return array_diff_key($emails, array_flip(array_map('current', $emailIdsWithAlreadyBoundMailboxesResult)));
    }
}
