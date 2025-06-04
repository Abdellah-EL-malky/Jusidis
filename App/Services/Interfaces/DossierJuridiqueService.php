<?php
namespace App\Services\Interfaces;

use App\Models\DossierJuridique;
use App\Models\Avocat;

interface DossierJuridiqueService
{
    public function getAllDossiers(): array;

    public function getDossierById(int $dossierId): DossierJuridique;

    public function createDossier(array $data, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function updateDossier(int $dossierId, array $data, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function deleteDossier(int $dossierId, string $effectueParType, ?int $effectueParId): bool;

    public function assignerAvocat(int $dossierId, int $avocatId, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function desassignerAvocat(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function changerStatut(int $dossierId, string $nouveauStatut, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function cloturerDossier(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function reouvrir(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function archiverDossier(int $dossierId, string $effectueParType, ?int $effectueParId): DossierJuridique;

    public function searchDossiers(array $filters): array;

    public function getDossiersByClient(int $clientId): array;

    public function getDossiersByAvocat(int $avocatId): array;

    public function getDossiersByStatut(string $statut): array;

    public function findBestAvocatForDossier(int $dossierId): ?Avocat;

    public function canCloture(int $dossierId): bool;

    public function getDossierStatistics(int $dossierId): array;

    public function getGeneralStatistics(): array;

    public function canDeleteDossier(int $dossierId): bool;
}