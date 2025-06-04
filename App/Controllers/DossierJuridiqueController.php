<?php

namespace App\Controllers;

use Core\Contracts\ResourceController;
use Core\Controller;
use App\Services\Implementations\DossierJuridiqueDefault;
use App\Services\Interfaces\DossierJuridiqueService;
use Core\Decorators\Description;
use Core\Decorators\Route;
use Core\Router\RouteMethod;

class DossierJuridiqueController extends Controller implements ResourceController
{
    private DossierJuridiqueService $dossierService;

    public function __construct()
    {
        parent::__construct();
        $this->dossierService = new DossierJuridiqueDefault();
    }

    public function index()
    {
        try {
            $params = $this->request->param();
            $filters = [];

            $allowedFilters = ['titre', 'statut', 'client_id', 'avocat_id', 'type_dossier_id', 'priorite'];
            foreach ($allowedFilters as $filter) {
                if (!empty($params[$filter])) {
                    $filters[$filter] = $params[$filter];
                }
            }

            if (!empty($params['statut_specifique'])) {
                return $this->json($this->dossierService->getDossiersByStatut($params['statut_specifique']));
            }

            if (!empty($filters)) {
                return $this->json($this->dossierService->searchDossiers($filters));
            }

            return $this->json($this->dossierService->getAllDossiers());
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $dossier = $this->dossierService->getDossierById((int) $id);
                        $params = $this->request->param();
            if (!empty($params['with_stats']) && $params['with_stats'] === 'true') {
                $stats = $this->dossierService->getDossierStatistics((int) $id);
                return $this->json([
                    'dossier' => $dossier,
                    'statistiques' => $stats
                ]);
            }

            return $this->json($dossier);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->all();
                        $requiredFields = ['titre', 'type_dossier_id', 'client_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->json(["error" => "Le champ '$field' est obligatoire."], 400);
                }
            }
            $dossier = $this->dossierService->createDossier($data, 'systeme', null);
            return $this->json($dossier, 201);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 500;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function update($id)
    {
        try {
            $data = $this->request->all();
            $dossier = $this->dossierService->updateDossier((int) $id, $data, 'systeme', null);
            return $this->json($dossier);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function destroy($id)
    {
        try {
            $success = $this->dossierService->deleteDossier((int) $id, 'systeme', null);
            
            if ($success) {
                return $this->json(["message" => "Dossier supprimé avec succès"]);
            } else {
                return $this->json(["error" => "Erreur lors de la suppression"], 500);
            }
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }    

    public function assignerAvocat($dossierId)
    {
        try {
            $data = $this->request->all();
            
            if (empty($data['avocat_id'])) {
                return $this->json(["error" => "Le champ 'avocat_id' est obligatoire."], 400);
            }

            $dossier = $this->dossierService->assignerAvocat(
                (int) $dossierId, 
                (int) $data['avocat_id'], 
                'systeme', 
                null
            );
            
            return $this->json([
                "message" => "Avocat assigné avec succès",
                "dossier" => $dossier
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }    

    public function desassignerAvocat($dossierId)
    {
        try {
            $dossier = $this->dossierService->desassignerAvocat((int) $dossierId, 'systeme', null);
            
            return $this->json([
                "message" => "Avocat désassigné avec succès",
                "dossier" => $dossier
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function changerStatut($dossierId)
    {
        try {
            $data = $this->request->all();
            
            if (empty($data['nouveau_statut'])) {
                return $this->json(["error" => "Le champ 'nouveau_statut' est obligatoire."], 400);
            }

            $dossier = $this->dossierService->changerStatut(
                (int) $dossierId, 
                $data['nouveau_statut'], 
                'systeme', 
                null
            );
            
            return $this->json([
                "message" => "Statut changé avec succès",
                "dossier" => $dossier
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }    

    public function cloturer($dossierId)
    {
        try {
            $dossier = $this->dossierService->cloturerDossier((int) $dossierId, 'systeme', null);
            
            return $this->json([
                "message" => "Dossier clôturé avec succès",
                "dossier" => $dossier
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }    

    public function reouvrir($dossierId)
    {
        try {
            $dossier = $this->dossierService->reouvrir((int) $dossierId, 'systeme', null);
            
            return $this->json([
                "message" => "Dossier réouvert avec succès",
                "dossier" => $dossier
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }    

    public function archiver($dossierId)
    {
        try {
            $dossier = $this->dossierService->archiverDossier((int) $dossierId, 'systeme', null);
            
            return $this->json([
                "message" => "Dossier archivé avec succès",
                "dossier" => $dossier
            ]);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 
                         ($e instanceof \RuntimeException ? 409 : 500);
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }    

    public function parClient($clientId)
    {
        try {
            $dossiers = $this->dossierService->getDossiersByClient((int) $clientId);
            return $this->json($dossiers);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }    
    
    public function parAvocat($avocatId)
    {
        try {
            $dossiers = $this->dossierService->getDossiersByAvocat((int) $avocatId);
            return $this->json($dossiers);
        } catch (\Exception $e) {
            $statusCode = $e instanceof \InvalidArgumentException ? 400 : 404;
            return $this->json(["error" => $e->getMessage()], $statusCode);
        }
    }

    public function meilleurAvocat($dossierId)
    {
        try {
            $avocat = $this->dossierService->findBestAvocatForDossier((int) $dossierId);
            
            if ($avocat) {
                return $this->json([
                    "avocat" => $avocat,
                    "message" => "Meilleur avocat trouvé pour ce dossier"
                ]);
            } else {
                return $this->json([
                    "avocat" => null,
                    "message" => "Aucun avocat disponible pour ce dossier"
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function statistiques()
    {
        try {
            $stats = $this->dossierService->getGeneralStatistics();
            return $this->json($stats);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function peutCloturer($dossierId)
    {
        try {
            $canClose = $this->dossierService->canCloture((int) $dossierId);
            return $this->json([
                "peut_cloturer" => $canClose,
                "message" => $canClose ? "Le dossier peut être clôturé" : "Le dossier ne peut pas être clôturé (étapes non validées)"
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }
}