-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 18/11/2025 às 20:36
-- Versão do servidor: 9.1.0
-- Versão do PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `dbprojalpha`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_pedido`
--

DROP TABLE IF EXISTS `tb_pedido`;
CREATE TABLE IF NOT EXISTS `tb_pedido` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `nome_pedido` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf_pedido` char(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `banco_pedido` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conta_pedido` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `taxa_pedido` decimal(3,1) NOT NULL,
  `meses_pedido` int NOT NULL,
  `capital_pedido` decimal(7,2) NOT NULL,
  `rendimento_pedido` decimal(7,2) NOT NULL,
  `total_pedido` decimal(7,2) NOT NULL,
  `id_user` int DEFAULT NULL,
  PRIMARY KEY (`id_pedido`),
  KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_usuario`
--

DROP TABLE IF EXISTS `tb_usuario`;
CREATE TABLE IF NOT EXISTS `tb_usuario` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `cpf_user` char(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha_user` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `cpf_user` (`cpf_user`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tb_usuario`
--

INSERT INTO `tb_usuario` (`id_user`, `cpf_user`, `nome_user`, `email_user`, `senha_user`) VALUES
(1, '1', 'admin', 'admin@admin', 'adminadm');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
