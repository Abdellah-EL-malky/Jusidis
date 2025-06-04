function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (error) {
        return 'Date invalide';
    }
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        return 'Date invalide';
    }
}

function formatStatus(status) {
    const statusMap = {
        'ouvert': 'Ouvert',
        'en_cours': 'En Cours',
        'cloture': 'Clôturé',
        'archive': 'Archivé',
        'actif': 'Actif',
        'inactif': 'Inactif'
    };
    return statusMap[status] || status;
}

function formatPriority(priority) {
    const priorityMap = {
        'basse': 'Basse',
        'normale': 'Normale',
        'haute': 'Haute',
        'urgente': 'Urgente'
    };
    return priorityMap[priority] || priority;
}

function formatActionType(actionType) {
    const actionMap = {
        'creation': 'Création',
        'changement_avocat': 'Changement d\'avocat',
        'ajout_document': 'Ajout de document',
        'validation_etape': 'Validation d\'étape',
        'changement_statut': 'Changement de statut',
        'cloture': 'Clôture'
    };
    return actionMap[actionType] || actionType;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[0-9+\-\s\.]{10,}$/;
    return re.test(phone);
}

function validateRequired(value) {
    return value && value.trim() !== '';
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    field.classList.remove('is-valid');
    field.classList.add('is-invalid');

    let feedback = field.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.appendChild(feedback);
    }
    
    feedback.textContent = message;
}

function showFieldSuccess(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.textContent = '';
    }
}

function clearFieldValidation(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    field.classList.remove('is-valid', 'is-invalid');
    
    const feedback = field.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.textContent = '';
    }
}

function createModal(id, title, body, footer = '') {
    const modalHtml = `
        <div class="modal fade" id="${id}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${body}
                    </div>
                    ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
                </div>
            </div>
        </div>
    `;

    const existingModal = document.getElementById(id);
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    return new bootstrap.Modal(document.getElementById(id));
}

function showConfirmModal(title, message, onConfirm, onCancel = null) {
    const modalId = 'confirmModal';
    const footer = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-danger" id="confirmBtn">Confirmer</button>
    `;

    const modal = createModal(modalId, title, `<p>${message}</p>`, footer);
    
    document.getElementById('confirmBtn').addEventListener('click', () => {
        modal.hide();
        if (onConfirm) onConfirm();
    });

    modal.show();
}

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

function formatNumber(number) {
    if (number === null || number === undefined) return 'N/A';
    return number.toLocaleString('fr-FR');
}

function formatCurrency(amount) {
    if (amount === null || amount === undefined) return 'N/A';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

function getStatusColor(status) {
    const colors = {
        'ouvert': 'primary',
        'en_cours': 'warning',
        'cloture': 'success',
        'archive': 'secondary',
        'actif': 'success',
        'inactif': 'danger',
        'en_attente': 'secondary',
        'validee': 'success',
        'annulee': 'danger'
    };
    return colors[status] || 'light';
}

function getPriorityColor(priority) {
    const colors = {
        'basse': 'success',
        'normale': 'primary',
        'haute': 'warning',
        'urgente': 'danger'
    };
    return colors[priority] || 'secondary';
}

function calculatePercentage(value, total) {
    if (!total || total === 0) return 0;
    return Math.round((value / total) * 100);
}

function calculateDaysBetween(date1, date2) {
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    const diffTime = Math.abs(d2 - d1);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function saveUserPreference(key, value) {
    try {
        localStorage.setItem(`cabinet_${key}`, JSON.stringify(value));
    } catch (error) {
        console.warn('Impossible de sauvegarder la préférence:', error);
    }
}

function getUserPreference(key, defaultValue = null) {
    try {
        const stored = localStorage.getItem(`cabinet_${key}`);
        return stored ? JSON.parse(stored) : defaultValue;
    } catch (error) {
        console.warn('Impossible de charger la préférence:', error);
        return defaultValue;
    }
}

function handleApiError(error) {
    console.error('Erreur API:', error);
    
    if (error.message) {
        showErrorToast(error.message);
    } else if (error.status) {
        switch (error.status) {
            case 400:
                showErrorToast('Données invalides');
                break;
            case 401:
                showErrorToast('Non autorisé');
                break;
            case 403:
                showErrorToast('Accès interdit');
                break;
            case 404:
                showErrorToast('Ressource non trouvée');
                break;
            case 409:
                showErrorToast('Conflit - Action impossible');
                break;
            case 500:
                showErrorToast('Erreur serveur');
                break;
            default:
                showErrorToast('Erreur de communication');
        }
    } else {
        showErrorToast('Erreur inattendue');
    }
}

function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

function sanitizeString(str) {
    if (!str) return '';
    return str.toString().trim();
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatDate,
        formatDateTime,
        formatStatus,
        formatPriority,
        validateEmail,
        validatePhone,
        debounce,
        calculatePercentage
    };
}