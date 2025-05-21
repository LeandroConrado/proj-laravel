<?php


// Inicia sessão de forma segura e compatível com ambientes modernos
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Autoload do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Funções auxiliares
require_once __DIR__ . '/../app/Core/Helpers.php';

use App\Core\Router;
use Dotenv\Dotenv;

// Carrega variáveis de ambiente do .env
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Registra as rotas da aplicação
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/admin.php';
require_once __DIR__ . '/../routes/api.php';

// Dispara o roteador
Router::dispatch();
