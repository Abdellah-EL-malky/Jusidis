async function renderDossiers() {
    try {
        console.log('Rendu de la section dossiers...');
        
        if (!appData.dossiers || appData.dossiers.length === 0) {
            await loadDossiers();
        }

        renderDossiersTable(appData.dossiers);
        
    } catch (error) {
        console.error('Erreur rendu dossiers:', error);
        const container = document.getElementById('dossiers-list');
        container.innerHTML = '<p class="text-danger">Erreur lors du chargement des dossiers</p>';
    }
}

function renderDossiersTable(dossiers) {
    const container = document.getElementById('dossiers-list');
    
    if (!dossiers || dossiers.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-folder-open fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">Aucun dossier trouvé</h5>
                <p class="text-muted">Commencez par créer votre premier dossier juridique</p>
                <button class="btn btn-primary" onclick="showCreateDossierModal()">
                    <i class="fas fa-plus me-1"></i>Créer un Dossier
                </button>
            </div>
        `;
        return;
    }

    const tableHtml = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-1"></i>Numéro</th>
                        <th><i class="fas fa-file-alt me-1"></i>Titre</th>
                        <th><i class="fas fa-user me-1"></i>Client</th>
                        <th><i class="fas fa-user-tie me-1"></i>Avocat</th>
                        <th><i class="fas fa-flag me-1"></i>Priorité</th>
                        <th><i class="fas fa-traffic-light me-1"></i>Statut</th>
                        <th><i class="fas fa-calendar me-1"></i>Date Ouverture</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${dossiers.map(dossier => createDossierRow(dossier)).join('')}
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = tableHtml;
}

function createDossierRow(dossier) {
    const clientNom = dossier.client ? dossier.client.nom : 'Non assigné';
    const avocatNom = dossier.avocat ? dossier.avocat.nom : 'Non assigné';
    const priorityClass = `priority-${dossier.priorite}`;
    const statusClass = `status-${dossier.statut}`;

    return `
        <tr>
            <td>
                <strong>${dossier.numero_dossier}</strong>
            </td>
            <td>
                <div>
                    <strong>${dossier.titre}</strong>
                    ${dossier.description ? `<br><small class="text-muted">${truncateText(dossier.description, 50)}</small>` : ''}
                </div>
            </td>
            <td>
                <i class="fas fa-user me-1"></i>${clientNom}
            </td>
            <td>
                ${avocatNom !== 'Non assigné' ? 
                    `<i class="fas fa-user-tie me-1"></i>${avocatNom}` : 
                    `<span class="text-muted"><i class="fas fa-user-slash me-1"></i>Non assigné</span>`
                }
            </td>
            <td>
                <i class="fas fa-flag ${priorityClass} me-1"></i>
                <span class="${priorityClass}">${formatPriority(dossier.priorite)}</span>
            </td>
            <td>
                <span class="badge ${statusClass}">${formatStatus(dossier.statut)}</span>
            </td>
            <td>
                <i class="fas fa-calendar me-1"></i>${formatDate(dossier.date_ouverture)}
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" onclick="viewDossier(${dossier.id})" title="Voir détails">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="editDossier(${dossier.id})" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Plus d'actions">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            ${createDossierActionsMenu(dossier)}
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

function createDossierActionsMenu(dossier) {
    let actions = [];

    if (dossier.statut === 'ouvert' || dossier.statut === 'en_cours') {
        if (!dossier.avocat_id) {
            actions.push(`<li><a class="dropdown-item" href="#" onclick="assignAvocat(${dossier.id})"><i class="fas fa-user-plus me-2"></i>Assigner Avocat</a></li>`);
        } else {
            actions.push(`<li><a class="dropdown-item" href="#" onclick="changeAvocat(${dossier.id})"><i class="fas fa-user-edit me-2"></i>Changer Avocat</a></li>`);
        }
        
        if (dossier.peut_etre_cloture) {
            actions.push(`<li><a class="dropdown-item text-success" href="#" onclick="closeDossier(${dossier.id})"><i class="fas fa-check me-2"></i>Clôturer</a></li>`);
        }
        
        actions.push(`<li><a class="dropdown-item" href="#" onclick="viewEtapes(${dossier.id})"><i class="fas fa-tasks me-2"></i>Voir Étapes</a></li>`);
    }

    if (dossier.statut === 'cloture') {
        actions.push(`<li><a class="dropdown-item text-warning" href="#" onclick="reopenDossier(${dossier.id})"><i class="fas fa-undo me-2"></i>Réouvrir</a></li>`);
        actions.push(`<li><a class="dropdown-item" href="#" onclick="archiveDossier(${dossier.id})"><i class="fas fa-archive me-2"></i>Archiver</a></li>`);
    }

    actions.push(`<li><hr class="dropdown-divider"></li>`);
    actions.push(`<li><a class="dropdown-item" href="#" onclick="viewHistorique(${dossier.id})"><i class="fas fa-history me-2"></i>Historique</a></li>`);
    
    if (dossier.statut === 'ouvert') {
        actions.push(`<li><a class="dropdown-item text-danger" href="#" onclick="deleteDossier(${dossier.id})"><i class="fas fa-trash me-2"></i>Supprimer</a></li>`);
    }

    return actions.join('');
}

async function viewDossier(dossierId) {
    try {
        showLoadingToast('Chargement des détails...');
        
        const dossier = await api.getDossierWithStats(dossierId);
        const etapes = await api.getEtapesByDossier(dossierId);
        
        showDossierModal(dossier, etapes);
        hideLoadingToast();
        
    } catch (error) {
        console.error('Erreur chargement dossier:', error);
        showErrorToast('Erreur lors du chargement du dossier');
    }
}

function showDossierModal(dossier, etapes) {
    const modalHtml = `
        <div class="modal fade" id="dossierModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-folder-open me-2"></i>
                            ${dossier.dossier.titre}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>Informations Générales</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Numéro:</strong></td><td>${dossier.dossier.numero_dossier}</td></tr>
                                    <tr><td><strong>Client:</strong></td><td>${dossier.dossier.client?.nom || 'N/A'}</td></tr>
                                    <tr><td><strong>Avocat:</strong></td><td>${dossier.dossier.avocat?.nom || 'Non assigné'}</td></tr>
                                    <tr><td><strong>Statut:</strong></td><td><span class="badge status-${dossier.dossier.statut}">${formatStatus(dossier.dossier.statut)}</span></td></tr>
                                    <tr><td><strong>Priorité:</strong></td><td><span class="priority-${dossier.dossier.priorite}">${formatPriority(dossier.dossier.priorite)}</span></td></tr>
                                    <tr><td><strong>Ouvert le:</strong></td><td>${formatDate(dossier.dossier.date_ouverture)}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-pie me-2"></i>Statistiques</h6>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Progression:</span>
                                        <span><strong>${dossier.statistiques?.pourcentage_avancement || 0}%</strong></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: ${dossier.statistiques?.pourcentage_avancement || 0}%"></div>
                                    </div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-success">${dossier.statistiques?.etapes_validees || 0}</h4>
                                        <small>Validées</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning">${dossier.statistiques?.etapes_en_cours || 0}</h4>
                                        <small>En Cours</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-secondary">${dossier.statistiques?.etapes_en_attente || 0}</h4>
                                        <small>En Attente</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${dossier.dossier.description ? `
                        <div class="mt-3">
                            <h6><i class="fas fa-file-alt me-2"></i>Description</h6>
                            <p class="text-muted">${dossier.dossier.description}</p>
                        </div>
                        ` : ''}
                        
                        <div class="mt-3">
                            <h6><i class="fas fa-tasks me-2"></i>Étapes (${etapes.length})</h6>
                            <div class="list-group">
                                ${etapes.map(etape => `
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">${etape.nom}</h6>
                                            <small class="text-muted">Ordre: ${etape.ordre_execution}</small>
                                        </div>
                                        <span class="badge bg-${getEtapeStatusColor(etape.statut)}">${formatEtapeStatus(etape.statut)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" onclick="editDossier(${dossier.dossier.id})">Modifier</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existingModal = document.getElementById('dossierModal');
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('dossierModal'));
    modal.show();
}

async function assignAvocat(dossierId) {
    try {
        const avocatsDisponibles = await api.getAvailableAvocats();
        
        if (avocatsDisponibles.length === 0) {
            showErrorToast('Aucun avocat disponible');
            return;
        }

        showAssignAvocatModal(dossierId, avocatsDisponibles);
        
    } catch (error) {
        console.error('Erreur assignation avocat:', error);
        showErrorToast('Erreur lors de l\'assignation de l\'avocat');
    }
}

function showAssignAvocatModal(dossierId, avocats) {
    const modalHtml = `
        <div class="modal fade" id="assignAvocatModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus me-2"></i>Assigner un Avocat
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Sélectionner un avocat :</label>
                            <select class="form-select" id="selectAvocat">
                                <option value="">Choisir un avocat...</option>
                                ${avocats.map(avocat => `
                                    <option value="${avocat.avocat_id}">
                                        ${avocat.nom} - ${avocat.specialisation || 'Généraliste'}
                                        ${avocat.certifie ? ' (Certifié)' : ''}
                                        (${avocat.emplacements_disponibles} places libres)
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" onclick="confirmAssignAvocat(${dossierId})">Assigner</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const existingModal = document.getElementById('assignAvocatModal');
    if (existingModal) {
        existingModal.remove();
    }

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('assignAvocatModal'));
    modal.show();
}

async function confirmAssignAvocat(dossierId) {
    try {
        const avocatId = document.getElementById('selectAvocat').value;
        
        if (!avocatId) {
            showErrorToast('Veuillez sélectionner un avocat');
            return;
        }

        showLoadingToast('Assignment en cours...');
        
        await api.assignAvocat(dossierId, parseInt(avocatId));
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('assignAvocatModal'));
        modal.hide();
        
        await loadDossiers();
        renderDossiersTable(appData.dossiers);
        
        showSuccessToast('Avocat assigné avec succès !');
        
    } catch (error) {
        console.error('Erreur assignation:', error);
        showErrorToast('Erreur lors de l\'assignation');
    }
}

async function closeDossier(dossierId) {
    if (!confirm('Êtes-vous sûr de vouloir clôturer ce dossier ?')) {
        return;
    }

    try {
        showLoadingToast('Clôture en cours...');
        
        await api.closeDossier(dossierId);
        
        await loadDossiers();
        renderDossiersTable(appData.dossiers);
        
        showSuccessToast('Dossier clôturé avec succès !');
        
    } catch (error) {
        console.error('Erreur clôture:', error);
        showErrorToast(error.message || 'Erreur lors de la clôture');
    }
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function getEtapeStatusColor(status) {
    const colors = {
        'en_attente': 'secondary',
        'en_cours': 'warning',
        'validee': 'success',
        'annulee': 'danger'
    };
    return colors[status] || 'light';
}

function formatEtapeStatus(status) {
    const statusMap = {
        'en_attente': 'En Attente',
        'en_cours': 'En Cours',
        'validee': 'Validée',
        'annulee': 'Annulée'
    };
    return statusMap[status] || status;
}