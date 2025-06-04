<?php
namespace App\Repositories;

use App\Models\DossierJuridique;
use Core\Facades\RepositoryMutations;
use PDO;

class DossierJuridiqueRepository extends RepositoryMutations
{
    public function __construct()
    {
        parent::__construct('dossiers_juridiques');
    }

    public function findAllDossiers(): array
    {
        $sql = "
            SELECT 
                d.*,
                td.nom as type_nom,
                td.description as type_description,
                td.exige_avocat_certifie,
                pc.nom as client_nom,
                pc.email as client_email,
                pa.nom as avocat_nom,
                pa.email as avocat_email,
                a.certifie as avocat_certifie
            FROM dossiers_juridiques d
            LEFT JOIN types_dossiers td ON d.type_dossier_id = td.id
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON d.avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            ORDER BY d.created_at DESC
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findById(int $dossierId): DossierJuridique
    {
        $sql = "
            SELECT 
                d.*,
                td.nom as type_nom,
                td.description as type_description,
                td.exige_avocat_certifie,
                pc.nom as client_nom,
                pc.email as client_email,
                pa.nom as avocat_nom,
                pa.email as avocat_email,
                a.certifie as avocat_certifie
            FROM dossiers_juridiques d
            LEFT JOIN types_dossiers td ON d.type_dossier_id = td.id
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON d.avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE d.id = :dossier_id
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['dossier_id' => $dossierId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            throw new \Exception("Dossier avec l'ID $dossierId introuvable.");
        }
        
        return $this->mapper($data);
    }

    public function findByClientId(int $clientId): array
    {
        $sql = "
            SELECT 
                d.*,
                td.nom as type_nom,
                td.description as type_description,
                td.exige_avocat_certifie,
                pc.nom as client_nom,
                pc.email as client_email,
                pa.nom as avocat_nom,
                pa.email as avocat_email,
                a.certifie as avocat_certifie
            FROM dossiers_juridiques d
            LEFT JOIN types_dossiers td ON d.type_dossier_id = td.id
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON d.avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE d.client_id = :client_id
            ORDER BY d.created_at DESC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findByAvocatId(int $avocatId): array
    {
        $sql = "
            SELECT 
                d.*,
                td.nom as type_nom,
                td.description as type_description,
                td.exige_avocat_certifie,
                pc.nom as client_nom,
                pc.email as client_email,
                pa.nom as avocat_nom,
                pa.email as avocat_email,
                a.certifie as avocat_certifie
            FROM dossiers_juridiques d
            LEFT JOIN types_dossiers td ON d.type_dossier_id = td.id
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON d.avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE d.avocat_id = :avocat_id
            ORDER BY d.created_at DESC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['avocat_id' => $avocatId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findByStatut(string $statut): array
    {
        $sql = "
            SELECT 
                d.*,
                td.nom as type_nom,
                td.description as type_description,
                td.exige_avocat_certifie,
                pc.nom as client_nom,
                pc.email as client_email,
                pa.nom as avocat_nom,
                pa.email as avocat_email,
                a.certifie as avocat_certifie
            FROM dossiers_juridiques d
            LEFT JOIN types_dossiers td ON d.type_dossier_id = td.id
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON d.avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE d.statut = :statut
            ORDER BY d.created_at DESC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['statut' => $statut]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findByNumeroDossier(string $numeroDossier): ?DossierJuridique
    {
        $sql = "
            SELECT 
                d.*,
                td.nom as type_nom,
                td.description as type_description,
                td.exige_avocat_certifie,
                pc.nom as client_nom,
                pc.email as client_email,
                pa.nom as avocat_nom,
                pa.email as avocat_email,
                a.certifie as avocat_certifie
            FROM dossiers_juridiques d
            LEFT JOIN types_dossiers td ON d.type_dossier_id = td.id
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON d.avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE d.numero_dossier = :numero_dossier
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['numero_dossier' => $numeroDossier]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->mapper($data) : null;
    }

    public function createDossier(array $data): DossierJuridique
    {
        $numeroDossier = $data['numero_dossier'] ?? $this->generateNumeroDossier();

        $dossierData = [
            'numero_dossier' => $numeroDossier,
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'type_dossier_id' => $data['type_dossier_id'],
            'client_id' => $data['client_id'],
            'avocat_id' => $data['avocat_id'] ?? null,
            'statut' => $data['statut'] ?? 'ouvert',
            'date_ouverture' => $data['date_ouverture'] ?? date('Y-m-d'),
            'priorite' => $data['priorite'] ?? 'normale'
        ];

        $dossierId = $this->save($dossierData);
        return $this->findById($dossierId);
    }

    public function assignerAvocat(int $dossierId, int $avocatId): bool
    {
        return $this->update(['avocat_id' => $avocatId], ['id' => $dossierId]);
    }

    public function changerStatut(int $dossierId, string $nouveauStatut): bool
    {
        $updateData = ['statut' => $nouveauStatut];
        
        if ($nouveauStatut === 'cloture') {
            $updateData['date_cloture'] = date('Y-m-d');
        }

        return $this->update($updateData, ['id' => $dossierId]);
    }

    public function marquerToutesEtapesValidees(int $dossierId, bool $validees = true): bool
    {
        return $this->update(['toutes_etapes_validees' => $validees], ['id' => $dossierId]);
    }

    public function search(array $filters = []): array
    {
        $sql = "
            SELECT 
                d.*,
                td.nom as type_nom,
                td.description as type_description,
                td.exige_avocat_certifie,
                pc.nom as client_nom,
                pc.email as client_email,
                pa.nom as avocat_nom,
                pa.email as avocat_email,
                a.certifie as avocat_certifie
            FROM dossiers_juridiques d
            LEFT JOIN types_dossiers td ON d.type_dossier_id = td.id
            LEFT JOIN clients c ON d.client_id = c.id
            LEFT JOIN personnes pc ON c.personne_id = pc.id
            LEFT JOIN avocats a ON d.avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE 1=1
        ";
        
        $params = [];

        if (!empty($filters['titre'])) {
            $sql .= " AND d.titre LIKE :titre";
            $params['titre'] = '%' . $filters['titre'] . '%';
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND d.statut = :statut";
            $params['statut'] = $filters['statut'];
        }

        if (!empty($filters['client_id'])) {
            $sql .= " AND d.client_id = :client_id";
            $params['client_id'] = $filters['client_id'];
        }

        if (!empty($filters['avocat_id'])) {
            $sql .= " AND d.avocat_id = :avocat_id";
            $params['avocat_id'] = $filters['avocat_id'];
        }

        if (!empty($filters['type_dossier_id'])) {
            $sql .= " AND d.type_dossier_id = :type_dossier_id";
            $params['type_dossier_id'] = $filters['type_dossier_id'];
        }

        if (!empty($filters['priorite'])) {
            $sql .= " AND d.priorite = :priorite";
            $params['priorite'] = $filters['priorite'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND d.date_ouverture >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND d.date_ouverture <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }

        $sql .= " ORDER BY d.created_at DESC";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function countByStatut(): array
    {
        $sql = "
            SELECT statut, COUNT(*) as count 
            FROM dossiers_juridiques 
            GROUP BY statut
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['statut']] = (int) $row['count'];
        }
        
        return $counts;
    }

    private function generateNumeroDossier(): string
    {
        do {
            $year = date('Y');
            $month = date('m');
            $numero = "DOS{$year}{$month}" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->findByNumeroDossier($numero);
        } while ($exists);

        return $numero;
    }

    protected function mapper(array $data): DossierJuridique
    {
        $dossier = new DossierJuridique(
            $this->get($data, 'id'),
            $this->get($data, 'numero_dossier'),
            $this->get($data, 'titre'),
            $this->get($data, 'description'),
            $this->get($data, 'type_dossier_id'),
            $this->get($data, 'client_id'),
            $this->get($data, 'avocat_id'),
            $this->get($data, 'statut'),
            $this->get($data, 'date_ouverture'),
            $this->get($data, 'date_cloture'),
            $this->get($data, 'priorite'),
            (bool) $this->get($data, 'toutes_etapes_validees'),
            $this->get($data, 'created_at'),
            $this->get($data, 'updated_at')
        );

        if ($this->get($data, 'type_nom')) {
            $dossier->setTypeDossier([
                'id' => $this->get($data, 'type_dossier_id'),
                'nom' => $this->get($data, 'type_nom'),
                'description' => $this->get($data, 'type_description'),
                'exige_avocat_certifie' => (bool) $this->get($data, 'exige_avocat_certifie')
            ]);
        }

        if ($this->get($data, 'client_nom')) {
            $dossier->setClient([
                'id' => $this->get($data, 'client_id'),
                'nom' => $this->get($data, 'client_nom'),
                'email' => $this->get($data, 'client_email')
            ]);
        }

        if ($this->get($data, 'avocat_nom')) {
            $dossier->setAvocat([
                'id' => $this->get($data, 'avocat_id'),
                'nom' => $this->get($data, 'avocat_nom'),
                'email' => $this->get($data, 'avocat_email'),
                'certifie' => (bool) $this->get($data, 'avocat_certifie')
            ]);
        }

        return $dossier;
    }
}