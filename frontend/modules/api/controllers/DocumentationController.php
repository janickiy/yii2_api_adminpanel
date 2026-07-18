<?php

declare(strict_types=1);

namespace frontend\modules\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class DocumentationController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex(): string
    {
        Yii::$app->response->format = Response::FORMAT_HTML;

        return <<<'HTML'
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Yii2 API Swagger</title>
    <link rel="stylesheet" href="/swagger-ui/swagger-ui.css">
    <link rel="icon" type="image/png" href="/swagger-ui/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/swagger-ui/favicon-16x16.png" sizes="16x16">
    <style>
        body { margin: 0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="/swagger-ui/swagger-ui-bundle.js"></script>
    <script src="/swagger-ui/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: '/docs',
                dom_id: '#swagger-ui',
                deepLinking: true,
                persistAuthorization: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: 'StandaloneLayout'
            });
        };
    </script>
</body>
</html>
HTML;
    }

    public function actionSpec(): Response
    {
        $specFile = Yii::getAlias('@api/openapi/openapi.yaml');

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/yaml; charset=UTF-8');
        Yii::$app->response->content = (string) file_get_contents($specFile);

        return Yii::$app->response;
    }
}
