<?php
namespace App\Repositories;

use Core\Facades\RepositoryMutations;
use PDO;

abstract class PersonneRepository extends RepositoryMutations
{
    public function __construct()
    {
        parent::__construct('personnes');
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM $this->tableName WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByType(string $type): array
    {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM $this->tableName WHERE type_personne = :type");
        $stmt->execute(['type' => $type]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    protected function createPersonne(array $personneData): int
    {
        $fields = ['nom', 'email', 'telephone', 'adresse', 'type_personne'];
        $data = [];
        
        foreach ($fields as $field) {
            if (isset($personneData[$field])) {
                $data[$field] = $personneData[$field];
            }
        }

        return $this->save($data);
    }

    protected function updatePersonne(int $personneId, array $personneData): bool
    {
        $allowedFields = ['nom', 'email', 'telephone', 'adresse'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($personneData[$field])) {
                $updateData[$field] = $personneData[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->update($updateData, ['id' => $personneId]);
    }

    public function deletePersonne(int $personneId): bool
    {
        return $this->delete(['id' => $personneId]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM $this->tableName WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }

    public function search(array $filters = []): array
    {
        $sql = "SELECT * FROM $this->tableName WHERE 1=1";
        $params = [];

        if (!empty($filters['nom'])) {
            $sql .= " AND nom LIKE :nom";
            $params['nom'] = '%' . $filters['nom'] . '%';
        }

        if (!empty($filters['email'])) {
            $sql .= " AND email LIKE :email";
            $params['email'] = '%' . $filters['email'] . '%';
        }

        if (!empty($filters['type_personne'])) {
            $sql .= " AND type_personne = :type_personne";
            $params['type_personne'] = $filters['type_personne'];
        }

        $sql .= " ORDER BY nom ASC";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->arrayMapper($data);
    }
}