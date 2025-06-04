async function renderDashboard() {
    try {
        console.log('Rendu du dashboard...');
        
        if (!appData.stats || Object.keys(appData.stats).length === 0) {
            await loadStatistics();
        }

        renderDashboardStats();
        
        await renderRecentDossiers();
        
        await renderPendingActions();
        
        console.log('Dashboard rendu avec succès');
    } catch (error) {
        console.error('Erreur rendu dashboard:', error);
        showErrorToast('Erreur lors du chargement du tableau de bord');
    }
}

function renderDashboardStats() {
    const statsContainer = document.getElementById('dashboard-stats');
    
    if (!appData.stats) {
        statsContainer.innerHTML = '<p class="text-muted">Aucune statistique disponible</p>';
        return;
    }

    const stats = appData.stats;
    
    statsContainer.innerHTML = `
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h3>${stats.total_dossiers || 0}</h3>
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
                        <h3>${stats.dossiers_en_cours || 0}</h3>
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
                        <h3>${stats.dossiers_clotures || 0}</h3>
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
                        <h3>${stats.avocats_disponibles || 0}</h3>
                        <p><i class="fas fa-user-tie me-1"></i>Avocats Disponibles</p>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <h4 class="text-primary">${stats.dossiers_ouverts || 0}</h4>
                            <small class="text-muted">Ouverts</small>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-warning">${stats.dossiers_en_cours || 0}</h4>
                            <small class="text-muted">En Cours</small>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-success">${stats.dossiers_clotures || 0}</h4>
                            <small class="text-muted">Clôturés</small>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-secondary">${stats.dossiers_archives || 0}</h4>
                            <small class="text-muted">Archivés</small>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-info">${stats.taux_cloture || 0}%</h4>
                            <small class="text-muted">Taux Clôture</small>
                        </div>
                        <div class="col-md-2">
                            <h4 class="text-primary">${stats.avocats_certifies || 0}</h4>
                            <small class="text-muted">Avocats Certifiés</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

async function renderRecentDossiers() {
    const container = document.getElementById('recent-dossiers');
    
    try {
        if (!appData.dossiers || appData.dossiers.length === 0) {
            await loadDossiers();
        }

        const recentDossiers = appData.dossiers
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            .slice(0, 5);

        if (recentDossiers.length === 0) {
            container.innerHTML = '<p class="text-muted">Aucun dossier récent</p>';
            return;
        }

        const dossiersHtml = recentDossiers.map(dossier => `
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${dossier.titre}</h6>
                    <small class="text-muted">
                        <i class="fas fa-hashtag me-1"></i>${dossier.numero_dossier}
                        ${dossier.client ? `• <i class="fas fa-user me-1"></i>${dossier.client.nom}` : ''}
                    </small>
                </div>
                <div class="text-end">
                    <span class="badge status-${dossier.statut}">${formatStatus(dossier.statut)}</span>
                    <br>
                    <small class="text-muted">${formatDate(dossier.date_ouverture)}</small>
                </div>
            </div>
        `).join('');

        container.innerHTML = dossiersHtml;
        
    } catch (error) {
        console.error('Erreur rendu dossiers récents:', error);
        container.innerHTML = '<p class="text-danger">Erreur lors du chargement</p>';
    }
}

async function renderPendingActions() {
    const container = document.getElementById('pending-actions');
    
    try {
        const etapesEnAttente = await api.getEtapesByStatus('en_attente');
        const etapesEnRetard = await api.getEtapesEnRetard();
        
        const actions = [];
        
        etapesEnRetard.forEach(etape => {
            actions.push({
                type: 'retard',
                title: `Étape en retard: ${etape.nom}`,
                subtitle: `Dossier: ${etape.dossier_titre || 'N/A'}`,
                date: etape.date_fin_prevue,
                urgency: 'danger',
                icon: 'exclamation-triangle'
            });
        });
        
        etapesEnAttente.slice(0, 3).forEach(etape => {
            actions.push({
                type: 'attente',
                title: `Étape en attente: ${etape.nom}`,
                subtitle: `Dossier: ${etape.dossier_titre || 'N/A'}`,
                date: etape.date_fin_prevue,
                urgency: 'warning',
                icon: 'clock'
            });
        });

        if (actions.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                    <p class="text-muted">Aucune action en attente</p>
                </div>
            `;
            return;
        }

        const actionsHtml = actions.map(action => `
            <div class="d-flex align-items-start border-bottom py-2">
                <div class="me-2">
                    <i class="fas fa-${action.icon} text-${action.urgency}"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">${action.title}</h6>
                    <small class="text-muted">${action.subtitle}</small>
                    ${action.date ? `<br><small class="text-${action.urgency}">Échéance: ${formatDate(action.date)}</small>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = actionsHtml;
        
    } catch (error) {
        console.error('Erreur rendu actions en attente:', error);
        container.innerHTML = '<p class="text-danger">Erreur lors du chargement</p>';
    }
}

function formatStatus(status) {
    const statusMap = {
        'ouvert': 'Ouvert',
        'en_cours': 'En Cours',
        'cloture': 'Clôturé',
        'archive': 'Archivé'
    };
    return statusMap[status] || status;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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