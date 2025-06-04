document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Cabinet Juridique Pro - Initialisation...');
    
    initializeApp();
});

function initializeApp() {
    initializeEventListeners();
    
    showSection('dashboard');
    
    loadBasicData();
}

function initializeEventListeners() {
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            if (section) {
                showSection(section);
                updateActiveNavLink(this);
            }
        });
    });

    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        });
    }

    const btnNouveauDossier = document.getElementById('btn-nouveau-dossier');
    if (btnNouveauDossier) {
        btnNouveauDossier.addEventListener('click', () => {
            console.log('🔧 Création de dossier - À implémenter');
            showInfoToast('Fonctionnalité en cours de développement');
        });
    }

    const btnNouveauClient = document.getElementById('btn-nouveau-client');
    if (btnNouveauClient) {
        btnNouveauClient.addEventListener('click', () => {
            console.log('🔧 Création de client - À implémenter');
            showInfoToast('Fonctionnalité en cours de développement');
        });
    }

    const btnNouvelAvocat = document.getElementById('btn-nouvel-avocat');
    if (btnNouvelAvocat) {
        btnNouvelAvocat.addEventListener('click', () => {
            console.log('🔧 Création d\'avocat - À implémenter');
            showInfoToast('Fonctionnalité en cours de développement');
        });
    }

    setupSearchFields();
}

function setupSearchFields() {
    const searchDossiers = document.getElementById('search-dossiers');
    if (searchDossiers) {
        searchDossiers.addEventListener('input', debounce((e) => {
            console.log('Recherche dossiers:', e.target.value);
            showInfoToast('Recherche en cours de développement');
        }, 300));
    }

    const searchClients = document.getElementById('search-clients');
    if (searchClients) {
        searchClients.addEventListener('input', debounce((e) => {
            console.log('Recherche clients:', e.target.value);
            showInfoToast('Recherche en cours de développement');
        }, 300));
    }

    const searchAvocats = document.getElementById('search-avocats');
    if (searchAvocats) {
        searchAvocats.addEventListener('input', debounce((e) => {
            console.log('Recherche avocats:', e.target.value);
            showInfoToast('Recherche en cours de développement');
        }, 300));
    }
}

function showSection(sectionName) {
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('d-none');
    });

    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.remove('d-none');
        currentSection = sectionName;

        loadSectionContent(sectionName);
    }
}

function updateActiveNavLink(activeLink) {
    // Retirer la classe active de tous les liens
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.classList.remove('active');
    });

    // Ajouter la classe active au lien cliqué
    activeLink.classList.add('active');
}

function loadBasicData() {
    console.log('Chargement des données de base...');
    
    appData.clients = [];
    appData.avocats = [];
    appData.dossiers = [];
    appData.etapes = [];
    appData.historique = [];
    appData.stats = {
        total_dossiers: 0,
        dossiers_ouverts: 0,
        dossiers_en_cours: 0,
        dossiers_clotures: 0,
        dossiers_archives: 0,
        total_clients: 0,
        total_avocats: 0,
        avocats_disponibles: 0,
        avocats_certifies: 0,
        taux_cloture: 0
    };

    showSuccessToast('Application initialisée avec succès !');
}

function loadSectionContent(sectionName) {
    try {
        switch (sectionName) {
            case 'dashboard':
                loadDashboardContent();
                break;
            case 'dossiers':
                loadDossiersContent();
                break;
            case 'clients':
                loadClientsContent();
                break;
            case 'avocats':
                loadAvocatsContent();
                break;
            case 'etapes':
                loadEtapesContent();
                break;
            case 'historique':
                loadHistoriqueContent();
                break;
            case 'statistiques':
                loadStatistiquesContent();
                break;
            default:
                console.log(`Section ${sectionName} non reconnue`);
        }
    } catch (error) {
        console.error(`Erreur lors du chargement de la section ${sectionName}:`, error);
        showErrorToast(`Erreur lors du chargement de ${sectionName}`);
    }
}

function loadDashboardContent() {
    console.log('Chargement du dashboard...');
    
    const statsContainer = document.getElementById('dashboard-stats');
    if (statsContainer) {
        statsContainer.innerHTML = `
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3>0</h3>
                            <p><i class="fas fa-folder-open me-1"></i>Total Dossiers</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-folder-open"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #43a047 0%, #66bb6a 100%);">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3>0</h3>
                            <p><i class="fas fa-play me-1"></i>En Cours</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #fb8c00 0%, #ffb74d 100%);">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3>0</h3>
                            <p><i class="fas fa-check me-1"></i>Clôturés</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #8e24aa 0%, #ab47bc 100%);">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3>0</h3>
                            <p><i class="fas fa-user-tie me-1"></i>Avocats Disponibles</p>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    const recentContainer = document.getElementById('recent-dossiers');
    if (recentContainer) {
        recentContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-folder-open fs-1 text-muted mb-3"></i>
                <p class="text-muted">Aucun dossier récent pour le moment</p>
                <small class="text-muted">Les dossiers apparaîtront ici une fois créés</small>
            </div>
        `;
    }

    const actionsContainer = document.getElementById('pending-actions');
    if (actionsContainer) {
        actionsContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                <p class="text-muted">Aucune action en attente</p>
                <small class="text-muted">Excellent travail !</small>
            </div>
        `;
    }
}

function loadDossiersContent() {
    console.log('Chargement des dossiers...');
    
    const container = document.getElementById('dossiers-list');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-folder-open fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">Aucun dossier trouvé</h5>
                <p class="text-muted">Commencez par créer votre premier dossier juridique</p>
                <button class="btn btn-primary" onclick="showInfoToast('Fonctionnalité en développement')">
                    <i class="fas fa-plus me-1"></i>Créer un Dossier
                </button>
            </div>
        `;
    }
}

function loadClientsContent() {
    console.log('👥 Chargement des clients...');
    
    const container = document.getElementById('clients-list');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-users fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">Aucun client enregistré</h5>
                <p class="text-muted">Ajoutez vos premiers clients pour commencer</p>
                <button class="btn btn-primary" onclick="showInfoToast('Fonctionnalité en développement')">
                    <i class="fas fa-plus me-1"></i>Ajouter un Client
                </button>
            </div>
        `;
    }
}

function loadAvocatsContent() {
    console.log('Chargement des avocats...');
    
    const container = document.getElementById('avocats-list');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-user-tie fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">Aucun avocat enregistré</h5>
                <p class="text-muted">Ajoutez les avocats de votre cabinet</p>
                <button class="btn btn-primary" onclick="showInfoToast('Fonctionnalité en développement')">
                    <i class="fas fa-plus me-1"></i>Ajouter un Avocat
                </button>
            </div>
        `;
    }
}

function loadEtapesContent() {
    console.log('Chargement des étapes...');
    
    const containers = ['etapes-en-attente', 'etapes-en-cours', 'etapes-validees'];
    containers.forEach(containerId => {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="text-center py-3">
                    <p class="text-muted mb-0">Aucune étape</p>
                </div>
            `;
        }
    });
}

function loadHistoriqueContent() {
    console.log('Chargement de l\'historique...');
    
    const container = document.getElementById('historique-list');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-history fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">Aucun historique disponible</h5>
                <p class="text-muted">L'historique des actions apparaîtra ici</p>
            </div>
        `;
    }
}

function loadStatistiquesContent() {
    console.log('Chargement des statistiques...');
    
    const container = document.getElementById('detailed-stats');
    if (container) {
        container.innerHTML = `
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-chart-bar fs-1 text-muted mb-3"></i>
                            <h5 class="text-muted">Statistiques détaillées</h5>
                            <p class="text-muted">Les statistiques apparaîtront ici une fois que vous aurez des données</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
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

function showSuccessToast(message) {
    const toast = document.getElementById('toast');
    const toastBody = toast.querySelector('.toast-body');
    toastBody.innerHTML = `
        <div class="d-flex align-items-center text-success">
            <i class="fas fa-check-circle me-2"></i>
            ${message}
        </div>
    `;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

function showErrorToast(message) {
    const toast = document.getElementById('toast');
    const toastBody = toast.querySelector('.toast-body');
    toastBody.innerHTML = `
        <div class="d-flex align-items-center text-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            ${message}
        </div>
    `;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

function showInfoToast(message) {
    const toast = document.getElementById('toast');
    const toastBody = toast.querySelector('.toast-body');
    toastBody.innerHTML = `
        <div class="d-flex align-items-center text-info">
            <i class="fas fa-info-circle me-2"></i>
            ${message}
        </div>
    `;
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
}

window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    showErrorToast('Une erreur inattendue s\'est produite');
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Promesse rejetée:', e.reason);
    showErrorToast('Erreur de communication avec le serveur');
});

console.log('Application initialisée avec succès');