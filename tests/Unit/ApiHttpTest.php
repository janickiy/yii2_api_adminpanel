<?php

declare(strict_types=1);

namespace tests\Unit;

use common\dtos\LoginUserDto;
use common\dtos\NoteQueryDto;
use common\dtos\NoteWriteDto;
use common\dtos\RegisterUserDto;
use frontend\components\api\RequestInputFactory;
use frontend\components\api\ValidationHttpException;
use frontend\forms\api\NoteWriteInput;
use frontend\forms\api\RegisterInput;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ApiHttpTest extends TestCase
{
    public function testDtosAreSimpleFrameworkIndependentContainers(): void
    {
        foreach (
            [
                LoginUserDto::class,
                RegisterUserDto::class,
                NoteQueryDto::class,
                NoteWriteDto::class,
            ] as $dtoClass
        ) {
            $reflection = new ReflectionClass($dtoClass);
            $file = $reflection->getFileName();

            self::assertTrue($reflection->isFinal());
            self::assertIsString($file);
            self::assertStringNotContainsString('yii\\', (string) file_get_contents($file));
        }
    }

    public function testRequestFactoryReturnsValidatedInput(): void
    {
        $input = (new RequestInputFactory())->fromParams(NoteWriteInput::class, [
            'category_id' => ' 2 ',
            'title' => '  Title  ',
            'content' => '  Content  ',
        ]);
        $dto = $input->toDto();

        self::assertSame(2, $dto->categoryId);
        self::assertSame('Title', $dto->title);
        self::assertSame('Content', $dto->content);
    }

    public function testAuthInputNormalizesTransportValuesBeforeMapping(): void
    {
        $input = (new RequestInputFactory())->fromParams(RegisterInput::class, [
            'name' => '  Alice Example  ',
            'email' => '  ALICE@EXAMPLE.TEST  ',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $dto = $input->toDto();
        self::assertSame('Alice Example', $dto->name);
        self::assertSame('alice@example.test', $dto->email);
        self::assertSame('Secret123!', $dto->password);
    }

    public function testRequestFactoryRaisesStructuredValidationException(): void
    {
        try {
            (new RequestInputFactory())->fromParams(NoteWriteInput::class, [
                'category_id' => 0,
                'title' => '',
                'content' => '',
            ]);
            self::fail('Invalid request parameters must be rejected.');
        } catch (ValidationHttpException $exception) {
            self::assertSame(422, $exception->statusCode);
            self::assertSame('Validation failed.', $exception->getMessage());
            self::assertArrayHasKey('category_id', $exception->fields());
            self::assertArrayHasKey('title', $exception->fields());
            self::assertArrayHasKey('content', $exception->fields());
        }
    }
}
