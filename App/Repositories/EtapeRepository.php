<?php
namespace App\Repositories;

use App\Models\Etape;
use Core\Facades\RepositoryMutations;
use PDO;

class EtapeRepository extends RepositoryMutations
{
    public function __construct()
    {
        parent::__construct('etapes');
    }

    public function findByDossierId(int $dossierId): array
    {
        $sql = "
            SELECT 
                e.*,
                pa.nom as validateur_nom
            FROM etapes e
            LEFT JOIN avocats a ON e.validee_par_avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE e.dossier_id = :dossier_id
            ORDER BY e.ordre_execution ASC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['dossier_id' => $dossierId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findById(int $etapeId): Etape
    {
        $sql = "
            SELECT 
                e.*,
                pa.nom as validateur_nom
            FROM etapes e
            LEFT JOIN avocats a ON e.validee_par_avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            WHERE e.id = :etape_id
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['etape_id' => $etapeId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            throw new \Exception("Etape avec l'ID $etapeId introuvable.");
        }
        
        return $this->mapper($data);
    }

    public function createEtape(array $data): Etape
    {
        $etapeData = [
            'dossier_id' => $data['dossier_id'],
            'nom' => $data['nom'],
            'description' => $data['description'] ?? null,
            'ordre_execution' => $data['ordre_execution'] ?? 1,
            'statut' => $data['statut'] ?? 'en_attente',
            'date_debut' => $data['date_debut'] ?? null,
            'date_fin_prevue' => $data['date_fin_prevue'] ?? null,
            'obligatoire' => isset($data['obligatoire']) ? (bool) $data['obligatoire'] : true
        ];

        $etapeId = $this->save($etapeData);
        return $this->findById($etapeId);
    }

    public function createEtapesParDefaut(int $dossierId, int $typeDossierId): array
    {
        $sql = "
            SELECT nom, description, ordre_execution, obligatoire
            FROM etapes_types
            WHERE type_dossier_id = :type_dossier_id
            ORDER BY ordre_execution ASC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['type_dossier_id' => $typeDossierId]);
        $etapesTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $etapesCrees = [];
        foreach ($etapesTypes as $etapeType) {
            $etapeData = [
                'dossier_id' => $dossierId,
                'nom' => $etapeType['nom'],
                'description' => $etapeType['description'],
                'ordre_execution' => $etapeType['ordre_execution'],
                'obligatoire' => (bool) $etapeType['obligatoire']
            ];

            $etape = $this->createEtape($etapeData);
            $etapesCrees[] = $etape;
        }

        return $etapesCrees;
    }

    public function validerEtape(int $etapeId, int $avocatId): bool
    {
        $updateData = [
            'statut' => 'validee',
            'date_validation' => date('Y-m-d H:i:s'),
            'validee_par_avocat_id' => $avocatId
        ];

        return $this->update($updateData, ['id' => $etapeId]);
    }

    public function demarrerEtape(int $etapeId): bool
    {
        $updateData = [
            'statut' => 'en_cours',
            'date_debut' => date('Y-m-d')
        ];

        return $this->update($updateData, ['id' => $etapeId]);
    }

    public function annulerEtape(int $etapeId): bool
    {
        return $this->update(['statut' => 'annulee'], ['id' => $etapeId]);
    }

    public function verifierToutesEtapesValidees(int $dossierId): bool
    {
        $sql = "
            SELECT COUNT(*) 
            FROM etapes 
            WHERE dossier_id = :dossier_id 
            AND obligatoire = 1 
            AND statut != 'validee'
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['dossier_id' => $dossierId]);
        $etapesNonValidees = (int) $stmt->fetchColumn();

        return $etapesNonValidees === 0;
    }

    public function findEtapesEnRetard(): array
    {
        $sql = "
            SELECT 
                e.*,
                pa.nom as validateur_nom,
                d.titre as dossier_titre,
                d.numero_dossier
            FROM etapes e
            LEFT JOIN avocats a ON e.validee_par_avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            LEFT JOIN dossiers_juridiques d ON e.dossier_id = d.id
            WHERE e.date_fin_prevue < CURDATE()
            AND e.statut != 'validee'
            AND e.statut != 'annulee'
            ORDER BY e.date_fin_prevue ASC
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findByStatut(string $statut): array
    {
        $sql = "
            SELECT 
                e.*,
                pa.nom as validateur_nom,
                d.titre as dossier_titre,
                d.numero_dossier
            FROM etapes e
            LEFT JOIN avocats a ON e.validee_par_avocat_id = a.id
            LEFT JOIN personnes pa ON a.personne_id = pa.id
            LEFT JOIN dossiers_juridiques d ON e.dossier_id = d.id
            WHERE e.statut = :statut
            ORDER BY e.ordre_execution ASC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['statut' => $statut]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function countByStatutForDossier(int $dossierId): array
    {
        $sql = "
            SELECT statut, COUNT(*) as count 
            FROM etapes 
            WHERE dossier_id = :dossier_id
            GROUP BY statut
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['dossier_id' => $dossierId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['statut']] = (int) $row['count'];
        }
        
        return $counts;
    }

    public function calculerPourcentageAvancement(int $dossierId): float
    {
        $sql = "
            SELECT 
                COUNT(*) as total_obligatoires,
                SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) as validees
            FROM etapes 
            WHERE dossier_id = :dossier_id AND obligatoire = 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['dossier_id' => $dossierId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int) $result['total_obligatoires'];
        $validees = (int) $result['validees'];

        if ($total === 0) {
            return 100.0;
        }

        return ($validees / $total) * 100;
    }

    public function reorderEtapes(int $dossierId, array $etapeIds): bool
    {
        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            foreach ($etapeIds as $ordre => $etapeId) {
                $sql = "UPDATE etapes SET ordre_execution = :ordre WHERE id = :etape_id AND dossier_id = :dossier_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'ordre' => $ordre + 1,
                    'etape_id' => $etapeId,
                    'dossier_id' => $dossierId
                ]);
            }

            $pdo->commit();
            return true;

        } catch (\Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function dupliquerEtapes(int $dossierSourceId, int $dossierDestinationId): array
    {
        $etapesSource = $this->findByDossierId($dossierSourceId);
        $etapesDupliquees = [];

        foreach ($etapesSource as $etapeSource) {
            $nouvelleEtapeData = [
                'dossier_id' => $dossierDestinationId,
                'nom' => $etapeSource->getNom(),
                'description' => $etapeSource->getDescription(),
                'ordre_execution' => $etapeSource->getOrdreExecution(),
                'obligatoire' => $etapeSource->getObligatoire()
            ];

            $nouvelleEtape = $this->createEtape($nouvelleEtapeData);
            $etapesDupliquees[] = $nouvelleEtape;
        }

        return $etapesDupliquees;
    }

    protected function mapper(array $data): Etape
    {
        $etape = new Etape(
            $this->get($data, 'id'),
            $this->get($data, 'dossier_id'),
            $this->get($data, 'nom'),
            $this->get($data, 'description'),
            (int) $this->get($data, 'ordre_execution'),
            $this->get($data, 'statut'),
            $this->get($data, 'date_debut'),
            $this->get($data, 'date_fin_prevue'),
            $this->get($data, 'date_validation'),
            $this->get($data, 'validee_par_avocat_id'),
            (bool) $this->get($data, 'obligatoire'),
            $this->get($data, 'created_at')
        );

        if ($this->get($data, 'validateur_nom')) {
            $etape->setValidateur([
                'id' => $this->get($data, 'validee_par_avocat_id'),
                'nom' => $this->get($data, 'validateur_nom')
            ]);
        }

        return $etape;
    }
}