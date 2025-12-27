<div align="center">

  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" alt="Logo" width="200"/>

  # 🛒 StoreWebsite E-commerce

  <p>
    Uma plataforma de e-commerce moderna, robusta e escalável.
  </p>

  <p>
    <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12"></a>
    <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2"></a>
    <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-v3-FDAE4B?style=for-the-badge&logo=filament&logoColor=white" alt="FilamentPHP"></a>
    <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS"></a>
  </p>
</div>

<br />

## 📖 Sobre o Projeto

O **StoreWebsite** é uma solução completa de loja virtual desenvolvida para oferecer uma experiência de compra fluida para os clientes e uma gestão poderosa para os administradores.

O projeto utiliza o **Filament PHP** para criar um painel administrativo intuitivo, permitindo o gerenciamento total de produtos, pedidos, clientes e conteúdo do site, enquanto o front-end é construído com **Blade**, **Tailwind CSS** e **Alpine.js** para máxima performance e responsividade.

---

## ✨ Funcionalidades Principais

### 🛍️ Para o Cliente (Front-end)
- **Vitrine Moderna:** Layout responsivo com banners estilo "Hero" e destaque de produtos.
- **Navegação Intuitiva:** Menu dinâmico de categorias e busca eficiente.
- **Interatividade:** Carrinho de compras, lista de favoritos e zoom interativo em produtos.
- **Detalhes do Produto:** Visualização de variações, preços e avaliações.

### ⚙️ Painel Administrativo (Back-end)
- **Dashboard:** Visão geral com gráficos de vendas e estatísticas (`StatsOverview`, `SalesChart`).
- **Catálogo:** Gestão completa de Produtos, Variantes, Categorias e Banners.
- **Vendas:** Controle de Pedidos (`OrderResource`) e Transações.
- **Usuários:** Gestão de Clientes, Endereços e Avaliações (`Reviews`).

---

## 🚀 Tecnologias Utilizadas

Este projeto foi desenvolvido com as tecnologias mais recentes do ecossistema PHP:

| Tecnologia | Função |
| :--- | :--- |
| **Laravel 12** | Framework PHP robusto para o back-end. |
| **Filament v3** | Criação rápida do painel administrativo (TALL Stack). |
| **Tailwind CSS** | Estilização utilitária e design responsivo. |
| **Alpine.js** | Interatividade leve no front-end (Menus, Modais). |
| **SQLite / MySQL** | Banco de dados (configurável). |
| **Vite** | Bundler de assets para front-end ultra-rápido. |

---

## 📸 Screenshots

<div align="center">
  <p><i>(Adicione prints da Home e do Dashboard aqui)</i></p>
</div>

---

## 🛠️ Instalação e Configuração

Siga os passos abaixo para rodar o projeto localmente:

### Pré-requisitos
- PHP 8.2 ou superior
- Composer
- Node.js & NPM

### Passo a Passo

1. **Clone o repositório**
   ```bash
   git clone [https://github.com/seu-usuario/storewebsite.git](https://github.com/seu-usuario/storewebsite.git)
   cd storewebsite
