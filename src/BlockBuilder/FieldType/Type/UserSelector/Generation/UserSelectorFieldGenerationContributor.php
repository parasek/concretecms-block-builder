<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\UserSelector\Generation;

use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContext;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorInterface;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerProperty;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerUseStatement;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\UserSelector\UserSelectorFieldTypeDto;
use Concrete\Core\User\UserInfo;
use Concrete\Core\User\UserInfoRepository;

final readonly class UserSelectorFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string GET_USER_INFO_METHOD = 'getBlockBuilderUserInfo';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::UserSelector;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof UserSelectorFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf('User Selector field generation requires DTO "%s"; "%s" was provided.', UserSelectorFieldTypeDto::class, $context->fieldDto::class));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;

        $this->contributeSharedControllerCode($planBuilder);
        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'integer',
                unsigned: true,
                hasDefault: true,
                defaultValue: 0,
                order: $context->position,
            ),
        );

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
            $planBuilder->form->addRepeatableDefaultValue($field->handle, 0);
        }

        $planBuilder->view->addFieldVariable(
            $context->fieldContext,
            new ViewVariableDocumentation(
                name: $field->handle,
                type: '\\' . UserInfo::class . '|null',
                description: sprintf('%s selected user', $field->label),
                order: $context->position,
            ),
        );
        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($field, $context->isBasicField()),
                order: $context->position,
            ),
        );
        $planBuilder->view->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderViewFragment($field, $context->isBasicField()),
                order: $context->position,
            ),
        );
    }

    private function contributeSharedControllerCode(BlockGenerationPlanBuilder $planBuilder): void
    {
        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(UserInfo::class))
            ->addUseStatement(new ControllerUseStatement(UserInfoRepository::class))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'user_selector.get_user_info',
                    code: <<<'PHP'
private function getBlockBuilderUserInfo(mixed $userIdentifier): ?UserInfo
{
    if (
        (!is_int($userIdentifier) && !is_string($userIdentifier))
        || (is_string($userIdentifier) && !ctype_digit($userIdentifier))
        || (int) $userIdentifier < 1
    ) {
        return null;
    }

    $userInfo = $this->app->make(UserInfoRepository::class)->getByID((int) $userIdentifier);

    return $userInfo instanceof UserInfo ? $userInfo : null;
}
PHP,
                ),
            );
    }

    private function contributeBasicControllerCode(
        UserSelectorFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller
            ->addProperty(new ControllerProperty(
                name: $field->handle,
                declaration: sprintf('protected int|string|null $%s = null;', $field->handle),
                order: $position,
            ))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$this->set(%1$s, $this->%2$s($this->%3$s) instanceof UserInfo ? (int) $this->%3$s : 0);',
                        $handleLiteral,
                        self::GET_USER_INFO_METHOD,
                        $field->handle,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::View->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$this->set(%1$s, $this->%2$s($this->%3$s));',
                        $handleLiteral,
                        self::GET_USER_INFO_METHOD,
                        $field->handle,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$args', '$args', $handleLiteral),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$args', false),
                    order: $position,
                ),
            );
    }

    private function contributeRepeatableControllerCode(
        UserSelectorFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $planBuilder->controller
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode('$data', '$entry', $handleLiteral),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$entry', true),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$entry[%1$s] = $this->%2$s($entry[%1$s] ?? null) instanceof UserInfo%3$s'
                        . '    ? (int) $entry[%1$s]%3$s'
                        . '    : 0;',
                        $handleLiteral,
                        self::GET_USER_INFO_METHOD,
                        PHP_EOL,
                    ),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForView->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf(
                        '$entry[%1$s] = $this->%2$s($entry[%1$s] ?? null);',
                        $handleLiteral,
                        self::GET_USER_INFO_METHOD,
                    ),
                    order: $position,
                ),
            );
    }

    private function renderSaveCode(string $targetVariable, string $sourceVariable, string $handleLiteral): string
    {
        return sprintf(
            '$userIdentifier = %1$s[%2$s] ?? null;%3$s'
            . '%4$s[%2$s] = (is_int($userIdentifier) || (is_string($userIdentifier) && ctype_digit($userIdentifier)))%3$s'
            . '    ? max(0, (int) $userIdentifier)%3$s'
            . '    : 0;',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            $targetVariable,
        );
    }

    private function renderValidationCode(
        UserSelectorFieldTypeDto $field,
        string $sourceVariable,
        bool $repeatable,
    ): string {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $entryMessage = $repeatable ? ' in entry %s' : '';
        $entryArgument = $repeatable ? ', $entryPosition + 1' : '';
        $validation = $field->required
            ? sprintf(
                'if (!$hasUserSelection) {%1$s    $errors->add(t(\'The field "%%s" is required%2$s.\', %3$s%4$s));%1$s}'
                . ' elseif (!$userInfo instanceof UserInfo) {%1$s    $errors->add(t(\'The field "%%s" requires a valid user%2$s.\', %3$s%4$s));%1$s}',
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
            )
            : sprintf(
                'if ($hasUserSelection && !$userInfo instanceof UserInfo) {%1$s'
                . '    $errors->add(t(\'The field "%%s" requires a valid user%2$s.\', %3$s%4$s));%1$s}',
                PHP_EOL,
                $entryMessage,
                $label,
                $entryArgument,
            );

        return sprintf(
            '$userIdentifier = %1$s[%2$s] ?? null;%3$s'
            . '$hasUserSelection = $userIdentifier !== null && $userIdentifier !== \'\' && $userIdentifier !== 0 && $userIdentifier !== \'0\';%3$s'
            . '$userInfo = $hasUserSelection ? $this->%4$s($userIdentifier) : null;%3$s'
            . '%5$s',
            $sourceVariable,
            $handleLiteral,
            PHP_EOL,
            self::GET_USER_INFO_METHOD,
            $validation,
        );
    }

    private function renderFormFragment(UserSelectorFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            'fragments/user_selector/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                    ? ''
                    : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
            ],
        );
    }

    private function renderViewFragment(UserSelectorFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            'fragments/user_selector/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $basicField
                ? ['{{HANDLE}}' => $field->handle]
                : ['{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle)],
        );
    }
}
