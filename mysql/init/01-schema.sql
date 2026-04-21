-- Corre só na primeira inicialização do volume de dados do MySQL
-- (ficheiros em /docker-entrypoint-initdb.d quando datadir está vazio).
-- O mesmo DDL existe em src/includes/ensure_alimentos_table.php para volumes antigos.

USE `meu_banco`;

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
);
