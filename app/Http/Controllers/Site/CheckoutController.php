<?php

namespace App\Http\Controllers\Site;

use App\Core\Container;
use App\Models\Admin\Configuracao;
use App\Models\Site\Carrinho;
use App\Models\Site\Checkout;
use App\Models\Site\Cliente;

class CheckoutController
{
    private $twig;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->twig = Container::makeTwig();
    }

    public function index(): void
    {
        $configModel = new Configuracao();
        $config = $configModel->buscar();

        $carrinho = new Carrinho();
        $produtos = $carrinho->listarProdutos();
        $subtotal = $carrinho->calcularTotal();
        $frete = $_SESSION['frete'] ?? 0;
        $totalGeral = $subtotal + $frete;

        echo $this->twig->render('site/themes/default/checkout/index.twig', [
            'config' => $config,
            'produtos' => $produtos,
            'subtotal' => $subtotal,
            'frete' => $frete,
            'total_geral' => $totalGeral
        ]);
    }

    public function validarCliente(): void
    {
        header('Content-Type: application/json');
        $email = $_GET['email'] ?? '';
        $clienteModel = new Cliente();
        $cliente = $clienteModel->buscarPorEmail($email);
        echo json_encode(['status' => 'ok', 'existe' => $cliente ? true : false]);
    }

    public function finalizarPedido(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido');
        }

        $clienteModel = new Cliente();

        $email = $_POST['email'] ?? '';
        $cliente = $clienteModel->buscarPorEmail($email);

        if (!$cliente) {
            $camposObrigatorios = ['nome', 'cpf', 'telefone', 'cep', 'rua', 'numero', 'bairro', 'cidade', 'estado'];
            foreach ($camposObrigatorios as $campo) {
                if (empty($_POST[$campo])) {
                    $_SESSION['error'] = "Preencha todos os dados de cadastro";
                    header("Location: " . base_url() . "/checkout");
                    exit;
                }
            }

            $novoCliente = [
                'nome' => $_POST['nome'],
                'email' => $_POST['email'],
                'telefone' => $_POST['telefone'],
                'cpf' => $_POST['cpf'],
                'rua' => $_POST['rua'],
                'numero_endereco' => $_POST['numero'],
                'bairro' => $_POST['bairro'],
                'cidade' => $_POST['cidade'],
                'estado' => $_POST['estado'],
                'cep' => $_POST['cep']
            ];
            $clienteId = $clienteModel->salvar($novoCliente);
        } else {
            $clienteId = $cliente['id'];
        }

        $pedido = [
            'cliente_id' => $clienteId,
            'frete_selecionado' => $_POST['frete_selecionado'] ?? '',
            'forma_pagamento' => $_POST['forma_pagamento'] ?? ''
        ];

        $checkout = new Checkout();
        $pedidoId = $checkout->criarPedido($pedido);

        header("Location: " . base_url() . "/confirmacao/{$pedidoId}");
        exit;
    }

    public function confirmacao($id): void
    {
        $checkout = new Checkout();
        $pedido = $checkout->buscarPorId($id);

        $configModel = new Configuracao();
        $config = $configModel->buscar();

        echo $this->twig->render('site/themes/default/checkout/confirmacao.twig', [
            'pedido' => $pedido,
            'config' => $config
        ]);
    }

    public function baixarPdf($numero): void
    {
        echo "PDF gerado para o pedido {$numero}";
    }
}
