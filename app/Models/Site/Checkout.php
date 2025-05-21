<?php

namespace App\Models\Site;

use App\Core\Database;
use PDO;

class Checkout
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }

    public function criarPedido(array $dados): int
    {
        $clienteId = $dados['cliente_id'];
        $freteSelecionado = explode(':', $dados['frete_selecionado']);
        $freteNome = $freteSelecionado[0] ?? '';
        $freteValor = (float) ($freteSelecionado[1] ?? 0);
        $fretePrazo = $freteSelecionado[2] ?? '';

        $carrinho = new Carrinho();
        $produtos = $carrinho->listarProdutos();
        $subtotal = $carrinho->calcularTotal();
        $total = $subtotal + $freteValor;

        $stmt = $this->pdo->prepare("
            INSERT INTO pedidos (cliente_id, frete_tipo, frete_valor, frete_prazo, forma_pagamento, subtotal, total, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Aguardando Pagamento')
        ");
        $stmt->execute([
            $clienteId,
            $freteNome,
            $freteValor,
            $fretePrazo,
            $dados['forma_pagamento'],
            $subtotal,
            $total
        ]);

        $pedidoId = $this->pdo->lastInsertId();

        // Grava os itens corretamente
        foreach ($produtos as $produto) {
            $stmt = $this->pdo->prepare("
                INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $pedidoId,
                $produto['id'],
                $produto['quantidade'],
                $produto['preco']
            ]);
        }

        // Limpa carrinho e frete
        $_SESSION['carrinho'] = [];
        $_SESSION['frete'] = 0;

        return (int) $pedidoId;
    }

    public function buscarPorId(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $pedido['itens'] = $this->buscarItens($id);
        }

        return $pedido;
    }

    public function buscarItens(int $pedidoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                ip.produto_id,
                ip.quantidade,
                ip.preco_unitario,
                p.nome AS nome_produto
            FROM itens_pedido ip
            JOIN produtos p ON p.id = ip.produto_id
            WHERE ip.pedido_id = ?
        ");
        $stmt->execute([$pedidoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarClientePorEmail(string $email): array|false
    {
        $stmt = $this->pdo->prepare("SELECT id FROM pedidos WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
