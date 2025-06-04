<?php
namespace App\Services\Interfaces;

use App\Models\Avocat;

interface AvocatService
{
    public function getAllAvocats(): array;

    public function getAvocatById(int $avocatId): Avocat;

    public function createAvocat(array $data): Avocat;

    public function updateAvocat(int $avocatId, array $data): Avocat;

    public function deleteAvocat(int $avocatId): bool;

    public function searchAvocats(array $filters): array;

    public function getAvailableAvocats(): array;

    public function getCertifiedAvocats(): array;

    public function findBestAvocatForDossier(int $typeDossierId, ?string $specialisation = null): ?Avocat;

    public function canHandleDossierType(int $avocatId, int $typeDossierId): bool;

    public function certifyAvocat(int $avocatId): Avocat;

    public function uncertifyAvocat(int $avocatId): Avocat;

    public function updateCapacity(int $avocatId, int $newCapacity): Avocat;

    public function getAvocatStatistics(int $avocatId): array;

    public function canDeleteAvocat(int $avocatId): bool;
}