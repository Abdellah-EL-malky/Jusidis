<?php
namespace App\Services\Implementations;

use App\Services\Interfaces\AvocatService;
use App\Repositories\AvocatRepository;
use App\Repositories\DossierJuridiqueRepository;
use App\Models\Avocat;
use InvalidArgumentException;
use RuntimeException;
use PDO;
use Core\Database;

class AvocatDefault implements AvocatService
{
    private AvocatRepository $avocatRepository;
    private DossierJuridiqueRepository $dossierRepository;

    public function __construct(
        AvocatRepository $avocatRepository = null,
        DossierJuridiqueRepository $dossierRepository = null
    ) {
        $this->avocatRepository = $avocatRepository ?? new AvocatRepository();
        $this->dossierRepository = $dossierRepository ?? new DossierJuridiqueRepository();
    }

    public function getAllAvocats(): array
    {
        return $this->avocatRepository->findAllAvocats();
    }

    public function getAvocatById(int $avocatId): Avocat
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        return $this->avocatRepository->findById($avocatId);
    }

    public function createAvocat(array $data): Avocat
    {
        $this->validateAvocatData($data, true);

        if ($this->avocatRepository->emailExists($data['email'])) {
            throw new InvalidArgumentException("Un avocat avec cet email existe déjà.");
        }

        if ($this->avocatRepository->numeroBarreauExists($data['numero_barreau'])) {
            throw new InvalidArgumentException("Un avocat avec ce numéro de barreau existe déjà.");
        }

        try {
            return $this->avocatRepository->createAvocat($data);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la création de l'avocat : " . $e->getMessage());
        }
    }

    public function updateAvocat(int $avocatId, array $data): Avocat
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        $this->validateAvocatData($data, false);

        $existingAvocat = $this->getAvocatById($avocatId);

        if (!empty($data['email']) && $data['email'] !== $existingAvocat->getEmail()) {
            if ($this->avocatRepository->emailExists($data['email'], $existingAvocat->getPersonneId())) {
                throw new InvalidArgumentException("Un autre avocat avec cet email existe déjà.");
            }
        }

        if (!empty($data['numero_barreau']) && $data['numero_barreau'] !== $existingAvocat->getNumeroBarreau()) {
            if ($this->avocatRepository->numeroBarreauExists($data['numero_barreau'], $avocatId)) {
                throw new InvalidArgumentException("Un autre avocat avec ce numéro de barreau existe déjà.");
            }
        }

        try {
            return $this->avocatRepository->updateAvocat($avocatId, $data);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la mise à jour de l'avocat : " . $e->getMessage());
        }
    }

    public function deleteAvocat(int $avocatId): bool
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        if (!$this->canDeleteAvocat($avocatId)) {
            throw new RuntimeException("Cet avocat ne peut pas être supprimé car il a des dossiers actifs.");
        }

        try {
            $avocat = $this->getAvocatById($avocatId);
            
            return $this->avocatRepository->deletePersonne($avocat->getPersonneId());
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la suppression de l'avocat : " . $e->getMessage());
        }
    }

    public function searchAvocats(array $filters): array
    {
        $cleanFilters = $this->sanitizeFilters($filters);
        
        return $this->avocatRepository->search($cleanFilters);
    }

    public function getAvailableAvocats(): array
    {
        return $this->avocatRepository->findAvailableAvocats();
    }

    public function getCertifiedAvocats(): array
    {
        return $this->avocatRepository->findCertifiedAvocats();
    }

    public function findBestAvocatForDossier(int $typeDossierId, ?string $specialisation = null): ?Avocat
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->getPdo()->prepare("SELECT * FROM types_dossiers WHERE id = :id");
            $stmt->execute(['id' => $typeDossierId]);
            $typeDossier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$typeDossier) {
                throw new InvalidArgumentException("Type de dossier non trouvé.");
            }

            $avocatsDisponibles = $this->getAvailableAvocats();

            if (empty($avocatsDisponibles)) {
                return null;
            }

            $avocatsCandidats = [];
            foreach ($avocatsDisponibles as $avocat) {
                if ($typeDossier['exige_avocat_certifie'] && !$avocat->isCertifie()) {
                    continue;
                }

                if ($specialisation && $avocat->getSpecialisation() !== $specialisation) {
                    continue;
                }

                $avocatsCandidats[] = $avocat;
            }

            if (empty($avocatsCandidats)) {
                return null;
            }

            usort($avocatsCandidats, function($a, $b) {
                $capaciteA = $a->getNombreEmplacementsDisponibles();
                $capaciteB = $b->getNombreEmplacementsDisponibles();
                
                if ($capaciteA === $capaciteB) {
                    if ($a->isCertifie() && !$b->isCertifie()) {
                        return -1;
                    }
                    if (!$a->isCertifie() && $b->isCertifie()) {
                        return 1;
                    }
                    return 0;
                }
                
                return $capaciteB - $capaciteA;
            });

            return $avocatsCandidats[0];

        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la recherche du meilleur avocat : " . $e->getMessage());
        }
    }

    public function canHandleDossierType(int $avocatId, int $typeDossierId): bool
    {
        try {
            $avocat = $this->getAvocatById($avocatId);

            if (!$avocat->estDisponible()) {
                return false;
            }

            $db = Database::getInstance();
            $stmt = $db->getPdo()->prepare("SELECT exige_avocat_certifie FROM types_dossiers WHERE id = :id");
            $stmt->execute(['id' => $typeDossierId]);
            $typeDossier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$typeDossier) {
                return false;
            }

            if ($typeDossier['exige_avocat_certifie'] && !$avocat->isCertifie()) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function certifyAvocat(int $avocatId): Avocat
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        $avocat = $this->getAvocatById($avocatId);

        if ($avocat->isCertifie()) {
            throw new RuntimeException("Cet avocat est déjà certifié.");
        }

        try {
            return $this->avocatRepository->updateAvocat($avocatId, ['certifie' => true]);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la certification de l'avocat : " . $e->getMessage());
        }
    }

    public function uncertifyAvocat(int $avocatId): Avocat
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        $avocat = $this->getAvocatById($avocatId);

        if (!$avocat->isCertifie()) {
            throw new RuntimeException("Cet avocat n'est pas certifié.");
        }

        $dossiersActifs = $this->dossierRepository->findByAvocatId($avocatId);
        foreach ($dossiersActifs as $dossier) {
            if ($dossier->estActif() && $dossier->getTypeDossier()['exige_avocat_certifie'] ?? false) {
                throw new RuntimeException("Impossible de décertifier cet avocat car il a des dossiers actifs qui exigent la certification.");
            }
        }

        try {
            return $this->avocatRepository->updateAvocat($avocatId, ['certifie' => false]);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la décertification de l'avocat : " . $e->getMessage());
        }
    }

    public function updateCapacity(int $avocatId, int $newCapacity): Avocat
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        if ($newCapacity < 1 || $newCapacity > 50) {
            throw new InvalidArgumentException("La capacité doit être comprise entre 1 et 50 dossiers.");
        }

        $avocat = $this->getAvocatById($avocatId);

        if ($newCapacity < $avocat->getDossiersActifsActuels()) {
            throw new RuntimeException("La nouvelle capacité ne peut pas être inférieure au nombre de dossiers actifs actuels ({$avocat->getDossiersActifsActuels()}).");
        }

        try {
            return $this->avocatRepository->updateAvocat($avocatId, ['max_dossiers_actifs' => $newCapacity]);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la mise à jour de la capacité : " . $e->getMessage());
        }
    }

    public function getAvocatStatistics(int $avocatId): array
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        try {
            $avocat = $this->getAvocatById($avocatId);
            $dossiers = $this->dossierRepository->findByAvocatId($avocatId);

            $stats = [
                'avocat' => $avocat,
                'total_dossiers' => count($dossiers),
                'dossiers_ouverts' => 0,
                'dossiers_en_cours' => 0,
                'dossiers_clotures' => 0,
                'dossiers_archives' => 0,
                'taux_occupation' => 0,
                'emplacements_disponibles' => $avocat->getNombreEmplacementsDisponibles(),
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

            if ($avocat->getMaxDossiersActifs() > 0) {
                $stats['taux_occupation'] = round(
                    ($avocat->getDossiersActifsActuels() / $avocat->getMaxDossiersActifs()) * 100, 
                    2
                );
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

    public function canDeleteAvocat(int $avocatId): bool
    {
        try {
            $dossiers = $this->dossierRepository->findByAvocatId($avocatId);
            
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

    private function validateAvocatData(array $data, bool $isCreation = true): void
    {
        if ($isCreation) {
            if (empty($data['nom'])) {
                throw new InvalidArgumentException("Le nom de l'avocat est obligatoire.");
            }

            if (empty($data['email'])) {
                throw new InvalidArgumentException("L'email de l'avocat est obligatoire.");
            }

            if (empty($data['numero_barreau'])) {
                throw new InvalidArgumentException("Le numéro de barreau est obligatoire.");
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L'email fourni n'est pas valide.");
        }

        if (!empty($data['nom']) && strlen($data['nom']) < 2) {
            throw new InvalidArgumentException("Le nom doit contenir au moins 2 caractères.");
        }

        if (!empty($data['numero_barreau']) && strlen($data['numero_barreau']) < 3) {
            throw new InvalidArgumentException("Le numéro de barreau doit contenir au moins 3 caractères.");
        }

        if (!empty($data['telephone']) && !preg_match('/^[0-9+\-\s\.]+$/', $data['telephone'])) {
            throw new InvalidArgumentException("Le numéro de téléphone n'est pas valide.");
        }

        if (!empty($data['max_dossiers_actifs'])) {
            $capacity = (int) $data['max_dossiers_actifs'];
            if ($capacity < 1 || $capacity > 50) {
                throw new InvalidArgumentException("La capacité maximale doit être comprise entre 1 et 50.");
            }
        }

        if (!empty($data['date_inscription_barreau'])) {
            if (!$this->isValidDate($data['date_inscription_barreau'])) {
                throw new InvalidArgumentException("La date d'inscription au barreau n'est pas valide.");
            }
        }
    }

    private function sanitizeFilters(array $filters): array
    {
        $allowedFilters = ['nom', 'email', 'specialisation', 'certifie', 'numero_barreau'];
        $cleanFilters = [];

        foreach ($allowedFilters as $filter) {
            if (!empty($filters[$filter])) {
                $cleanFilters[$filter] = trim($filters[$filter]);
            }
        }

        if (isset($cleanFilters['certifie'])) {
            $cleanFilters['certifie'] = filter_var($cleanFilters['certifie'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($cleanFilters['certifie'] === null) {
                unset($cleanFilters['certifie']);
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