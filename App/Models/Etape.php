<?php
namespace App\Models;

use JsonSerializable;

class Etape implements JsonSerializable
{
    private $id;
    private $dossierId;
    private $nom;
    private $description;
    private $ordreExecution;
    private $statut;
    private $dateDebut;
    private $dateFinPrevue;
    private $dateValidation;
    private $valideeParAvocatId;
    private $obligatoire;
    private $createdAt;

    private $dossier;
    private $validateur;

    public function __construct($id, $dossierId, $nom, $description = null, $ordreExecution = 1, $statut = 'en_attente', $dateDebut = null, $dateFinPrevue = null, $dateValidation = null, $valideeParAvocatId = null, $obligatoire = true, $createdAt = null)
    {
        $this->id = $id;
        $this->dossierId = $dossierId;
        $this->nom = $nom;
        $this->description = $description;
        $this->ordreExecution = $ordreExecution;
        $this->statut = $statut;
        $this->dateDebut = $dateDebut;
        $this->dateFinPrevue = $dateFinPrevue;
        $this->dateValidation = $dateValidation;
        $this->valideeParAvocatId = $valideeParAvocatId;
        $this->obligatoire = $obligatoire;
        $this->createdAt = $createdAt;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'dossier_id' => $this->dossierId,
            'nom' => $this->nom,
            'description' => $this->description,
            'ordre_execution' => $this->ordreExecution,
            'statut' => $this->statut,
            'date_debut' => $this->dateDebut,
            'date_fin_prevue' => $this->dateFinPrevue,
            'date_validation' => $this->dateValidation,
            'validee_par_avocat_id' => $this->valideeParAvocatId,
            'obligatoire' => $this->obligatoire,
            'created_at' => $this->createdAt,
            'est_validee' => $this->estValidee(),
            'est_en_retard' => $this->estEnRetard(),
            'statut_couleur' => $this->getStatutCouleur()
        ];

        if ($this->dossier) {
            $data['dossier'] = $this->dossier;
        }
        if ($this->validateur) {
            $data['validateur'] = $this->validateur;
        }

        return $data;
    }

    // Getters
    public function getId() 
    {
         return $this->id; 
    }

    public function getDossierId() 
    {
         return $this->dossierId; 
    }

    public function getNom() 
    {
         return $this->nom; 
    }

    public function getDescription() 
    {
         return $this->description; 
    }

    public function getOrdreExecution() 
    {
         return $this->ordreExecution; 
    }

    public function getStatut() 
    {
         return $this->statut; 
    }

    public function getDateDebut() 
    {
         return $this->dateDebut; 
    }

    public function getDateFinPrevue() 
    {
         return $this->dateFinPrevue; 
    }

    public function getDateValidation() 
    {
         return $this->dateValidation; 
    }

    public function getValideeParAvocatId() 
    {
         return $this->valideeParAvocatId; 
    }

    public function getObligatoire() 
    {
         return $this->obligatoire; 
    }

    public function getCreatedAt() 
    {
         return $this->createdAt; 
    }

    public function getDossier() 
    {
         return $this->dossier; 
    }

    public function getValidateur() 
    {
         return $this->validateur; 
    }
    
    // Setters
    public function setId($id) 
    {
         $this->id = $id; 
    }

    public function setDossierId($dossierId) 
    {
         $this->dossierId = $dossierId; 
    }

    public function setNom($nom) 
    {
         $this->nom = $nom; 
    }

    public function setDescription($description) 
    {
         $this->description = $description; 
    }

    public function setOrdreExecution($ordreExecution) 
    {
         $this->ordreExecution = $ordreExecution; 
    }

    public function setStatut($statut) 
    {
         $this->statut = $statut; 
    }

    public function setDateDebut($dateDebut) 
    {
         $this->dateDebut = $dateDebut; 
    }

    public function setDateFinPrevue($dateFinPrevue) 
    {
         $this->dateFinPrevue = $dateFinPrevue; 
    }

    public function setDateValidation($dateValidation) 
    {
         $this->dateValidation = $dateValidation; 
    }

    public function setValideeParAvocatId($valideeParAvocatId) 
    {
         $this->valideeParAvocatId = $valideeParAvocatId; 
    }

    public function setObligatoire($obligatoire) 
    {
         $this->obligatoire = $obligatoire; 
    }

    public function setCreatedAt($createdAt) 
    {
         $this->createdAt = $createdAt; 
    }

    public function setDossier($dossier) 
    {
         $this->dossier = $dossier; 
    }

    public function setValidateur($validateur) 
    {
         $this->validateur = $validateur; 
    }

    // Méthodes
    public function estValidee(): bool
    {
        return $this->statut === 'validee';
    }

    public function estEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function estAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }

    public function estObligatoire(): bool
    {
        return $this->obligatoire;
    }

    public function estEnRetard(): bool
    {
        if (!$this->dateFinPrevue || $this->estValidee()) {
            return false;
        }

        $today = new \DateTime();
        $dateFinPrevue = new \DateTime($this->dateFinPrevue);
        
        return $today > $dateFinPrevue;
    }

    public function valider($avocatId = null): bool
    {
        if (!$this->estValidee()) {
            $this->statut = 'validee';
            $this->dateValidation = date('Y-m-d H:i:s');
            $this->valideeParAvocatId = $avocatId;
            return true;
        }
        return false;
    }

    public function demarrer(): bool
    {
        if ($this->estEnAttente()) {
            $this->statut = 'en_cours';
            $this->dateDebut = date('Y-m-d');
            return true;
        }
        return false;
    }

    public function annuler(): bool
    {
        if (!$this->estValidee()) {
            $this->statut = 'annulee';
            return true;
        }
        return false;
    }

    public function calculerDureeEstimee(): ?int
    {
        if (!$this->dateDebut || !$this->dateFinPrevue) {
            return null;
        }

        $debut = new \DateTime($this->dateDebut);
        $fin = new \DateTime($this->dateFinPrevue);
        
        return $debut->diff($fin)->days;
    }

    public function calculerDureeReelle(): ?int
    {
        if (!$this->dateDebut || !$this->dateValidation) {
            return null;
        }

        $debut = new \DateTime($this->dateDebut);
        $validation = new \DateTime($this->dateValidation);
        
        return $debut->diff($validation)->days;
    }

    public function getStatutCouleur(): string
    {
        return match($this->statut) {
            'en_attente' => 'secondary',
            'en_cours' => 'warning',
            'validee' => 'success',
            'annulee' => 'danger',
            default => 'light'
        };
    }
}