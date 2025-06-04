<?php
namespace App\Repositories;

use App\Models\Historique;
use Core\Facades\RepositoryMutations;
use PDO;

class HistoriqueRepository extends RepositoryMutations
{
    public function __construct()
    {
        parent::__construct('historique');
    }

    public function findByDossierId(int $dossierId): array
    {
        $sql = "
            SELECT 
                h.*,
                d.titre as dossier_titre,
                d.numero_dossier,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.nom
                    WHEN h.effectue_par_type = 'avocat' THEN pa.nom
                    ELSE 'Système'
                END as utilisateur_nom,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.email
                    WHEN h.effectue_par_type = 'avocat' THEN pa.email
                    ELSE null
                END as utilisateur_email
            FROM historique h
            LEFT JOIN dossiers_juridiques d ON h.dossier_id = d.id
            LEFT JOIN clients c ON h.effectue_par_type = 'client' AND h.effectue_par_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON h.effectue_par_type = 'avocat' AND h.effectue_par_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE h.dossier_id = :dossier_id
            ORDER BY h.date_action DESC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['dossier_id' => $dossierId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findById(int $historiqueId): Historique
    {
        $sql = "
            SELECT 
                h.*,
                d.titre as dossier_titre,
                d.numero_dossier,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.nom
                    WHEN h.effectue_par_type = 'avocat' THEN pa.nom
                    ELSE 'Système'
                END as utilisateur_nom,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.email
                    WHEN h.effectue_par_type = 'avocat' THEN pa.email
                    ELSE null
                END as utilisateur_email
            FROM historique h
            LEFT JOIN dossiers_juridiques d ON h.dossier_id = d.id
            LEFT JOIN clients c ON h.effectue_par_type = 'client' AND h.effectue_par_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON h.effectue_par_type = 'avocat' AND h.effectue_par_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE h.id = :historique_id
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['historique_id' => $historiqueId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            throw new \Exception("Historique with ID $historiqueId not found.");
        }
        
        return $this->mapper($data);
    }

    public function createEntree(array $data): Historique
    {
        $historiqueData = [
            'dossier_id' => $data['dossier_id'],
            'type_action' => $data['type_action'],
            'description' => $data['description'],
            'effectue_par_type' => $data['effectue_par_type'],
            'effectue_par_id' => $data['effectue_par_id'] ?? null,
            'ancienne_valeur' => $data['ancienne_valeur'] ?? null,
            'nouvelle_valeur' => $data['nouvelle_valeur'] ?? null,
            'date_action' => $data['date_action'] ?? date('Y-m-d H:i:s')
        ];

        $historiqueId = $this->save($historiqueData);
        return $this->findById($historiqueId);
    }

    public function enregistrerCreation(int $dossierId, string $effectueParType, ?int $effectueParId, string $titreDossier): Historique
    {
        return $this->createEntree([
            'dossier_id' => $dossierId,
            'type_action' => 'creation',
            'description' => "Création du dossier '$titreDossier'",
            'effectue_par_type' => $effectueParType,
            'effectue_par_id' => $effectueParId
        ]);
    }

    public function enregistrerChangementAvocat(int $dossierId, string $effectueParType, ?int $effectueParId, ?string $ancienAvocat, string $nouveauAvocat): Historique
    {
        return $this->createEntree([
            'dossier_id' => $dossierId,
            'type_action' => 'changement_avocat',
            'description' => "Changement d'avocat assigné",
            'effectue_par_type' => $effectueParType,
            'effectue_par_id' => $effectueParId,
            'ancienne_valeur' => $ancienAvocat,
            'nouvelle_valeur' => $nouveauAvocat
        ]);
    }

    public function enregistrerAjoutDocument(int $dossierId, string $effectueParType, ?int $effectueParId, string $nomDocument): Historique
    {
        return $this->createEntree([
            'dossier_id' => $dossierId,
            'type_action' => 'ajout_document',
            'description' => "Ajout du document '$nomDocument'",
            'effectue_par_type' => $effectueParType,
            'effectue_par_id' => $effectueParId,
            'nouvelle_valeur' => $nomDocument
        ]);
    }

    public function enregistrerValidationEtape(int $dossierId, string $effectueParType, ?int $effectueParId, string $nomEtape): Historique
    {
        return $this->createEntree([
            'dossier_id' => $dossierId,
            'type_action' => 'validation_etape',
            'description' => "Validation de l'étape '$nomEtape'",
            'effectue_par_type' => $effectueParType,
            'effectue_par_id' => $effectueParId,
            'ancienne_valeur' => 'en_attente',
            'nouvelle_valeur' => 'validee'
        ]);
    }

    public function enregistrerChangementStatut(int $dossierId, string $effectueParType, ?int $effectueParId, string $ancienStatut, string $nouveauStatut): Historique
    {
        return $this->createEntree([
            'dossier_id' => $dossierId,
            'type_action' => 'changement_statut',
            'description' => "Changement du statut du dossier",
            'effectue_par_type' => $effectueParType,
            'effectue_par_id' => $effectueParId,
            'ancienne_valeur' => $ancienStatut,
            'nouvelle_valeur' => $nouveauStatut
        ]);
    }

    public function enregistrerCloture(int $dossierId, string $effectueParType, ?int $effectueParId): Historique
    {
        return $this->createEntree([
            'dossier_id' => $dossierId,
            'type_action' => 'cloture',
            'description' => "Clôture définitive du dossier",
            'effectue_par_type' => $effectueParType,
            'effectue_par_id' => $effectueParId
        ]);
    }

    public function findByTypeAction(string $typeAction): array
    {
        $sql = "
            SELECT 
                h.*,
                d.titre as dossier_titre,
                d.numero_dossier,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.nom
                    WHEN h.effectue_par_type = 'avocat' THEN pa.nom
                    ELSE 'Système'
                END as utilisateur_nom
            FROM historique h
            LEFT JOIN dossiers_juridiques d ON h.dossier_id = d.id
            LEFT JOIN clients c ON h.effectue_par_type = 'client' AND h.effectue_par_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON h.effectue_par_type = 'avocat' AND h.effectue_par_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE h.type_action = :type_action
            ORDER BY h.date_action DESC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['type_action' => $typeAction]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findByUtilisateur(string $effectueParType, int $effectueParId): array
    {
        $sql = "
            SELECT 
                h.*,
                d.titre as dossier_titre,
                d.numero_dossier,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.nom
                    WHEN h.effectue_par_type = 'avocat' THEN pa.nom
                    ELSE 'Système'
                END as utilisateur_nom
            FROM historique h
            LEFT JOIN dossiers_juridiques d ON h.dossier_id = d.id
            LEFT JOIN clients c ON h.effectue_par_type = 'client' AND h.effectue_par_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON h.effectue_par_type = 'avocat' AND h.effectue_par_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE h.effectue_par_type = :effectue_par_type 
            AND h.effectue_par_id = :effectue_par_id
            ORDER BY h.date_action DESC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute([
            'effectue_par_type' => $effectueParType,
            'effectue_par_id' => $effectueParId
        ]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findRecent(int $heures = 24): array
    {
        $sql = "
            SELECT 
                h.*,
                d.titre as dossier_titre,
                d.numero_dossier,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.nom
                    WHEN h.effectue_par_type = 'avocat' THEN pa.nom
                    ELSE 'Système'
                END as utilisateur_nom
            FROM historique h
            LEFT JOIN dossiers_juridiques d ON h.dossier_id = d.id
            LEFT JOIN clients c ON h.effectue_par_type = 'client' AND h.effectue_par_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON h.effectue_par_type = 'avocat' AND h.effectue_par_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE h.date_action >= DATE_SUB(NOW(), INTERVAL :heures HOUR)
            ORDER BY h.date_action DESC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['heures' => $heures]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function getStatistiquesActions(int $jours = 30): array
    {
        $sql = "
            SELECT 
                type_action, 
                COUNT(*) as count,
                DATE(date_action) as date_action
            FROM historique 
            WHERE date_action >= DATE_SUB(NOW(), INTERVAL :jours DAY)
            GROUP BY type_action, DATE(date_action)
            ORDER BY date_action DESC, type_action
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['jours' => $jours]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActionsByDossier(int $dossierId): int
    {
        $sql = "SELECT COUNT(*) FROM historique WHERE dossier_id = :dossier_id";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['dossier_id' => $dossierId]);
        return (int) $stmt->fetchColumn();
    }

    public function search(array $filters = []): array
    {
        $sql = "
            SELECT 
                h.*,
                d.titre as dossier_titre,
                d.numero_dossier,
                CASE 
                    WHEN h.effectue_par_type = 'client' THEN pc.nom
                    WHEN h.effectue_par_type = 'avocat' THEN pa.nom
                    ELSE 'Système'
                END as utilisateur_nom
            FROM historique h
            LEFT JOIN dossiers_juridiques d ON h.dossier_id = d.id
            LEFT JOIN clients c ON h.effectue_par_type = 'client' AND h.effectue_par_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON h.effectue_par_type = 'avocat' AND h.effectue_par_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE 1=1
        ";
        
        $params = [];

        if (!empty($filters['dossier_id'])) {
            $sql .= " AND h.dossier_id = :dossier_id";
            $params['dossier_id'] = $filters['dossier_id'];
        }

        if (!empty($filters['type_action'])) {
            $sql .= " AND h.type_action = :type_action";
            $params['type_action'] = $filters['type_action'];
        }

        if (!empty($filters['effectue_par_type'])) {
            $sql .= " AND h.effectue_par_type = :effectue_par_type";
            $params['effectue_par_type'] = $filters['effectue_par_type'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND h.date_action >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND h.date_action <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }

        if (!empty($filters['description'])) {
            $sql .= " AND h.description LIKE :description";
            $params['description'] = '%' . $filters['description'] . '%';
        }

        $sql .= " ORDER BY h.date_action DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int) $filters['limit'];
        }

        $stmt = $this->db->getPdo()->prepare($sql);
        
        foreach ($params as $key => $value) {
            if ($key === 'limit') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function purgerAncien(int $joursAConserver = 365): int
    {
        $sql = "
            DELETE FROM historique 
            WHERE date_action < DATE_SUB(NOW(), INTERVAL :jours DAY)
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['jours' => $joursAConserver]);
        
        return $stmt->rowCount();
    }

    protected function mapper(array $data): Historique
    {
        $historique = new Historique(
            $this->get($data, 'id'),
            $this->get($data, 'dossier_id'),
            $this->get($data, 'type_action'),
            $this->get($data, 'description'),
            $this->get($data, 'effectue_par_type'),
            $this->get($data, 'effectue_par_id'),
            $this->get($data, 'ancienne_valeur'),
            $this->get($data, 'nouvelle_valeur'),
            $this->get($data, 'date_action')
        );

        if ($this->get($data, 'dossier_titre')) {
            $historique->setDossier([
                'id' => $this->get($data, 'dossier_id'),
                'titre' => $this->get($data, 'dossier_titre'),
                'numero_dossier' => $this->get($data, 'numero_dossier')
            ]);
        }

        if ($this->get($data, 'utilisateur_nom')) {
            $historique->setUtilisateur([
                'id' => $this->get($data, 'effectue_par_id'),
                'nom' => $this->get($data, 'utilisateur_nom'),
                'email' => $this->get($data, 'utilisateur_email'),
                'type' => $this->get($data, 'effectue_par_type')
            ]);
        }

        return $historique;
    }
}