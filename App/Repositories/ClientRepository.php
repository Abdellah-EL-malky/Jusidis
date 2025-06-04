<?php
namespace App\Repositories;

use App\Models\Client;
use PDO;

class ClientRepository extends PersonneRepository
{
    public function findAllClients(): array
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
                c.id as client_id,
                c.numero_client,
                c.date_inscription,
                c.statut
            FROM personnes p
            INNER JOIN clients c ON p.id = c.personne_id
            WHERE p.type_personne = 'client'
            ORDER BY p.nom ASC
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function findById(int $clientId): Client
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
                c.id as client_id,
                c.numero_client,
                c.date_inscription,
                c.statut
            FROM personnes p
            INNER JOIN clients c ON p.id = c.personne_id
            WHERE c.id = :client_id
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            throw new \Exception("Client avec l'ID $clientId introuvable.");
        }
        
        return $this->mapper($data);
    }

    public function findByNumeroClient(string $numeroClient): ?Client
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
                c.id as client_id,
                c.numero_client,
                c.date_inscription,
                c.statut
            FROM personnes p
            INNER JOIN clients c ON p.id = c.personne_id
            WHERE c.numero_client = :numero_client
            LIMIT 1
        ";

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['numero_client' => $numeroClient]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->mapper($data) : null;
    }

    public function createClient(array $data): Client
    {
        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            $personneData = [
                'nom' => $data['nom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'type_personne' => 'client'
            ];

            $personneId = $this->createPersonne($personneData);

            $numeroClient = $data['numero_client'] ?? $this->generateNumeroClient();

            $clientData = [
                'personne_id' => $personneId,
                'numero_client' => $numeroClient,
                'date_inscription' => $data['date_inscription'] ?? date('Y-m-d'),
                'statut' => $data['statut'] ?? 'actif'
            ];

            $stmt = $pdo->prepare("
                INSERT INTO clients (personne_id, numero_client, date_inscription, statut) 
                VALUES (:personne_id, :numero_client, :date_inscription, :statut)
            ");
            $stmt->execute($clientData);
            
            $clientId = $pdo->lastInsertId();

            $pdo->commit();

            return $this->findById($clientId);

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \Exception("Erreur lors de la création du client: " . $e->getMessage());
        }
    }

    public function updateClient(int $clientId, array $data): Client
    {
        $pdo = $this->db->getPdo();
        
        try {
            $pdo->beginTransaction();

            $client = $this->findById($clientId);

            $personneFields = ['nom', 'email', 'telephone', 'adresse'];
            $personneData = array_intersect_key($data, array_flip($personneFields));
            
            if (!empty($personneData)) {
                $this->updatePersonne($client->getPersonneId(), $personneData);
            }

            $clientFields = ['numero_client', 'statut'];
            $clientData = array_intersect_key($data, array_flip($clientFields));
            
            if (!empty($clientData)) {
                $this->updateClientSpecific($clientId, $clientData);
            }

            $pdo->commit();

            return $this->findById($clientId);

        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new \Exception("Erreur lors de la mise à jour du client: " . $e->getMessage());
        }
    }

    private function updateClientSpecific(int $clientId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $setParts = array_map(fn($key) => "$key = :$key", array_keys($data));
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE clients SET $setClause WHERE id = :client_id";
        $data['client_id'] = $clientId;

        $stmt = $this->db->getPdo()->prepare($sql);
        return $stmt->execute($data);
    }

    public function findActiveClients(): array
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
                c.id as client_id,
                c.numero_client,
                c.date_inscription,
                c.statut
            FROM personnes p
            INNER JOIN clients c ON p.id = c.personne_id
            WHERE p.type_personne = 'client' AND c.statut = 'actif'
            ORDER BY p.nom ASC
        ";

        $stmt = $this->db->getPdo()->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->arrayMapper($data);
    }

    public function countDossiers(int $clientId): int
    {
        $sql = "SELECT COUNT(*) FROM dossiers_juridiques WHERE client_id = :client_id";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        return (int) $stmt->fetchColumn();
    }

    private function generateNumeroClient(): string
    {
        do {
            $numero = 'CL' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->findByNumeroClient($numero);
        } while ($exists);

        return $numero;
    }

    protected function mapper(array $data): Client
    {
        return new Client(
            $this->get($data, 'personne_id'),
            $this->get($data, 'nom'),
            $this->get($data, 'email'),
            $this->get($data, 'telephone'),
            $this->get($data, 'adresse'),
            $this->get($data, 'created_at'),
            $this->get($data, 'updated_at'),
            $this->get($data, 'client_id'),
            $this->get($data, 'personne_id'),
            $this->get($data, 'numero_client'),
            $this->get($data, 'date_inscription'),
            $this->get($data, 'statut')
        );
    }
}