<?php
namespace App\Services\Interfaces;

use App\Models\Client;

interface ClientService
{
    public function getAllClients(): array;

    public function getClientById(int $clientId): Client;

    public function createClient(array $data): Client;

    public function updateClient(int $clientId, array $data): Client;

    public function deleteClient(int $clientId): bool;

    public function searchClients(array $filters): array;

    public function getActiveClients(): array;

    public function deactivateClient(int $clientId): Client;

    public function activateClient(int $clientId): Client;

    public function canDeleteClient(int $clientId): bool;

    public function getClientStatistics(int $clientId): array;
}