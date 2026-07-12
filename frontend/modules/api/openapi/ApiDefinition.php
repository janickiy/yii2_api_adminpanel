<?php

declare(strict_types=1);

namespace frontend\modules\api\openapi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Yii2 API Adminpanel',
    description: 'Yii2 rewrite of the Laravel API admin panel with JWT notes API and AdminLTE control panel.',
)]
#[OA\Server(
    url: 'http://localhost:8082',
    description: 'Docker local server',
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
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-07-04 22:30:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-07-04 22:30:00'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Note',
    required: ['id', 'user_id', 'title', 'content', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Планы на день'),
        new OA\Property(property: 'content', type: 'string', example: 'Подготовить релиз и проверить API.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-07-04 22:30:00'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-07-04 22:30:00'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'NotePayload',
    required: ['title', 'content'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Планы на день'),
        new OA\Property(property: 'content', type: 'string', example: 'Подготовить релиз и проверить API.'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed.'),
        new OA\Property(property: 'errors', type: 'object'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'AuthError',
    properties: [
        new OA\Property(property: 'error', type: 'string', example: 'Invalid credentials'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'NotFoundError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Note not found'),
    ],
    type: 'object',
)]
final class ApiDefinition
{
}
