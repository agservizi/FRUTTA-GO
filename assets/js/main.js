// main.js - JavaScript principale

// Toast notifications
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');

    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };

    toast.className = `${colors[type]} text-white px-4 py-2 rounded-md shadow-md mb-2`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// API wrapper
class Api {
    static async request(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = { ...defaultOptions, ...options };

        try {
            const response = await fetch(endpoint, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Errore API');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    static async get(endpoint) {
        return this.request(endpoint);
    }

    static async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    static async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    static async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE',
        });
    }
}

// Cache semplice
class Cache {
    static set(key, data, ttl = 300000) { // 5 minuti default
        const item = {
            data: data,
            timestamp: Date.now(),
            ttl: ttl
        };
        localStorage.setItem(key, JSON.stringify(item));
    }

    static get(key) {
        const item = localStorage.getItem(key);
        if (!item) return null;

        const parsed = JSON.parse(item);
        if (Date.now() - parsed.timestamp > parsed.ttl) {
            localStorage.removeItem(key);
            return null;
        }

        return parsed.data;
    }

    static clear() {
        localStorage.clear();
    }
}

// Utility per formattare prezzi
function formatPrice(price) {
    const currencySymbol = window.appSettings?.currency_symbol || '€';
    return `${currencySymbol}${parseFloat(price).toFixed(2)}`;
}

// Utility per formattare date
function formatDate(dateString) {
    return new Date(dateString).toLocaleString('it-IT');
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', () => {
    // Aggiungi CSRF token a tutti i form
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    if (csrfToken) {
        // Salva per usi futuri
        window.csrfToken = csrfToken.value;
    }

    // Header scroll effect
    const header = document.getElementById('main-header');
    if (header) {
        header.classList.add('bg-white');
        header.classList.remove('bg-transparent');
        window.addEventListener('scroll', () => {
            header.classList.add('bg-white');
            header.classList.remove('bg-transparent');
        });
    }
});