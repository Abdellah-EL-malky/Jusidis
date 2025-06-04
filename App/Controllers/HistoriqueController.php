<?php

namespace App\Controllers;

use Core\Controller;
use App\Repositories\HistoriqueRepository;
use Core\Decorators\Description;
use Core\Decorators\Route;
use Core\Router\RouteMethod;

class HistoriqueController extends Controller
{
    private HistoriqueRepository $historiqueRepository;

    public function __construct()
    {
        parent::__construct();
        $this->historiqueRepository = new HistoriqueRepository();
    }

    public function index()
    {
        try {
            $params = $this->request->param();
            $filters = [];

            $allowedFilters = ['dossier_id', 'type_action', 'effectue_par_type', 'date_debut', 'date_fin', 'description', 'limit'];
            foreach ($allowedFilters as $filter) {
                if (!empty($params[$filter])) {
                    $filters[$filter] = $params[$filter];
                }
            }

            if (!empty($params['recent'])) {
                $heures = !empty($params['heures']) ? (int) $params['heures'] : 24;
                $historique = $this->historiqueRepository->findRecent($heures);
                return $this->json($historique);
            }

            if (!empty($filters)) {
                $historique = $this->historiqueRepository->search($filters);
                return $this->json($historique);
            }

            $filters['limit'] = 100;
            $historique = $this->historiqueRepository->search($filters);
            return $this->json($historique);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $historique = $this->historiqueRepository->findById((int) $id);
            return $this->json($historique);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function parDossier($dossierId)
    {
        try {
            $historique = $this->historiqueRepository->findByDossierId((int) $dossierId);
            return $this->json($historique);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 404);
        }
    }

    public function parType($typeAction)
    {
        try {
            $historique = $this->historiqueRepository->findByTypeAction($typeAction);
            return $this->json($historique);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function parUtilisateur($utilisateurType, $utilisateurId)
    {
        try {
            $typesValides = ['client', 'avocat', 'systeme'];
            if (!in_array($utilisateurType, $typesValides)) {
                return $this->json(["error" => "Type d'utilisateur non valide. Types autorisés : " . implode(', ', $typesValides)], 400);
            }

            $historique = $this->historiqueRepository->findByUtilisateur($utilisateurType, (int) $utilisateurId);
            return $this->json($historique);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function recent()
    {
        try {
            $params = $this->request->param();
            $heures = !empty($params['heures']) ? (int) $params['heures'] : 24;
            
            if ($heures < 1 || $heures > 8760) { 
                return $this->json(["error" => "Le paramètre 'heures' doit être entre 1 et 8760."], 400);
            }

            $historique = $this->historiqueRepository->findRecent($heures);
            return $this->json([
                "periode" => "{$heures} dernières heures",
                "nombre_entrees" => count($historique),
                "historique" => $historique
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function statistiques()
    {
        try {
            $params = $this->request->param();
            $jours = !empty($params['jours']) ? (int) $params['jours'] : 30;
            
            if ($jours < 1 || $jours > 365) {
                return $this->json(["error" => "Le paramètre 'jours' doit être entre 1 et 365."], 400);
            }

            $stats = $this->historiqueRepository->getStatistiquesActions($jours);
            
            $statsGroupees = [];
            foreach ($stats as $stat) {
                $type = $stat['type_action'];
                if (!isset($statsGroupees[$type])) {
                    $statsGroupees[$type] = [
                        'type_action' => $type,
                        'total' => 0,
                        'par_jour' => []
                    ];
                }
                $statsGroupees[$type]['total'] += $stat['count'];
                $statsGroupees[$type]['par_jour'][] = [
                    'date' => $stat['date_action'],
                    'count' => $stat['count']
                ];
            }

            return $this->json([
                "periode" => "{$jours} derniers jours",
                "statistiques" => array_values($statsGroupees)
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function compterParDossier($dossierId)
    {
        try {
            $count = $this->historiqueRepository->countActionsByDossier((int) $dossierId);
            return $this->json([
                "dossier_id" => (int) $dossierId,
                "nombre_actions" => $count
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function recherche()
    {
        try {
            $data = $this->request->all();
            
            $allowedFilters = [
                'dossier_id', 'type_action', 'effectue_par_type', 
                'date_debut', 'date_fin', 'description', 'limit'
            ];
            
            $filters = [];
            foreach ($allowedFilters as $filter) {
                if (!empty($data[$filter])) {
                    $filters[$filter] = $data[$filter];
                }
            }

            if (empty($filters)) {
                return $this->json(["error" => "Au moins un critère de recherche est requis."], 400);
            }

            $historique = $this->historiqueRepository->search($filters);
            return $this->json([
                "criteres" => $filters,
                "nombre_resultats" => count($historique),
                "historique" => $historique
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 400);
        }
    }

    public function purger()
    {
        try {
            $data = $this->request->all();
            $joursAConserver = !empty($data['jours_a_conserver']) ? (int) $data['jours_a_conserver'] : 365;
            
            if ($joursAConserver < 30) {
                return $this->json(["error" => "Il faut conserver au moins 30 jours d'historique."], 400);
            }

            $nombreSupprime = $this->historiqueRepository->purgerAncien($joursAConserver);
            
            return $this->json([
                "message" => "Purge effectuée avec succès",
                "jours_conserves" => $joursAConserver,
                "entrees_supprimees" => $nombreSupprime
            ]);
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }

    public function export()
    {
        try {
            $params = $this->request->param();
            $filters = [];

            $allowedFilters = ['dossier_id', 'type_action', 'date_debut', 'date_fin'];
            foreach ($allowedFilters as $filter) {
                if (!empty($params[$filter])) {
                    $filters[$filter] = $params[$filter];
                }
            }

            $historique = $this->historiqueRepository->search($filters);
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="historique_' . date('Y-m-d_H-i-s') . '.json"');
            
            echo json_encode([
                "export_date" => date('Y-m-d H:i:s'),
                "filters_applied" => $filters,
                "total_entries" => count($historique),
                "historique" => $historique
            ], JSON_PRETTY_PRINT);
            
            exit;
        } catch (\Exception $e) {
            return $this->json(["error" => $e->getMessage()], 500);
        }
    }
}