<?php
namespace App\Services\Implementations;

use App\Services\Interfaces\DossierJuridiqueService;
use App\Services\Interfaces\AvocatService;
use App\Repositories\DossierJuridiqueRepository;
use App\Repositories\ClientRepository;
use App\Repositories\AvocatRepository;
use App\Repositories\EtapeRepository;
use App\Repositories\HistoriqueRepository;
use App\Models\DossierJuridique;
use App\Models\Avocat;
use InvalidArgumentException;
use RuntimeException;
use PDO;
use Core\Database;

class DossierJuridiqueDefault implements DossierJuridiqueService
{
    private DossierJuridiqueRepository $dossierRepository;
    private ClientRepository $clientRepository;
    private AvocatRepository $avocatRepository;
    private EtapeRepository $etapeRepository;
    private HistoriqueRepository $historiqueRepository;
    private AvocatService $avocatService;

    public function __construct(
        DossierJuridiqueRepository $dossierRepository = null,
        ClientRepository $clientRepository = null,
        AvocatRepository $avocatRepository = null,
        EtapeRepository $etapeRepository = null,
        HistoriqueRepository $historiqueRepository = null,
        AvocatService $avocatService = null
    ) {
        $this->dossierRepository = $dossierRepository ?? new DossierJuridiqueRepository();
        $this->clientRepository = $clientRepository ?? new ClientRepository();
        $this->avocatRepository = $avocatRepository ?? new AvocatRepository();
        $this->etapeRepository = $etapeRepository ?? new EtapeRepository();
        $this->historiqueRepository = $historiqueRepository ?? new HistoriqueRepository();
        $this->avocatService = $avocatService ?? new AvocatDefault();
    }

    public function getAllDossiers(): array
    {
        return $this->dossierRepository->findAllDossiers();
    }

    public function getDossierById(int $dossierId): DossierJuridique
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        return $this->dossierRepository->findById($dossierId);
    }

    public function createDossier(array $data, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        $this->validateDossierData($data, true);

        try {
            $this->clientRepository->findById($data['client_id']);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Client non trouvé.");
        }

        $typeDossier = $this->getTypeDossier($data['type_dossier_id']);
        if (!$typeDossier) {
            throw new InvalidArgumentException("Type de dossier non trouvé.");
        }

        try {
            $dossier = $this->dossierRepository->createDossier($data);

            $this->etapeRepository->createEtapesParDefaut($dossier->getId(), $data['type_dossier_id']);

            $this->historiqueRepository->enregistrerCreation(
                $dossier->getId(),
                $effectueParType,
                $effectueParId,
                $dossier->getTitre()
            );

            return $this->getDossierById($dossier->getId());
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la création du dossier : " . $e->getMessage());
        }
    }

    public function updateDossier(int $dossierId, array $data, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }
        $this->validateDossierData($data, false);
        $dossierActuel = $this->getDossierById($dossierId);
        $this->enregistrerChangements($dossierActuel, $data, $effectueParType, $effectueParId);

        try {            $updateData = array_intersect_key($data, array_flip([
                'titre', 'description', 'priorite'
            ]));

            if (!empty($updateData)) {
                foreach ($updateData as $field => $value) {
                    $this->dossierRepository->update([$field => $value], ['id' => $dossierId]);
                }
            }

            return $this->getDossierById($dossierId);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la mise à jour du dossier : " . $e->getMessage());
        }
    }

    public function deleteDossier(int $dossierId, string $effectueParType, ?int $effectueParId): bool
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }
        if (!$this->canDeleteDossier($dossierId)) {
            throw new RuntimeException("Ce dossier ne peut pas être supprimé car il contient des données critiques.");
        }

        try {
            $dossier = $this->getDossierById($dossierId);
            if ($dossier->getAvocatId() && $dossier->estActif()) {
                $this->avocatRepository->decrementDossiersActifs($dossier->getAvocatId());
            }

            return $this->dossierRepository->delete(['id' => $dossierId]);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la suppression du dossier : " . $e->getMessage());
        }
    }

    public function assignerAvocat(int $dossierId, int $avocatId, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        if ($dossierId <= 0 || $avocatId <= 0) {
            throw new InvalidArgumentException("Les IDs doivent être des entiers positifs.");
        }

        $dossier = $this->getDossierById($dossierId);
        $avocat = $this->avocatService->getAvocatById($avocatId);
        if (!$this->avocatService->canHandleDossierType($avocatId, $dossier->getTypeDossierId())) {
            throw new RuntimeException("Cet avocat ne peut pas traiter ce type de dossier (certification requise ou capacité insuffisante).");
        }

        try {
            $ancienAvocatNom = null;
            $ancienAvocatId = $dossier->getAvocatId();
            if ($ancienAvocatId && $dossier->estActif()) {
                $this->avocatRepository->decrementDossiersActifs($ancienAvocatId);
                $ancienAvocat = $this->avocatService->getAvocatById($ancienAvocatId);
                $ancienAvocatNom = $ancienAvocat->getNom();
            }
            $this->dossierRepository->assignerAvocat($dossierId, $avocatId);
            if ($dossier->estActif()) {
                $this->avocatRepository->incrementDossiersActifs($avocatId);
            }
            $this->historiqueRepository->enregistrerChangementAvocat(
                $dossierId,
                $effectueParType,
                $effectueParId,
                $ancienAvocatNom,
                $avocat->getNom()
            );

            return $this->getDossierById($dossierId);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de l'assignation de l'avocat : " . $e->getMessage());
        }
    }

    public function desassignerAvocat(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        $dossier = $this->getDossierById($dossierId);

        if (!$dossier->getAvocatId()) {
            throw new RuntimeException("Ce dossier n'a pas d'avocat assigné.");
        }

        try {
            $avocat = $this->avocatService->getAvocatById($dossier->getAvocatId());
            $avocatNom = $avocat->getNom();
            if ($dossier->estActif()) {
                $this->avocatRepository->decrementDossiersActifs($dossier->getAvocatId());
            }
            $this->dossierRepository->assignerAvocat($dossierId, null);
            $this->historiqueRepository->enregistrerChangementAvocat(
                $dossierId,
                $effectueParType,
                $effectueParId,
                $avocatNom,
                null
            );

            return $this->getDossierById($dossierId);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la désassignation de l'avocat : " . $e->getMessage());
        }
    }

    public function changerStatut(int $dossierId, string $nouveauStatut, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        $statutsValides = ['ouvert', 'en_cours', 'cloture', 'archive'];
        if (!in_array($nouveauStatut, $statutsValides)) {
            throw new InvalidArgumentException("Statut non valide. Statuts autorisés : " . implode(', ', $statutsValides));
        }

        $dossier = $this->getDossierById($dossierId);
        $ancienStatut = $dossier->getStatut();

        if ($ancienStatut === $nouveauStatut) {
            throw new RuntimeException("Le dossier a déjà ce statut.");
        }

        if ($nouveauStatut === 'cloture') {
            return $this->cloturerDossier($dossierId, $effectueParType, $effectueParId);
        }

        try {
            $this->gererCompteurAvocatSelonStatut($dossier, $ancienStatut, $nouveauStatut);

            $this->dossierRepository->changerStatut($dossierId, $nouveauStatut);

            $this->historiqueRepository->enregistrerChangementStatut(
                $dossierId,
                $effectueParType,
                $effectueParId,
                $ancienStatut,
                $nouveauStatut
            );

            return $this->getDossierById($dossierId);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors du changement de statut : " . $e->getMessage());
        }
    }

    public function cloturerDossier(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        if (!$this->canCloture($dossierId)) {
            throw new RuntimeException("Ce dossier ne peut pas être clôturé car toutes les étapes obligatoires ne sont pas validées.");
        }

        $dossier = $this->getDossierById($dossierId);

        if ($dossier->estCloture()) {
            throw new RuntimeException("Ce dossier est déjà clôturé.");
        }

        try {
            $ancienStatut = $dossier->getStatut();

            $this->dossierRepository->changerStatut($dossierId, 'cloture');

            if ($dossier->getAvocatId() && $dossier->estActif()) {
                $this->avocatRepository->decrementDossiersActifs($dossier->getAvocatId());
            }

            $this->historiqueRepository->enregistrerCloture(
                $dossierId,
                $effectueParType,
                $effectueParId
            );

            return $this->getDossierById($dossierId);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la clôture du dossier : " . $e->getMessage());
        }
    }

    public function reouvrir(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        $dossier = $this->getDossierById($dossierId);

        if (!$dossier->estCloture()) {
            throw new RuntimeException("Seuls les dossiers clôturés peuvent être réouverts.");
        }

        try {
            if ($dossier->getAvocatId()) {
                $avocat = $this->avocatService->getAvocatById($dossier->getAvocatId());
                if (!$avocat->estDisponible()) {
                    throw new RuntimeException("L'avocat assigné n'a plus de capacité disponible. Veuillez réassigner un autre avocat.");
                }
            }

            $this->dossierRepository->changerStatut($dossierId, 'en_cours');

            if ($dossier->getAvocatId()) {
                $this->avocatRepository->incrementDossiersActifs($dossier->getAvocatId());
            }

            $this->historiqueRepository->enregistrerChangementStatut(
                $dossierId,
                $effectueParType,
                $effectueParId,
                'cloture',
                'en_cours'
            );

            return $this->getDossierById($dossierId);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de la réouverture du dossier : " . $e->getMessage());
        }
    }

    public function archiverDossier(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        $dossier = $this->getDossierById($dossierId);

        if (!$dossier->estCloture()) {
            throw new RuntimeException("Seuls les dossiers clôturés peuvent être archivés.");
        }

        try {
            return $this->changerStatut($dossierId, 'archive', $effectueParType, $effectueParId);
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors de l'archivage du dossier : " . $e->getMessage());
        }
    }

    public function searchDossiers(array $filters): array
    {
        $cleanFilters = $this->sanitizeFilters($filters);
        
        return $this->dossierRepository->search($cleanFilters);
    }

    public function getDossiersByClient(int $clientId): array
    {
        if ($clientId <= 0) {
            throw new InvalidArgumentException("L'ID du client doit être un entier positif.");
        }

        try {
            $this->clientRepository->findById($clientId);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Client non trouvé.");
        }

        return $this->dossierRepository->findByClientId($clientId);
    }

    public function getDossiersByAvocat(int $avocatId): array
    {
        if ($avocatId <= 0) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être un entier positif.");
        }

        try {
            $this->avocatService->getAvocatById($avocatId);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Avocat non trouvé.");
        }

        return $this->dossierRepository->findByAvocatId($avocatId);
    }

    public function getDossiersByStatut(string $statut): array
    {
        $statutsValides = ['ouvert', 'en_cours', 'cloture', 'archive'];
        if (!in_array($statut, $statutsValides)) {
            throw new InvalidArgumentException("Statut non valide. Statuts autorisés : " . implode(', ', $statutsValides));
        }

        return $this->dossierRepository->findByStatut($statut);
    }

    public function findBestAvocatForDossier(int $dossierId): ?Avocat
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        $dossier = $this->getDossierById($dossierId);
        
        return $this->avocatService->findBestAvocatForDossier($dossier->getTypeDossierId());
    }

    public function canCloture(int $dossierId): bool
    {
        try {
            return $this->etapeRepository->verifierToutesEtapesValidees($dossierId);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDossierStatistics(int $dossierId): array
    {
        if ($dossierId <= 0) {
            throw new InvalidArgumentException("L'ID du dossier doit être un entier positif.");
        }

        try {
            $dossier = $this->getDossierById($dossierId);
            $etapes = $this->etapeRepository->findByDossierId($dossierId);
            $historique = $this->historiqueRepository->findByDossierId($dossierId);

            $stats = [
                'dossier' => $dossier,
                'nombre_etapes' => count($etapes),
                'etapes_validees' => 0,
                'etapes_en_cours' => 0,
                'etapes_en_attente' => 0,
                'pourcentage_avancement' => 0,
                'nombre_actions_historique' => count($historique),
                'peut_etre_cloture' => $this->canCloture($dossierId),
                'duree_depuis_ouverture' => null,
                'derniere_activite' => null
            ];

            foreach ($etapes as $etape) {
                switch ($etape->getStatut()) {
                    case 'validee':
                        $stats['etapes_validees']++;
                        break;
                    case 'en_cours':
                        $stats['etapes_en_cours']++;
                        break;
                    case 'en_attente':
                        $stats['etapes_en_attente']++;
                        break;
                }
            }

            $stats['pourcentage_avancement'] = $this->etapeRepository->calculerPourcentageAvancement($dossierId);

            if ($dossier->getDateOuverture()) {
                $dateOuverture = new \DateTime($dossier->getDateOuverture());
                $maintenant = new \DateTime();
                $stats['duree_depuis_ouverture'] = $dateOuverture->diff($maintenant)->days;
            }

            if (!empty($historique)) {
                $stats['derniere_activite'] = $historique[0]->getDateAction();
            }

            return $stats;
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors du calcul des statistiques : " . $e->getMessage());
        }
    }

    public function getGeneralStatistics(): array
    {
        try {
            $statsStatuts = $this->dossierRepository->countByStatut();
            
            return [
                'total_dossiers' => array_sum($statsStatuts),
                'dossiers_ouverts' => $statsStatuts['ouvert'] ?? 0,
                'dossiers_en_cours' => $statsStatuts['en_cours'] ?? 0,
                'dossiers_clotures' => $statsStatuts['cloture'] ?? 0,
                'dossiers_archives' => $statsStatuts['archive'] ?? 0,
                'taux_cloture' => $this->calculerTauxCloture($statsStatuts),
                'avocats_disponibles' => count($this->avocatService->getAvailableAvocats()),
                'avocats_certifies' => count($this->avocatService->getCertifiedAvocats())
            ];
        } catch (\Exception $e) {
            throw new RuntimeException("Erreur lors du calcul des statistiques générales : " . $e->getMessage());
        }
    }

    public function canDeleteDossier(int $dossierId): bool
    {
        try {
            $dossier = $this->getDossierById($dossierId);
            
            return !in_array($dossier->getStatut(), ['cloture', 'archive']);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateDossierData(array $data, bool $isCreation = true): void
    {
        if ($isCreation) {
            if (empty($data['titre'])) {
                throw new InvalidArgumentException("Le titre du dossier est obligatoire.");
            }

            if (empty($data['type_dossier_id'])) {
                throw new InvalidArgumentException("Le type de dossier est obligatoire.");
            }

            if (empty($data['client_id'])) {
                throw new InvalidArgumentException("Le client est obligatoire.");
            }
        }

        if (!empty($data['titre']) && strlen($data['titre']) < 3) {
            throw new InvalidArgumentException("Le titre doit contenir au moins 3 caractères.");
        }

        if (!empty($data['priorite'])) {
            $prioritesValides = ['basse', 'normale', 'haute', 'urgente'];
            if (!in_array($data['priorite'], $prioritesValides)) {
                throw new InvalidArgumentException("Priorité non valide. Priorités autorisées : " . implode(', ', $prioritesValides));
            }
        }

        if (!empty($data['type_dossier_id']) && !is_numeric($data['type_dossier_id'])) {
            throw new InvalidArgumentException("L'ID du type de dossier doit être numérique.");
        }

        if (!empty($data['client_id']) && !is_numeric($data['client_id'])) {
            throw new InvalidArgumentException("L'ID du client doit être numérique.");
        }

        if (!empty($data['avocat_id']) && !is_numeric($data['avocat_id'])) {
            throw new InvalidArgumentException("L'ID de l'avocat doit être numérique.");
        }
    }

    private function sanitizeFilters(array $filters): array
    {
        $allowedFilters = [
            'titre', 'statut', 'client_id', 'avocat_id', 'type_dossier_id', 
            'priorite', 'date_debut', 'date_fin'
        ];
        $cleanFilters = [];

        foreach ($allowedFilters as $filter) {
            if (!empty($filters[$filter])) {
                $cleanFilters[$filter] = trim($filters[$filter]);
            }
        }

        if (!empty($cleanFilters['date_debut'])) {
            if (!$this->isValidDate($cleanFilters['date_debut'])) {
                throw new InvalidArgumentException("La date de début n'est pas valide.");
            }
        }

        if (!empty($cleanFilters['date_fin'])) {
            if (!$this->isValidDate($cleanFilters['date_fin'])) {
                throw new InvalidArgumentException("La date de fin n'est pas valide.");
            }
        }

        return $cleanFilters;
    }

    private function enregistrerChangements(DossierJuridique $dossierActuel, array $nouveauxData, string $effectueParType, ?int $effectueParId): void
    {
        $champsTraques = ['titre', 'description', 'priorite'];
        
        foreach ($champsTraques as $champ) {
            if (isset($nouveauxData[$champ])) {
                $ancienneValeur = $this->getValueByField($dossierActuel, $champ);
                $nouvelleValeur = $nouveauxData[$champ];
                
                if ($ancienneValeur !== $nouvelleValeur) {
                    $this->historiqueRepository->createEntree([
                        'dossier_id' => $dossierActuel->getId(),
                        'type_action' => 'changement_statut',
                        'description' => "Modification du champ '$champ'",
                        'effectue_par_type' => $effectueParType,
                        'effectue_par_id' => $effectueParId,
                        'ancienne_valeur' => $ancienneValeur,
                        'nouvelle_valeur' => $nouvelleValeur
                    ]);
                }
            }
        }
    }

    private function getValueByField(DossierJuridique $dossier, string $field): ?string
    {
        return match($field) {
            'titre' => $dossier->getTitre(),
            'description' => $dossier->getDescription(),
            'priorite' => $dossier->getPriorite(),
            default => null
        };
    }

    private function gererCompteurAvocatSelonStatut(DossierJuridique $dossier, string $ancienStatut, string $nouveauStatut): void
    {
        if (!$dossier->getAvocatId()) {
            return;
        }

        $ancienActif = in_array($ancienStatut, ['ouvert', 'en_cours']);
        $nouveauActif = in_array($nouveauStatut, ['ouvert', 'en_cours']);

        if ($ancienActif && !$nouveauActif) {
            $this->avocatRepository->decrementDossiersActifs($dossier->getAvocatId());
        } elseif (!$ancienActif && $nouveauActif) {
            $avocat = $this->avocatService->getAvocatById($dossier->getAvocatId());
            if (!$avocat->estDisponible()) {
                throw new RuntimeException("L'avocat assigné n'a plus de capacité disponible.");
            }
            $this->avocatRepository->incrementDossiersActifs($dossier->getAvocatId());
        }
    }

    private function getTypeDossier(int $typeDossierId): ?array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->getPdo()->prepare("SELECT * FROM types_dossiers WHERE id = :id");
            $stmt->execute(['id' => $typeDossierId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function calculerTauxCloture(array $statsStatuts): float
    {
        $totalDossiers = array_sum($statsStatuts);
        if ($totalDossiers === 0) {
            return 0.0;
        }

        $dossiersClotures = ($statsStatuts['cloture'] ?? 0) + ($statsStatuts['archive'] ?? 0);
        return round(($dossiersClotures / $totalDossiers) * 100, 2);
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}