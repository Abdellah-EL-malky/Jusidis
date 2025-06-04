<?php
namespace App\Services\Implementations;

use App\Services\Interfaces\ClientService;
use App\Repositories\ClientRepository;
use App\Repositories\DossierJuridiqueRepository;
use App\Models\Client;
use InvalidArgumentException;
use RuntimeException;

class ClientDefault implements ClientService
{
    private ClientRepository $clientRepository;
    private DossierJuridiqueRepository $dossierRepository;

    public function __construct(
        ClientRepository $clientRepository = null,
        DossierJuridiqueRepository $dossierRepository = null
    ) {
        $this->clientRepository = $clientRepository ?? new ClientRepository();
        $this->dossierRepository = $dossierRepository ?? new DossierJuridiqueRepository();
    }

    public function getAllClients(): array
    {
        return $this->clientRepository->findAllClients();
    }

    public function getClientById(int $clientId): Client
    {
        if ($clientId <= 0) {
            throw new InvalidArgumentException("L'ID du client doit être un entier positif.");
        }

        return $this->clientRepository->findById($clientId);
    }

    public function createClient(array $data): Client
    {
        $this->validateClientData($data, true);

        if ($this->clientRepository->emailExists($data['email'])) {
            throw new InvalidArgumentException("Un client avec cet email existe déjà.");
        }

        try {
            return $this->clientRepository->createClient($data);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la création du client : " . $e->getMessage());
        }
    }

    public function updateClient(int $clientId, array $data): Client
    {
        if ($clientId <= 0) {
            throw new InvalidArgumentException("L'ID du client doit être un entier positif.");
        }

        $this->validateClientData($data, false);

        $existingClient = $this->getClientById($clientId);

        if (!empty($data['email']) && $data['email'] !== $existingClient->getEmail()) {
            if ($this->clientRepository->emailExists($data['email'], $existingClient->getPersonneId())) {
                throw new InvalidArgumentException("Un autre client avec cet email existe déjà.");
            }
        }

        try {
            return $this->clientRepository->updateClient($clientId, $data);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la mise à jour du client : " . $e->getMessage());
        }
    }

    public function deleteClient(int $clientId): bool
    {
        if ($clientId <= 0) {
            throw new InvalidArgumentException("L'ID du client doit être un entier positif.");
        }

        if (!$this->canDeleteClient($clientId)) {
            throw new RuntimeException("Ce client ne peut pas être supprimé car il a des dossiers actifs.");
        }

        try {
            $client = $this->getClientById($clientId);
            
            return $this->clientRepository->deletePersonne($client->getPersonneId());
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la suppression du client : " . $e->getMessage());
        }
    }

    public function searchClients(array $filters): array
    {
        $cleanFilters = $this->sanitizeFilters($filters);
        
        return $this->clientRepository->search($cleanFilters);
    }

    public function getActiveClients(): array
    {
        return $this->clientRepository->findActiveClients();
    }

    public function deactivateClient(int $clientId): Client
    {
        if ($clientId <= 0) {
            throw new InvalidArgumentException("L'ID du client doit être un entier positif.");
        }

        $client = $this->getClientById($clientId);
        
        if (!$client->isActif()) {
            throw new RuntimeException("Ce client est déjà inactif.");
        }

        $dossiersActifs = $this->dossierRepository->findByClientId($clientId);
        $hasActiveDossiers = false;
        
        foreach ($dossiersActifs as $dossier) {
            if ($dossier->estActif()) {
                $hasActiveDossiers = true;
                break;
            }
        }

        if ($hasActiveDossiers) {
            throw new RuntimeException("Impossible de désactiver un client ayant des dossiers actifs.");
        }

        try {
            return $this->clientRepository->updateClient($clientId, ['statut' => 'inactif']);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la désactivation du client : " . $e->getMessage());
        }
    }

    public function activateClient(int $clientId): Client
    {
        if ($clientId <= 0) {
            throw new InvalidArgumentException("L'ID du client doit être un entier positif.");
        }

        $client = $this->getClientById($clientId);
        
        if ($client->isActif()) {
            throw new RuntimeException("Ce client est déjà actif.");
        }

        try {
            return $this->clientRepository->updateClient($clientId, ['statut' => 'actif']);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de l'activation du client : " . $e->getMessage());
        }
    }

    public function canDeleteClient(int $clientId): bool
    {
        try {
            $dossiers = $this->dossierRepository->findByClientId($clientId);
            
            foreach ($dossiers as $dossier) {
                if ($dossier->estActif()) {
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getClientStatistics(int $clientId): array
    {
        if ($clientId <= 0) {
            throw new InvalidArgumentException("L'ID du client doit être un entier positif.");
        }

        try {
            $client = $this->getClientById($clientId);
            $dossiers = $this->dossierRepository->findByClientId($clientId);

            $stats = [
                'client' => $client,
                'total_dossiers' => count($dossiers),
                'dossiers_ouverts' => 0,
                'dossiers_en_cours' => 0,
                'dossiers_clotures' => 0,
                'dossiers_archives' => 0,
                'date_premier_dossier' => null,
                'date_dernier_dossier' => null
            ];

            $dates = [];
            foreach ($dossiers as $dossier) {
                $statut = $dossier->getStatut();
                switch ($statut) {
                    case 'ouvert':
                        $stats['dossiers_ouverts']++;
                        break;
                    case 'en_cours':
                        $stats['dossiers_en_cours']++;
                        break;
                    case 'cloture':
                        $stats['dossiers_clotures']++;
                        break;
                    case 'archive':
                        $stats['dossiers_archives']++;
                        break;
                }

                if ($dossier->getDateOuverture()) {
                    $dates[] = $dossier->getDateOuverture();
                }
            }

            if (!empty($dates)) {
                sort($dates);
                $stats['date_premier_dossier'] = $dates[0];
                $stats['date_dernier_dossier'] = end($dates);
            }

            return $stats;
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors du calcul des statistiques : " . $e->getMessage());
        }
    }

    private function validateClientData(array $data, bool $isCreation = true): void
    {
        if ($isCreation) {
            if (empty($data['nom'])) {
                throw new InvalidArgumentException("Le nom du client est obligatoire.");
            }

            if (empty($data['email'])) {
                throw new InvalidArgumentException("L'email du client est obligatoire.");
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L'email fourni n'est pas valide.");
        }

        if (!empty($data['nom']) && strlen($data['nom']) < 2) {
            throw new InvalidArgumentException("Le nom doit contenir au moins 2 caractères.");
        }

        if (!empty($data['telephone']) && !preg_match('/^[0-9+\-\s\.]+$/', $data['telephone'])) {
            throw new InvalidArgumentException("Le numéro de téléphone n'est pas valide.");
        }

        if (!empty($data['statut']) && !in_array($data['statut'], ['actif', 'inactif'])) {
            throw new InvalidArgumentException("Le statut doit être 'actif' ou 'inactif'.");
        }
    }

    private function sanitizeFilters(array $filters): array
    {
        $allowedFilters = ['nom', 'email', 'statut', 'date_inscription_debut', 'date_inscription_fin'];
        $cleanFilters = [];

        foreach ($allowedFilters as $filter) {
            if (!empty($filters[$filter])) {
                $cleanFilters[$filter] = trim($filters[$filter]);
            }
        }

        if (!empty($cleanFilters['date_inscription_debut'])) {
            if (!$this->isValidDate($cleanFilters['date_inscription_debut'])) {
                throw new InvalidArgumentException("La date de début d'inscription n'est pas valide.");
            }
        }

        if (!empty($cleanFilters['date_inscription_fin'])) {
            if (!$this->isValidDate($cleanFilters['date_inscription_fin'])) {
                throw new InvalidArgumentException("La date de fin d'inscription n'est pas valide.");
            }
        }

        return $cleanFilters;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}