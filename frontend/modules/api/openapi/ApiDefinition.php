<?php

declare(strict_types=1);

namespace frontend\modules\api\openapi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Notes Service API',
    description: 'JWT REST API for categorized user notes.',
)]
#[OA\Server(
    url: '/',
    description: 'Current server',
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Регистрация, авторизация и выход',
)]
#[OA\Tag(
    name: 'Notes',
    description: 'Работа с заметками авторизованного пользователя',
)]
#[OA\Tag(
    name: 'Categories',
    description: 'Категории заметок',
)]
#[OA\Tag(
    name: 'System',
    description: 'Служебные endpoints API',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'JWT token from /api/v1/login',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Иван Иванов'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-07-04T22:30:00+00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-07-04T22:30:00+00:00'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Note',
    required: ['id', 'user_id', 'category_id', 'title', 'content'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'category_id', type: 'integer', example: 2),
        new OA\Property(property: 'title', type: 'string', example: 'Планы на день'),
        new OA\Property(property: 'content', type: 'string', example: 'Подготовить релиз и проверить API.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-07-04T22:30:00+00:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-07-04T22:30:00+00:00'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'NotePayload',
    required: ['category_id', 'title', 'content'],
    properties: [
        new OA\Property(property: 'category_id', type: 'integer', minimum: 1, example: 2),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Планы на день'),
        new OA\Property(property: 'content', type: 'string', example: 'Подготовить релиз и проверить API.'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Работа'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'error', type: 'object'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    properties: [
        new OA\Property(property: 'error', type: 'object'),
    ],
    type: 'object',
)]
final class ApiDefinition
{
}
