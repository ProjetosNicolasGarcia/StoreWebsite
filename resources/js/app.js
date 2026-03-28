import './bootstrap';

// =========================================================================
// 1. CONFIGURAÇÃO GLOBAL DO AXIOS (Otimização de Performance)
// Remove a necessidade do CDN no <head> e previne bloqueio de renderização
// =========================================================================
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// =========================================================================
// 2. CONFIGURAÇÃO DO ALPINE.JS (Otimização de Performance)
// Injeta o plugin diretamente no bundle do Vite
// =========================================================================
import collapse from '@alpinejs/collapse';

// O Livewire 3 expõe o Alpine globalmente. Injetamos o plugin antes da inicialização.
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
});

// =========================================================================
// 3. LÓGICA DO CABEÇALHO TRANSPARENTE (UI do seu projeto)
// Lógica para o cabeçalho transparente que muda ao rolar na Home
// =========================================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // >>> TRAVA DE SEGURANÇA <<<
    // Verifica se NÃO estamos na Home Page ('/').
    // Se a URL for diferente de '/', paramos o script imediatamente.
    if (window.location.pathname !== '/') {
        return;
    }

    const header = document.getElementById('main-header');
    
    // Segurança extra: se não achar o header, não faz nada
    if (!header) return;

    const headerLinks = header.querySelectorAll('nav a, .header-icons a');
    const headerLogo = document.getElementById('header-logo');

    function updateHeader() {
        if (window.scrollY > 50) {
            // Estado: Rolou para baixo (Fundo Branco, Texto Preto)
            header.classList.remove('bg-transparent', 'text-white', 'py-6');
            header.classList.add('bg-white', 'text-gray-900', 'shadow-md', 'py-4');
            
            if (headerLogo) {
                headerLogo.classList.remove('text-white');
                headerLogo.classList.add('text-gray-900');
            }

            headerLinks.forEach(link => {
                link.classList.remove('text-white', 'hover:text-gray-300');
                link.classList.add('text-gray-600', 'hover:text-black');
            });
        } else {
            // Estado: No topo (Transparente, Texto Branco)
            header.classList.add('bg-transparent', 'text-white', 'py-6');
            header.classList.remove('bg-white', 'text-gray-900', 'shadow-md', 'py-4');

            if (headerLogo) {
                headerLogo.classList.add('text-white');
                headerLogo.classList.remove('text-gray-900');
            }

            headerLinks.forEach(link => {
                link.classList.add('text-white', 'hover:text-gray-300');
                link.classList.remove('text-gray-600', 'hover:text-black');
            });
        }
    }

    // Executa ao carregar e ao rolar
    window.addEventListener('scroll', updateHeader);
    updateHeader(); // Chama uma vez para garantir o estado inicial
});