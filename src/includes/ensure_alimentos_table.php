<?php

declare(strict_types=1);

/**
 * Bases criadas antes da tabela `alimentos` não voltam a executar
 * docker-entrypoint-initdb.d. Este passo é idempotente (IF NOT EXISTS).
 * Manter alinhado com mysql/init/01-schema.sql.
 */
function garantir_tabela_alimentos(mysqli $conn): void
{
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `alimentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cadastrado_por` VARCHAR(120) NOT NULL,
    `nome` VARCHAR(200) NOT NULL,
    `distribuidora` VARCHAR(200) NOT NULL,
    `data_recebimento` DATE NOT NULL,
    `data_fabricacao` DATE NOT NULL,
    `data_validade` DATE NOT NULL,
    `perecivel` TINYINT(1) NOT NULL DEFAULT 0,
    `preco_custo` DECIMAL(10,2) NOT NULL,
    `criado_em` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
)
SQL;

    try {
        $conn->query($sql);
        $chk = $conn->query("SHOW COLUMNS FROM `alimentos` LIKE 'preco_venda'");
        if ($chk instanceof mysqli_result && $chk->num_rows > 0) {
            $conn->query('ALTER TABLE `alimentos` DROP COLUMN `preco_venda`');
        }
        if ($chk instanceof mysqli_result) {
            $chk->free();
        }
    } catch (mysqli_sql_exception $e) {
        exit('Erro ao preparar a base de dados: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}
