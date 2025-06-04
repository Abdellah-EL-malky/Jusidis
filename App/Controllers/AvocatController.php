<?php

namespace App\Controllers;

use Core\Contracts\ResourceController;
use Core\Controller;
use App\Services\Implementations\AvocatDefault;
use App\Services\Interfaces\AvocatService;
use Core\Decorators\Description;
use Core\Decorators\Route;
use Core\Router\RouteMethod;

#[Route('/api/v1')]
class AvocatController extends Controller implements ResourceController
{
    private AvocatService $avocatService;

    public function __construct()
    {
        parent::__construct();
        $this->avocatService = new AvocatDefault();
    }

    public function index()
    {
        try {
            $params = $this->request->param();
            $filters = [];

            if (!empty($params['nom'])) {
                $filters['nom'] = $params['nom'];
            }
            if (!empty($params['specialisation'])) {
                $filters['specialisation'] = $params['specialisation'];
            }
            if (!empty($params['certifie'])) {
                $filters['certifie'] = filter_var($params['certifie'], FILTER_VALIDATE_BOOLEAN);
            }
            
            if (!empty($params['disponibles_seulement']) && $params['disponibles_seulement'] === 'true') {
                return $this->json($this->avocatService->getAvailableAvocats());
            }
            if (!empty($params['certifies_seulement']) && $params['certifies_seulement'] === 'true') {
                return $this->json($this->avocatService->getCertifiedAvocats());
            }

            if (!empty($filters)) {
                return $this->json($this->avocatService->searchAvocats($filters));
            }

            return $this->json($this->avocatService->getAllAvocats());
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $avocat = $this->avocatService->getAvocatById((int) $id);
            
            $params = $this->request->param();
            if (!empty($params['with_stats']) && $params['with_stats'] === 'true') {
                $stats = $this->avocatService->getAvocatStatistics((int) $id);
                return $this->json([
                    'avocat' => $avocat,
                    'statistiques' => $stats
                ]);
            }

            return $this->json($avocat);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->all();
            
            $requiredFields = ['nom', 'email', 'numero_barreau'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->json(["error" => "Le champ '$field' est obligatoire."], 400);
                }
            }

            $avocat = $this->avocatService->createAvocat($data);
            return $this->json($avocat, 201);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 500;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function update($id)
    {
        try {
            $data = $this->request->all();
            $avocat = $this->avocatService->updateAvocat((int) $id, $data);
            return $this->json($avocat);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function destroy($id)
    {
        try {
            $success = $this->avocatService->deleteAvocat((int) $id);
            
            if ($success) {
                return $this->json(["message" => "Avocat supprimé avec succès"]);
            } else {
                return $this->json(["error" => "Erreur lors de la suppression"], 500);
            }
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function certifier($avocatId)
    {
        try {
            $avocat = $this->avocatService->certifyAvocat((int) $avocatId);
            return $this->json([
                "message" => "Avocat certifié avec succès",
                "avocat" => $avocat
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function decertifier($avocatId)
    {
        try {
            $avocat = $this->avocatService->uncertifyAvocat((int) $avocatId);
            return $this->json([
                "message" => "Certification retirée avec succès",
                "avocat" => $avocat
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function updateCapacite($avocatId)
    {
        try {
            $data = $this->request->all();
            
            if (!isset($data['nouvelle_capacite'])) {
                return $this->json(["error" => "Le champ 'nouvelle_capacite' est obligatoire."], 400);
            }

            $avocat = $this->avocatService->updateCapacity((int) $avocatId, (int) $data['nouvelle_capacite']);
            return $this->json([
                "message" => "Capacité mise à jour avec succès",
                "avocat" => $avocat
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function meilleurPourDossier($typeDossierId)
    {
        try {
            $params = $this->request->param();
            $specialisation = $params['specialisation'] ?? null;
            
            $avocat = $this->avocatService->findBestAvocatForDossier((int) $typeDossierId, $specialisation);
            
            if ($avocat) {
                return $this->json([
                    "avocat" => $avocat,
                    "message" => "Meilleur avocat trouvé"
                ]);
            } else {
                return $this->json([
                    "avocat" => null,
                    "message" => "Aucun avocat disponible pour ce type de dossier"
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }
}