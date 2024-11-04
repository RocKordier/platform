<?php

namespace Oro\Bundle\SecurityBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclConfigurationPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\AclGroupProvidersPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnerMetadataProvidersPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\OwnershipDecisionMakerPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\PublicSecurityServicesPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\RemoveAclSchemaListenerPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\SessionPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Compiler\SetFirewallExceptionListenerPass;
use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationFormLoginFactory;
use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationHttpBasicFactory;
use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationRememberMeFactory;
use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedStringType;
use Oro\Bundle\SecurityBundle\DoctrineExtension\Dbal\Types\CryptedTextType;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceWithHandlerCompilerPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroSecurityBundle extends Bundle
{
    #[\Override]
    public function boot(): void
    {
        parent::boot();

        $crypter = $this->container->get('oro_security.encoder.repetitive_crypter');
        CryptedStringType::setCrypter($crypter);
        CryptedTextType::setCrypter($crypter);
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AclConfigurationPass());
        $container->addCompilerPass(new OwnershipDecisionMakerPass());
        $container->addCompilerPass(new OwnerMetadataProvidersPass());
        $container->addCompilerPass(new AclGroupProvidersPass());
        $container->addCompilerPass(new PriorityNamedTaggedServiceWithHandlerCompilerPass(
            'oro_security.access_rule_executor',
            'oro_security.access_rule',
            function (array $attributes, string $serviceId): array {
                unset($attributes['priority']);

                return [$serviceId, $attributes];
            }
        ));
        $container->addCompilerPass(new SessionPass());
        $container->addCompilerPass(new SetFirewallExceptionListenerPass());
        $container->addCompilerPass(new PublicSecurityServicesPass());

        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new RemoveAclSchemaListenerPass());
            $container->moveCompilerPassBefore(
                RemoveAclSchemaListenerPass::class,
                RegisterEventListenersAndSubscribersPass::class
            );
        }

        $extension = $container->getExtension('security');
        $extension->addAuthenticatorFactory(new OrganizationFormLoginFactory());
        $extension->addAuthenticatorFactory(new OrganizationHttpBasicFactory());
        $extension->addAuthenticatorFactory(new OrganizationRememberMeFactory());

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAttributeMappingDriver(
                    ['Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity'],
                    [$this->getPath() . '/Tests/Functional/Environment/Entity']
                )
            );
        }
    }
}
