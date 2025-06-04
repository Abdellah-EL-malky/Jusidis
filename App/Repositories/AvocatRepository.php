<?php
namespace App\Repositories;

use App\Models\Avocat;
use PDO;

class AvocatRepository extends PersonneRepository
{
    public function findAllAvocats(): array
    {
        $sql = "
            SELECT 
                p.id as personne_id,
                p.nom,
                p.email,
                p.telephone,
                p.adresse,
                p.type_personne,
                p.created_at,
                p.updated_at,
                a.id as avocat_id,
                a.numero_barreau,
                a.specialisation,
                a.certifie,
                a.max_dossiers_actifs,
                a.dossiers_actifs_actuels,
                a.date_inscription_barreau
            FROM personnes p
            INNER JOIN avocats a ON p.id = a.personne_id
            WHERE p.type_personne = 'avocat'
            ORDER BY p.nom ASC
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findById(int $avocatId): Avocat
    {
        $sql = "
            SELECT 
                p.id as personne_id,
                p.nom,
                p.email,
                p.telephone,
                p.adresse,
                p.type_personne,
                p.created_at,
                p.updated_at,
                a.id as avocat_id,
                a.numero_barreau,
                a.specialisation,
                a.certifie,
                a.max_dossiers_actifs,
                a.dossiers_actifs_actuels,
                a.date_inscription_barreau
            FROM personnes p
            INNER JOIN avocats a ON p.id = a.personne_id
            WHERE a.id = :avocat_id
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['avocat_id' => $avocatId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            throw new \Exception("Avocat avec l'ID $avocatId introuvable.");
        }
        
        return $this->mapper($data);
    }

    public function findByNumeroBarreau(string $numeroBarreau): ?Avocat
    {
        $sql = "
            SELECT 
                p.id as personne_id,
                p.nom,
                p.email,
                p.telephone,
                p.adresse,
                p.type_personne,
                p.created_at,
                p.updated_at,
                a.id as avocat_id,
                a.numero_barreau,
                a.specialisation,
                a.certifie,
                a.max_dossiers_actifs,
                a.dossiers_actifs_actuels,
                a.date_inscription_barreau
            FROM personnes p
            INNER JOIN avocats a ON p.id = a.personne_id
            WHERE a.numero_barreau = :numero_barreau
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['numero_barreau' => $numeroBarreau]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->mapper($data) : null;
    }

    public function findAvailableAvocats(): array
    {
        $sql = "
            SELECT 
                p.id as personne_id,
                p.nom,
                p.email,
                p.telephone,
                p.adresse,
                p.type_personne,
                p.created_at,
                p.updated_at,
                a.id as avocat_id,
                a.numero_barreau,
                a.specialisation,
                a.certifie,
                a.max_dossiers_actifs,
                a.dossiers_actifs_actuels,
                a.date_inscription_barreau
            FROM personnes p
            INNER JOIN avocats a ON p.id = a.personne_id
            WHERE p.type_personne = 'avocat' 
            AND a.dossiers_actifs_actuels < a.max_dossiers_actifs
            ORDER BY (a.max_dossiers_actifs - a.dossiers_actifs_actuels) DESC, p.nom ASC
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findCertifiedAvocats(): array
    {
        $sql = "
            SELECT 
                p.id as personne_id,
                p.nom,
                p.email,
                p.telephone,
                p.adresse,
                p.type_personne,
                p.created_at,
                p.updated_at,
                a.id as avocat_id,
                a.numero_barreau,
                a.specialisation,
                a.certifie,
                a.max_dossiers_actifs,
                a.dossiers_actifs_actuels,
                a.date_inscription_barreau
            FROM personnes p
            INNER JOIN avocats a ON p.id = a.personne_id
            WHERE p.type_personne = 'avocat' AND a.certifie = 1
            ORDER BY p.nom ASC
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findBySpecialisation(string $specialisation): array
    {
        $sql = "
            SELECT 
                p.id as personne_id,
                p.nom,
                p.email,
                p.telephone,
                p.adresse,
                p.type_personne,
                p.created_at,
                p.updated_at,
                a.id as avocat_id,
                a.numero_barreau,
                a.specialisation,
                a.certifie,
                a.max_dossiers_actifs,
                a.dossiers_actifs_actuels,
                a.date_inscription_barreau
            FROM personnes p
            INNER JOIN avocats a ON p.id = a.personne_id
            WHERE p.type_personne = 'avocat' AND a.specialisation = :specialisation
            ORDER BY p.nom ASC
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['specialisation' => $specialisation]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function createAvocat(array $data): Avocat
    {
        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            $personneData = [
                'nom' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'type_personne' => 'avocat'
            ];

            $personneId = $this->createPersonne($personneData);

            $avocatData = [
                'personne_id' => $personneId,
                'numero_barreau' => $data['numero_barreau'],
                'specialisation' => $data['specialisation'] ?? null,
                'certifie' => isset($data['certifie']) ? (bool) $data['certifie'] : false,
                'max_dossiers_actifs' => $data['max_dossiers_actifs'] ?? 10,
                'dossiers_actifs_actuels' => 0,
                'date_inscription_barreau' => $data['date_inscription_barreau'] ?? null
            ];

            $stmt = $pdo->prepare("
                INSERT INTO avocats (personne_id, numero_barreau, specialisation, certifie, max_dossiers_actifs, dossiers_actifs_actuels, date_inscription_barreau) 
                VALUES (:personne_id, :numero_barreau, :specialisation, :certifie, :max_dossiers_actifs, :dossiers_actifs_actuels, :date_inscription_barreau)
            ");
            $stmt->execute($avocatData);
            
            $avocatId = $pdo->lastInsertId();

            $pdo->commit();

            return $this->findById($avocatId);

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \Exception("Erreur lors de la création de l'avocat: " . $e->getMessage());
        }
    }

    public function updateAvocat(int $avocatId, array $data): Avocat
    {
        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            $avocat = $this->findById($avocatId);

            $personneFields = ['nom', 'email', 'telephone', 'adresse'];
            $personneData = array_intersect_key($data, array_flip($personneFields));
            
            if (!empty($personneData)) {
                $this->updatePersonne($avocat->getPersonneId(), $personneData);
            }

            $avocatFields = ['numero_barreau', 'specialisation', 'certifie', 'max_dossiers_actifs', 'date_inscription_barreau'];
            $avocatData = array_intersect_key($data, array_flip($avocatFields));
            
            if (!empty($avocatData)) {
                $this->updateAvocatSpecific($avocatId, $avocatData);
            }

            $pdo->commit();

            return $this->findById($avocatId);

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \Exception("Erreur lors de la mise à jour de l'avocat: " . $e->getMessage());
        }
    }

    private function updateAvocatSpecific(int $avocatId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setParts = array_map(fn($key) => "$key = :$key", array_keys($data));
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE avocats SET $setClause WHERE id = :avocat_id";
        $data['avocat_id'] = $avocatId;

        $stmt = $this->db->getPdo()->prepare($sql);
        return $stmt->execute($data);
    }

    public function incrementDossiersActifs(int $avocatId): bool
    {
        $sql = "UPDATE avocats SET dossiers_actifs_actuels = dossiers_actifs_actuels + 1 WHERE id = :avocat_id";
        $stmt = $this->db->getPdo()->prepare($sql);
        return $stmt->execute(['avocat_id' => $avocatId]);
    }

    public function decrementDossiersActifs(int $avocatId): bool
    {
        $sql = "UPDATE avocats SET dossiers_actifs_actuels = GREATEST(0, dossiers_actifs_actuels - 1) WHERE id = :avocat_id";
        $stmt = $this->db->getPdo()->prepare($sql);
        return $stmt->execute(['avocat_id' => $avocatId]);
    }

    public function numeroBarreauExists(string $numeroBarreau, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM avocats WHERE numero_barreau = :numero_barreau";
        $params = ['numero_barreau' => $numeroBarreau];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }

    protected function mapper(array $data): Avocat
    {
        return new Avocat(
            $this->get($data, 'personne_id'),
            $this->get($data, 'nom'),
            $this->get($data, 'email'),
            $this->get($data, 'telephone'),
            $this->get($data, 'adresse'),
            $this->get($data, 'created_at'),
            $this->get($data, 'updated_at'),
            $this->get($data, 'avocat_id'),
            $this->get($data, 'personne_id'),
            $this->get($data, 'numero_barreau'),
            $this->get($data, 'specialisation'),
            (bool) $this->get($data, 'certifie'),
            (int) $this->get($data, 'max_dossiers_actifs'),
            (int) $this->get($data, 'dossiers_actifs_actuels'),
            $this->get($data, 'date_inscription_barreau')
        );
    }
}