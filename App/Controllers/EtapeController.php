<?php

namespace App\Controllers;

use Core\Contracts\ResourceController;
use Core\Controller;
use App\Repositories\EtapeRepository;
use Core\Decorators\Description;
use Core\Decorators\Route;
use Core\Router\RouteMethod;

class EtapeController extends Controller implements ResourceController
{
    private EtapeRepository $etapeRepository;

    public function __construct()
    {
        parent::__construct();
        $this->etapeRepository = new EtapeRepository();
    }

    public function index()
    {
        try {
            $params = $this->request->param();
            
            if (!empty($params['dossier_id'])) {
                $etapes = $this->etapeRepository->findByDossierId((int) $params['dossier_id']);
                return $this->json($etapes);
            }
            
            if (!empty($params['statut'])) {
                $etapes = $this->etapeRepository->findByStatut($params['statut']);
                return $this->json($etapes);
            }

            if (!empty($params['en_retard']) && $params['en_retard'] === 'true') {
                $etapes = $this->etapeRepository->findEtapesEnRetard();
                return $this->json($etapes);
            }

            return $this->json(["message" => "Veuillez spécifier un filtre (dossier_id, statut, ou en_retard=true)"]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $etape = $this->etapeRepository->findById((int) $id);
            return $this->json($etape);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->all();
            
            $requiredFields = ['dossier_id', 'nom'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->json(["error" => "Le champ '$field' est obligatoire."], 400);
                }
            }

            $etape = $this->etapeRepository->createEtape($data);
            return $this->json($etape, 201);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function update($id)
    {
        try {
            $data = $this->request->all();
            
            $allowedFields = ['nom', 'description', 'date_fin_prevue', 'ordre_execution'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            if (empty($updateData)) {
                return $this->json(["error" => "Aucun champ valide à mettre à jour."], 400);
            }

            $success = $this->etapeRepository->update($updateData, ['id' => (int) $id]);
            
            if ($success) {
                $etape = $this->etapeRepository->findById((int) $id);
                return $this->json($etape);
            } else {
                return $this->json(["error" => "Erreur lors de la mise à jour"], 500);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $etape = $this->etapeRepository->findById((int) $id);
            
            if ($etape->estValidee()) {
                return $this->json(["error" => "Impossible de supprimer une étape validée."], 409);
            }

            $success = $this->etapeRepository->delete(['id' => (int) $id]);
            
            if ($success) {
                return $this->json(["message" => "Étape supprimée avec succès"]);
            } else {
                return $this->json(["error" => "Erreur lors de la suppression"], 500);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function valider($etapeId)
    {
        try {
            $data = $this->request->all();
            
            if (empty($data['avocat_id'])) {
                return $this->json(["error" => "Le champ 'avocat_id' est obligatoire pour valider une étape."], 400);
            }

            $success = $this->etapeRepository->validerEtape((int) $etapeId, (int) $data['avocat_id']);
            
            if ($success) {
                $etape = $this->etapeRepository->findById((int) $etapeId);
                return $this->json([
                    "message" => "Étape validée avec succès",
                    "etape" => $etape
                ]);
            } else {
                return $this->json(["error" => "Erreur lors de la validation"], 500);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function demarrer($etapeId)
    {
        try {
            $success = $this->etapeRepository->demarrerEtape((int) $etapeId);
            
            if ($success) {
                $etape = $this->etapeRepository->findById((int) $etapeId);
                return $this->json([
                    "message" => "Étape démarrée avec succès",
                    "etape" => $etape
                ]);
            } else {
                return $this->json(["error" => "Erreur lors du démarrage"], 500);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function annuler($etapeId)
    {
        try {
            $success = $this->etapeRepository->annulerEtape((int) $etapeId);
            
            if ($success) {
                $etape = $this->etapeRepository->findById((int) $etapeId);
                return $this->json([
                    "message" => "Étape annulée avec succès",
                    "etape" => $etape
                ]);
            } else {
                return $this->json(["error" => "Erreur lors de l'annulation"], 500);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function statistiquesDossier($dossierId)
    {
        try {
            $stats = $this->etapeRepository->countByStatutForDossier((int) $dossierId);
            $pourcentage = $this->etapeRepository->calculerPourcentageAvancement((int) $dossierId);
            
            return $this->json([
                "dossier_id" => (int) $dossierId,
                "statistiques_etapes" => $stats,
                "pourcentage_avancement" => $pourcentage,
                "toutes_etapes_validees" => $this->etapeRepository->verifierToutesEtapesValidees((int) $dossierId)
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function reorganiser($dossierId)
    {
        try {
            $data = $this->request->all();
            
            if (empty($data['etape_ids']) || !is_array($data['etape_ids'])) {
                return $this->json(["error" => "Le champ 'etape_ids' doit être un tableau d'IDs d'étapes."], 400);
            }

            $success = $this->etapeRepository->reorderEtapes((int) $dossierId, $data['etape_ids']);
            
            if ($success) {
                $etapes = $this->etapeRepository->findByDossierId((int) $dossierId);
                return $this->json([
                    "message" => "Ordre des étapes réorganisé avec succès",
                    "etapes" => $etapes
                ]);
            } else {
                return $this->json(["error" => "Erreur lors de la réorganisation"], 500);
            }
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function dupliquer($dossierSourceId, $dossierDestinationId)
    {
        try {
            $etapesDupliquees = $this->etapeRepository->dupliquerEtapes(
                (int) $dossierSourceId, 
                (int) $dossierDestinationId
            );
            
            return $this->json([
                "message" => "Étapes dupliquées avec succès",
                "nombre_etapes" => count($etapesDupliquees),
                "etapes" => $etapesDupliquees
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 400);
        }
    }
}