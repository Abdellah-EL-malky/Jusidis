<?php

namespace App\Controllers;

use Core\Contracts\ResourceController;
use Core\Controller;
use App\Services\Implementations\ClientDefault;
use App\Services\Interfaces\ClientService;
use Core\Decorators\Description;
use Core\Decorators\Route;

class ClientController extends Controller implements ResourceController
{
    private ClientService $clientService;

    public function __construct()
    {
        parent::__construct();
        $this->clientService = new ClientDefault();
    }

    public function index()
    {
        try {
            $params = $this->request->param();
            $filters = [];

            if (!empty($params['nom'])) {
                $filters['nom'] = $params['nom'];
            }
            if (!empty($params['email'])) {
                $filters['email'] = $params['email'];
            }
            if (!empty($params['statut'])) {
                $filters['statut'] = $params['statut'];
            }
            if (!empty($params['actifs_seulement']) && $params['actifs_seulement'] === 'true') {
                return $this->json($this->clientService->getActiveClients());
            }

            if (!empty($filters)) {
                return $this->json($this->clientService->searchClients($filters));
            }

            return $this->json($this->clientService->getAllClients());
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $client = $this->clientService->getClientById((int) $id);
            
            $params = $this->request->param();
            if (!empty($params['with_stats']) && $params['with_stats'] === 'true') {
                $stats = $this->clientService->getClientStatistics((int) $id);
                return $this->json([
                    'client' => $client,
                    'statistiques' => $stats
                ]);
            }

            return $this->json($client);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->all();
            
            $requiredFields = ['nom', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->json(["error" => "Le champ '$field' est obligatoire."], 400);
                }
            }

            $client = $this->clientService->createClient($data);
            return $this->json($client, 201);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 500;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function update($id)
    {
        try {
            $data = $this->request->all();
            $client = $this->clientService->updateClient((int) $id, $data);
            return $this->json($client);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function destroy($id)
    {
        try {
            $success = $this->clientService->deleteClient((int) $id);
            
            if ($success) {
                return $this->json(["message" => "Client supprimé avec succès"]);
            } else {
                return $this->json(["error" => "Erreur lors de la suppression"], 500);
            }
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }
}