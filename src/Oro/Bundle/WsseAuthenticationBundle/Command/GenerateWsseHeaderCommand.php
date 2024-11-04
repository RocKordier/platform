<?php

declare(strict_types=1);

namespace Oro\Bundle\WsseAuthenticationBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Generates X-WSSE HTTP header for a given user API key.
 */
class GenerateWsseHeaderCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:wsse:generate-header';

    private ManagerRegistry $registry;
    private ContainerInterface $container;

    public function __construct(ManagerRegistry $registry, ContainerInterface $container)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->container = $container;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function configure()
    {
        $this->addArgument('apiKey', InputArgument::REQUIRED, 'User API key')
            ->addOption('firewall', null, InputArgument::OPTIONAL, 'Firewall name', $this->getDefaultSecurityFirewall())
            ->setDescription('Generates X-WSSE HTTP header for a given user API key.')
            ->addUsage('--firewall=<firewall-name> <apiKey>')
        ;
    }

    /**
     * @return int
     * @throws \InvalidArgumentException
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiKey = $input->getArgument('apiKey');
        /** @var UserApi $userApi */
        $userApi = $this->registry->getRepository($this->getApiKeyEntityClass())->findOneBy(
            ['apiKey' => $apiKey]
        );
        if (!$userApi) {
            throw new \InvalidArgumentException(
                sprintf(
                    'API key "%s" does not exists',
                    $apiKey
                )
            );
        }
        $user = $userApi->getUser();
        $organization = $userApi->getOrganization();
        if (!$organization->isEnabled()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Organization for API key "%s" is not active',
                    $apiKey
                )
            );
        }
        $created = date('c');
        // http://stackoverflow.com/questions/18117695/how-to-calculate-wsse-nonce
        $nonce = base64_encode(openssl_random_pseudo_bytes(16));
        $passwordDigest = $this->getPasswordHasher($input->getOption('firewall'))->hash(
            sprintf(
                '%s%s%s',
                base64_decode($nonce),
                $created,
                $userApi->getApiKey()
            )
        );
        $output->writeln('<info>To use WSSE authentication add following headers to the request:</info>');
        $output->writeln('Authorization: WSSE profile="UsernameToken"');
        $output->writeln(
            sprintf(
                'X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
                $user->getUserIdentifier(),
                $passwordDigest,
                $nonce,
                $created
            )
        );
        $output->writeln('');

        return Command::SUCCESS;
    }

    private function getPasswordHasher(string $firewallName): PasswordHasherInterface
    {
        $serviceId = 'oro_wsse_authentication.hasher.' . $firewallName;
        if (!$this->container->has($serviceId)) {
            throw new \InvalidArgumentException(
                sprintf('WSSE password hasher for firewall "%s" is not defined', $firewallName)
            );
        }

        return $this->container->get($serviceId);
    }

    protected function getApiKeyEntityClass(): string
    {
        return UserApi::class;
    }

    protected function getDefaultSecurityFirewall(): string
    {
        return 'wsse_secured';
    }
}
