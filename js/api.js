class CabinetAPI {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        try {
            console.log(`API Request: ${finalOptions.method || 'GET'} ${url}`);
            
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Erreur de communication' }));
                throw new Error(errorData.error || `HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log(`API Response:`, data);
            return data;
            
        } catch (error) {
            console.error(`API Error for ${url}:`, error);
            throw error;
        }
    }

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async patch(endpoint, data) {
        return this.request(endpoint, {
            method: 'PATCH',
            body: JSON.stringify(data)
        });
    }

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }

    async getClients() {
        return this.get('clients');
    }

    async getClient(id) {
        return this.get(`clients/${id}`);
    }

    async getClientWithStats(id) {
        return this.get(`clients/${id}?with_stats=true`);
    }

    async createClient(clientData) {
        return this.post('clients', clientData);
    }

    async updateClient(id, clientData) {
        return this.patch(`clients/${id}`, clientData);
    }

    async deleteClient(id) {
        return this.delete(`clients/${id}`);
    }

    async searchClients(filters) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`clients?${params}`);
    }

    async getAvocats() {
        return this.get('avocats');
    }

    async getAvocat(id) {
        return this.get(`avocats/${id}`);
    }

    async getAvocatWithStats(id) {
        return this.get(`avocats/${id}?with_stats=true`);
    }

    async getAvailableAvocats() {
        return this.get('avocats?disponibles_seulement=true');
    }

    async getCertifiedAvocats() {
        return this.get('avocats?certifies_seulement=true');
    }

    async createAvocat(avocatData) {
        return this.post('avocats', avocatData);
    }

    async updateAvocat(id, avocatData) {
        return this.patch(`avocats/${id}`, avocatData);
    }

    async deleteAvocat(id) {
        return this.delete(`avocats/${id}`);
    }

    async certifyAvocat(id) {
        return this.patch(`avocats/certifier/${id}`, {});
    }

    async uncertifyAvocat(id) {
        return this.patch(`avocats/decertifier/${id}`, {});
    }

    async updateAvocatCapacity(id, newCapacity) {
        return this.patch(`avocats/capacite/${id}`, { nouvelle_capacite: newCapacity });
    }

    async findBestAvocatForDossierType(typeDossierId, specialisation = null) {
        const params = specialisation ? `?specialisation=${specialisation}` : '';
        return this.get(`/avocats/meilleur-pour-dossier/${typeDossierId}${params}`);
    }

    async getDossiers() {
        return this.get('dossier-juridiques');
    }

    async getDossier(id) {
        return this.get(`dossier-juridiques/${id}`);
    }

    async getDossierWithStats(id) {
        return this.get(`dossier-juridiques/${id}?with_stats=true`);
    }

    async createDossier(dossierData) {
        return this.post('dossier-juridiques', dossierData);
    }

    async updateDossier(id, dossierData) {
        return this.patch(`dossier-juridiques/${id}`, dossierData);
    }

    async deleteDossier(id) {
        return this.delete(`dossier-juridiques/${id}`);
    }

    async assignAvocat(dossierId, avocatId) {
        return this.patch(`dossier-juridiques/assigner-avocat/${dossierId}`, { avocat_id: avocatId });
    }

    async unassignAvocat(dossierId) {
        return this.patch(`dossier-juridiques/desassigner-avocat/${dossierId}`, {});
    }

    async changeDossierStatus(dossierId, newStatus) {
        return this.patch(`dossier-juridiques/changer-statut/${dossierId}`, { nouveau_statut: newStatus });
    }

    async closeDossier(dossierId) {
        return this.patch(`dossier-juridiques/cloturer/${dossierId}`, {});
    }

    async reopenDossier(dossierId) {
        return this.patch(`dossier-juridiques/reouvrir/${dossierId}`, {});
    }

    async archiveDossier(dossierId) {
        return this.patch(`dossier-juridiques/archiver/${dossierId}`, {});
    }

    async getDossiersByClient(clientId) {
        return this.get(`dossier-juridiques/client/${clientId}`);
    }

    async getDossiersByAvocat(avocatId) {
        return this.get(`dossier-juridiques/avocat/${avocatId}`);
    }

    async findBestAvocatForDossier(dossierId) {
        return this.get(`dossier-juridiques/meilleur-avocat/${dossierId}`);
    }

    async canCloseDossier(dossierId) {
        return this.get(`dossier-juridiques/peut-cloturer/${dossierId}`);
    }

    async getGeneralStatistics() {
        return this.get('dossier-juridiques/statistiques');
    }

    async getEtapes(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/etapes?${params}`);
    }

    async getEtape(id) {
        return this.get(`/etapes/${id}`);
    }

    async getEtapesByDossier(dossierId) {
        return this.get(`/etapes?dossier_id=${dossierId}`);
    }

    async getEtapesByStatus(status) {
        return this.get(`/etapes?statut=${status}`);
    }

    async getEtapesEnRetard() {
        return this.get(`/etapes?en_retard=true`);
    }

    async createEtape(etapeData) {
        return this.post('/etapes', etapeData);
    }

    async updateEtape(id, etapeData) {
        return this.patch(`/etapes/${id}`, etapeData);
    }

    async deleteEtape(id) {
        return this.delete(`/etapes/${id}`);
    }

    async validateEtape(etapeId, avocatId) {
        return this.patch(`/etapes/valider/${etapeId}`, { avocat_id: avocatId });
    }

    async startEtape(etapeId) {
        return this.patch(`/etapes/demarrer/${etapeId}`, {});
    }

    async cancelEtape(etapeId) {
        return this.patch(`/etapes/annuler/${etapeId}`, {});
    }

    async getDossierEtapesStats(dossierId) {
        return this.get(`/etapes/dossier/${dossierId}/statistiques`);
    }

    async reorderEtapes(dossierId, etapeIds) {
        return this.patch(`/etapes/dossier/${dossierId}/reorganiser`, { etape_ids: etapeIds });
    }

    async getHistorique(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        return this.get(`/historique?${params}`);
    }

    async getHistoriqueEntry(id) {
        return this.get(`/historique/${id}`);
    }

    async getHistoriqueByDossier(dossierId) {
        return this.get(`/historique/dossier/${dossierId}`);
    }

    async getHistoriqueByType(typeAction) {
        return this.get(`/historique/type/${typeAction}`);
    }

    async getHistoriqueByUser(userType, userId) {
        return this.get(`/historique/utilisateur/${userType}/${userId}`);
    }

    async getRecentHistorique(hours = 24) {
        return this.get(`/historique/recent?heures=${hours}`);
    }

    async getHistoriqueStats(days = 30) {
        return this.get(`/historique/statistiques?jours=${days}`);
    }

    async searchHistorique(criteria) {
        return this.post('/historique/recherche', criteria);
    }

    async exportHistorique(filters = {}) {
        const params = new URLSearchParams(filters).toString();
        window.open(`${this.baseUrl}/historique/export?${params}`, '_blank');
    }

    async getTypesDossiers() {
        return [
            { id: 1, nom: 'Divorce', exige_avocat_certifie: false },
            { id: 2, nom: 'Succession', exige_avocat_certifie: true },
            { id: 3, nom: 'Droit pénal', exige_avocat_certifie: true },
            { id: 4, nom: 'Droit commercial', exige_avocat_certifie: false },
            { id: 5, nom: 'Droit immobilier', exige_avocat_certifie: false },
            { id: 6, nom: 'Droit du travail', exige_avocat_certifie: false }
        ];
    }
}

const api = new CabinetAPI(API_BASE_URL);

async function loadClients() {
    try {
        appData.clients = await api.getClients();
        console.log('Clients chargés:', appData.clients.length);
    } catch (error) {
        console.error('Erreur chargement clients:', error);
        throw error;
    }
}

async function loadAvocats() {
    try {
        appData.avocats = await api.getAvocats();
        console.log('Avocats chargés:', appData.avocats.length);
    } catch (error) {
        console.error('Erreur chargement avocats:', error);
        throw error;
    }
}

async function loadDossiers() {
    try {
        appData.dossiers = await api.getDossiers();
        console.log('Dossiers chargés:', appData.dossiers.length);
    } catch (error) {
        console.error('Erreur chargement dossiers:', error);
        throw error;
    }
}

async function loadStatistics() {
    try {
        appData.stats = await api.getGeneralStatistics();
        console.log('Statistiques chargées:', appData.stats);
    } catch (error) {
        console.error('Erreur chargement statistiques:', error);
        throw error;
    }
}

async function loadEtapes() {
    try {
        appData.etapes = await api.getEtapes();
        console.log('Étapes chargées:', appData.etapes.length);
    } catch (error) {
        console.error('Erreur chargement étapes:', error);
        throw error;
    }
}

async function loadHistorique() {
    try {
        appData.historique = await api.getHistorique({ limit: 50 });
        console.log('Historique chargé:', appData.historique.length);
    } catch (error) {
        console.error('Erreur chargement historique:', error);
        throw error;
    }
}