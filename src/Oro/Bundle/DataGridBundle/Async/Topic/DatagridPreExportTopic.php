<?php

namespace Oro\Bundle\DataGridBundle\Async\Topic;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\ChainConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines MQ topic that initializes the datagrid data export.
 */
class DatagridPreExportTopic extends AbstractTopic implements JobAwareTopicInterface
{
    private int $batchSize;

    /** @var string[] */
    private array $outputFormats;

    private TokenAccessorInterface $tokenAccessor;

    /** @var ChainConfigurationProvider $provider  */
    private ConfigurationProviderInterface $provider;

    public function __construct(
        int                            $batchSize,
        TokenAccessorInterface         $tokenAccessor,
        ConfigurationProviderInterface $provider,
        array                          $outputFormats = ['csv', 'xlsx'],
    ) {
        $this->batchSize = $batchSize;
        $this->tokenAccessor = $tokenAccessor;
        $this->provider = $provider;
        $this->outputFormats = $outputFormats;
    }

    #[\Override]
    public static function getName(): string
    {
        return 'oro.datagrid.pre_export';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Initializes the datagrid data export.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'contextParameters',
                'batchSize',
                'outputFormat',
                'notificationTemplate',
            ])
            ->setRequired([
                'contextParameters',
                'outputFormat',
            ])
            ->setDefaults([
                'contextParameters' => \Closure::fromCallable([$this, 'configureContextParameters']),
                'batchSize' => $this->batchSize,
                'notificationTemplate' => 'datagrid_export_result',
            ])
            ->addAllowedTypes('contextParameters', 'array')
            ->addAllowedTypes('outputFormat', 'string')
            ->addAllowedTypes('notificationTemplate', 'string')
            ->addAllowedTypes('batchSize', 'int')
            ->addAllowedValues('outputFormat', $this->outputFormats)
            ->setInfo('batchSize', 'Number of rows to export in each child job.');
    }

    private function configureContextParameters(OptionsResolver $parametersResolver): void
    {
        $parametersResolver
            ->setDefined([
                'gridName',
                'gridParameters',
                FormatterProvider::FORMAT_TYPE,
            ])
            ->setRequired([
                'gridName',
            ])
            ->setDefaults([
                'gridParameters' => [],
                FormatterProvider::FORMAT_TYPE => 'excel',
            ])
            ->addAllowedTypes('gridName', 'string')
            ->addAllowedValues('gridName', function (string $gridName) {
                if (!$this->provider->isValidConfiguration($gridName)) {
                    throw new InvalidOptionsException(
                        sprintf('Grid %s configuration is not valid.', $gridName)
                    );
                }

                return true;
            })
            ->addAllowedTypes('gridParameters', 'array')
            ->addAllowedTypes(FormatterProvider::FORMAT_TYPE, 'string')
            ->addNormalizer('gridParameters', static function (Options $options, array $value) {
                if (!in_array(
                    DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE,
                    $value[ParameterBag::DATAGRID_MODES_PARAMETER] ?? [],
                    false
                )) {
                    // Enables "importexport" datagrid mode to avoid unwanted datagrid extensions.
                    $value[ParameterBag::DATAGRID_MODES_PARAMETER][] = DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE;
                }

                return $value;
            });
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        $gridName = $messageBody['contextParameters']['gridName'];
        $outputFormat = $messageBody['outputFormat'];

        return sprintf(
            '%s.%s.user_%s.%s',
            DatagridExportTopic::getName(),
            $gridName,
            $this->tokenAccessor->getUserId(),
            $outputFormat
        );
    }
}
