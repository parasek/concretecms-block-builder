<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Security;

use ArrayObject;
use BlockBuilder\Block\Request\CreateBlockInputNormalizer;
use BlockBuilder\Block\Validation\CreateBlockRequestValidator;
use BlockBuilder\Block\Validation\CreateBlockValidatorCollection;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\Validator\Block\CsrfValidator;
use BlockBuilder\Block\Validation\Validator\Block\PermissionsValidator;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Concrete\Core\Validation\CSRF\Token;
use Symfony\Component\HttpFoundation\FileBag;

final class CreateBlockRequestValidationTest extends BlockBuilderTestCase
{
    public function testCsrfFailureStopsTheRequestPipeline(): void
    {
        $calls = new ArrayObject();
        $validator = $this->createRequestValidator(
            csrfFeedback: new ValidationFeedback(errors: ['csrf failure']),
            permissionsFeedback: new ValidationFeedback(errors: ['permission validator must not run']),
            validatorsFeedback: new ValidationFeedback(errors: ['business validators must not run']),
            calls: $calls,
        );

        $result = $validator->validate(['unsupported' => 'value'], new FileBag());

        self::assertSame(['csrf failure'], $result->errors);
        self::assertSame([], $result->data);
        self::assertSame(['csrf'], $calls->getArrayCopy());
    }

    public function testPermissionFailureStopsBeforeNormalization(): void
    {
        $calls = new ArrayObject();
        $validator = $this->createRequestValidator(
            csrfFeedback: new ValidationFeedback(),
            permissionsFeedback: new ValidationFeedback(errors: ['permission failure']),
            validatorsFeedback: new ValidationFeedback(errors: ['business validators must not run']),
            calls: $calls,
        );

        $result = $validator->validate(['unsupported' => 'value'], new FileBag());

        self::assertSame(['permission failure'], $result->errors);
        self::assertSame([], $result->data);
        self::assertSame(['csrf', 'permissions'], $calls->getArrayCopy());
    }

    public function testNormalizationFailureStopsBeforeBusinessValidation(): void
    {
        $calls = new ArrayObject();
        $validator = $this->createRequestValidator(
            csrfFeedback: new ValidationFeedback(),
            permissionsFeedback: new ValidationFeedback(),
            validatorsFeedback: new ValidationFeedback(errors: ['business validators must not run']),
            calls: $calls,
        );

        $result = $validator->validate([
            'unsupported' => 'value',
            'blockName' => 'Example block',
            'basic' => [],
            'entries' => [],
        ], new FileBag());

        self::assertSame(['The request contains an unsupported field.'], $result->errors);
        self::assertSame('Example block', $result->data['blockName']);
        self::assertSame(['csrf', 'permissions'], $calls->getArrayCopy());
    }

    public function testNormalizedDataAndTrustedRebuildSourceReachBusinessValidation(): void
    {
        $calls = new ArrayObject();
        $validator = $this->createRequestValidator(
            csrfFeedback: new ValidationFeedback(),
            permissionsFeedback: new ValidationFeedback(),
            validatorsFeedback: new ValidationFeedback(
                errors: ['business failure'],
                fieldsWithError: ['blockName'],
                tabsWithError: ['block-settings'],
            ),
            calls: $calls,
        );
        $input = [
            'blockName' => 'Example block',
            'cacheBlockRecord' => '1',
            'basic' => [],
            'entries' => [],
        ];
        $originalInput = $input;

        $result = $validator->validate($input, new FileBag(), 'loaded_source');

        self::assertSame(['business failure'], $result->errors);
        self::assertSame(['blockName'], $result->fieldsWithError);
        self::assertSame(['block-settings'], $result->tabsWithError);
        self::assertSame('Example block', $result->data['blockName']);
        self::assertSame('1', $result->data['cacheBlockRecord']);
        self::assertArrayNotHasKey('rebuildSourceHandle', $result->data);
        self::assertSame($originalInput, $input);

        $recordedCalls = $calls->getArrayCopy();
        self::assertSame('csrf', $recordedCalls[0]);
        self::assertSame('permissions', $recordedCalls[1]);
        self::assertSame('validators', $recordedCalls[2]['step']);
        self::assertSame('loaded_source', $recordedCalls[2]['data']['rebuildSourceHandle']);
    }

    /**
     * @dataProvider csrfResultProvider
     */
    public function testCsrfValidatorUsesTheCreateBlockAction(bool $isValid, array $expectedErrors): void
    {
        $token = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMock();
        $token->expects(self::once())
            ->method('validate')
            ->with('create_block')
            ->willReturn($isValid);

        $feedback = (new CsrfValidator($token))->validate([]);

        self::assertSame($expectedErrors, $feedback->errors);
        self::assertSame([], $feedback->fieldsWithError);
        self::assertSame([], $feedback->tabsWithError);
    }

    public static function csrfResultProvider(): array
    {
        return [
            'valid token' => [true, []],
            'invalid token' => [false, ['Invalid CSRF token. Please refresh and try again.']],
        ];
    }

    private function createRequestValidator(
        ValidationFeedback $csrfFeedback,
        ValidationFeedback $permissionsFeedback,
        ValidationFeedback $validatorsFeedback,
        ArrayObject $calls,
    ): CreateBlockRequestValidator {
        $csrfValidator = new readonly class($csrfFeedback, $calls) extends CsrfValidator {
            public function __construct(
                private ValidationFeedback $feedback,
                private ArrayObject $calls,
            ) {
            }

            public function validate(array $data, ?FileBag $files = null): ValidationFeedback
            {
                $this->calls[] = 'csrf';

                return $this->feedback;
            }
        };
        $permissionsValidator = new class($permissionsFeedback, $calls) extends PermissionsValidator {
            public function __construct(
                private readonly ValidationFeedback $feedback,
                private readonly ArrayObject $calls,
            ) {
            }

            public function validate(array $data, ?FileBag $files = null): ValidationFeedback
            {
                $this->calls[] = 'permissions';

                return $this->feedback;
            }
        };
        $validatorCollection = new readonly class($validatorsFeedback, $calls) extends CreateBlockValidatorCollection {
            public function __construct(
                private ValidationFeedback $feedback,
                private ArrayObject $calls,
            ) {
            }

            public function validate(array $data, FileBag $files): ValidationFeedback
            {
                $this->calls[] = [
                    'step' => 'validators',
                    'data' => $data,
                ];

                return $this->feedback;
            }
        };

        return new CreateBlockRequestValidator(
            csrfValidator: $csrfValidator,
            permissionsValidator: $permissionsValidator,
            inputNormalizer: $this->getService(CreateBlockInputNormalizer::class),
            validators: $validatorCollection,
        );
    }
}
