<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use BlockBuilder\BlockGenerator\Exception\GenerationContributionConflictException;
use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\Exception\UnsupportedFieldGenerationException;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\FieldType\FieldTypeRegistry;

final class FieldGenerationContributorRegistry
{
    /**
     * @var array<string, FieldGenerationContributorInterface>
     */
    private array $contributorsByType = [];

    public function __construct(
        private readonly FieldTypeRegistry $fieldTypeRegistry,
        FieldGenerationContributorCollection $contributors,
    ) {
        foreach ($contributors as $contributor) {
            $this->register($contributor);
        }
    }

    /**
     * @return FieldGenerationContributorInterface[]
     */
    public function all(): array
    {
        return array_values($this->contributorsByType);
    }

    public function getFor(FieldGenerationContext $context): FieldGenerationContributorInterface
    {
        $fieldType = $this->fieldTypeRegistry->get($context->fieldType);
        $expectedDtoClass = $fieldType::getDtoClass();

        if (!$context->fieldDto instanceof $expectedDtoClass) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Field type "%s" requires DTO "%s" during generation; "%s" was provided.',
                $context->fieldType->value,
                $expectedDtoClass,
                $context->fieldDto::class,
            ));
        }

        return $this->contributorsByType[$context->fieldType->value]
            ?? throw new UnsupportedFieldGenerationException(sprintf(
                'No generation contributor is registered for field type "%s".',
                $context->fieldType->value,
            ));
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $this->getFor($context)->contribute($context, $planBuilder);
    }

    private function register(FieldGenerationContributorInterface $contributor): void
    {
        $fieldTypeHandle = $contributor->getFieldType()->value;

        if (isset($this->contributorsByType[$fieldTypeHandle])) {
            throw new GenerationContributionConflictException(sprintf(
                'More than one generation contributor is registered for field type "%s".',
                $fieldTypeHandle,
            ));
        }

        $this->contributorsByType[$fieldTypeHandle] = $contributor;
    }
}
