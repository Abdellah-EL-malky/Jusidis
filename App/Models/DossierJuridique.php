<?php
namespace App\Models;

use JsonSerializable;

class DossierJuridique implements JsonSerializable
{
    private $id;
    private $numeroDossier;
    private $titre;
    private $description;
    private $typeDossierId;
    private $clientId;
    private $avocatId;
    private $statut;
    private $dateOuverture;
    private $dateCloture;
    private $priorite;
    private $toutesEtapesValidees;
    private $createdAt;
    private $updatedAt;

    private $client;
    private $avocat;
    private $typeDossier;
    private $etapes;

    public function __construct($id, $numeroDossier, $titre, $description = null, $typeDossierId = null, $clientId = null, $avocatId = null, $statut = 'ouvert', $dateOuverture = null, $dateCloture = null, $priorite = 'normale', $toutesEtapesValidees = false, $createdAt = null, $updatedAt = null)
    {
        $this->id = $id;
        $this->numeroDossier = $numeroDossier;
        $this->titre = $titre;
        $this->description = $description;
        $this->typeDossierId = $typeDossierId;
        $this->clientId = $clientId;
        $this->avocatId = $avocatId;
        $this->statut = $statut;
        $this->dateOuverture = $dateOuverture ?: date('Y-m-d');
        $this->dateCloture = $dateCloture;
        $this->priorite = $priorite;
        $this->toutesEtapesValidees = $toutesEtapesValidees;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->etapes = [];
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'numero_dossier' => $this->numeroDossier,
            'titre' => $this->titre,
            'description' => $this->description,
            'type_dossier_id' => $this->typeDossierId,
            'client_id' => $this->clientId,
            'avocat_id' => $this->avocatId,
            'statut' => $this->statut,
            'date_ouverture' => $this->dateOuverture,
            'date_cloture' => $this->dateCloture,
            'priorite' => $this->priorite,
            'toutes_etapes_validees' => $this->toutesEtapesValidees,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'peut_etre_cloture' => $this->peutEtreCloture()
        ];

        if ($this->client) {
            $data['client'] = $this->client;
        }
        if ($this->avocat) {
            $data['avocat'] = $this->avocat;
        }
        if ($this->typeDossier) {
            $data['type_dossier'] = $this->typeDossier;
        }
        if (!empty($this->etapes)) {
            $data['etapes'] = $this->etapes;
        }

        return $data;
    }

    // Getters
    public function getId() 
    {
         return $this->id; 
    }

    public function getNumeroDossier() 
    {
         return $this->numeroDossier; 
    }

    public function getTitre() 
    {
         return $this->titre; 
    }

    public function getDescription() 
    {
         return $this->description; 
    }

    public function getTypeDossierId() 
    {
         return $this->typeDossierId; 
    }

    public function getClientId() 
    {
         return $this->clientId; 
    }

    public function getAvocatId() 
    {
         return $this->avocatId; 
    }

    public function getStatut() 
    {
         return $this->statut; 
    }

    public function getDateOuverture() 
    {
         return $this->dateOuverture; 
    }

    public function getDateCloture() 
    {
         return $this->dateCloture; 
    }

    public function getPriorite() 
    {
         return $this->priorite; 
    }
    
    public function getToutesEtapesValidees() 
    {
         return $this->toutesEtapesValidees; 
    }
    
    public function getCreatedAt() 
    {
         return $this->createdAt; 
    }
    
    public function getUpdatedAt() 
    {
         return $this->updatedAt; 
    }
    
    public function getClient() 
    {
         return $this->client; 
    }
    
    public function getAvocat() 
    {
         return $this->avocat; 
    }
    
    public function getTypeDossier() 
    {
         return $this->typeDossier; 
    }
    
    public function getEtapes() 
    {
         return $this->etapes; 
    }

    // Setters
    public function setId($id) 
    {
         $this->id = $id; 
    }

    public function setNumeroDossier($numeroDossier) 
    {
         $this->numeroDossier = $numeroDossier; 
    }

    public function setTitre($titre) 
    {
         $this->titre = $titre; 
    }

    public function setDescription($description) 
    {
         $this->description = $description; 
    }

    public function setTypeDossierId($typeDossierId) 
    {
         $this->typeDossierId = $typeDossierId; 
    }

    public function setClientId($clientId) 
    {
         $this->clientId = $clientId; 
    }

    public function setAvocatId($avocatId) 
    {
         $this->avocatId = $avocatId; 
    }

    public function setStatut($statut) 
    {
         $this->statut = $statut; 
    }

    public function setDateOuverture($dateOuverture) 
    {
         $this->dateOuverture = $dateOuverture; 
    }

    public function setDateCloture($dateCloture) 
    {
         $this->dateCloture = $dateCloture; 
    }

    public function setPriorite($priorite) 
    {
         $this->priorite = $priorite; 
    }

    public function setToutesEtapesValidees($toutesEtapesValidees) 
    {
         $this->toutesEtapesValidees = $toutesEtapesValidees; 
    }

    public function setCreatedAt($createdAt) 
    {
         $this->createdAt = $createdAt; 
    }

    public function setUpdatedAt($updatedAt) 
    {
         $this->updatedAt = $updatedAt; 
    }

    public function setClient($client) 
    {
         $this->client = $client; 
    }

    public function setAvocat($avocat) 
    {
         $this->avocat = $avocat; 
    }

    public function setTypeDossier($typeDossier) 
    {
         $this->typeDossier = $typeDossier; 
    }

    public function setEtapes($etapes) 
    {
         $this->etapes = $etapes; 
    }

    // Méthodes
    public function estActif(): bool
    {
        return in_array($this->statut, ['ouvert', 'en_cours']);
    }

    public function estCloture(): bool
    {
        return $this->statut === 'cloture';
    }

    public function peutEtreCloture(): bool
    {
        return $this->toutesEtapesValidees && !$this->estCloture();
    }

    public function genererNumeroDossier(): string
    {
        $year = date('Y');
        $month = date('m');
        return "DOS{$year}{$month}" . str_pad($this->id ?? rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function calculerProgressionPourcentage(): float
    {
        if (empty($this->etapes)) {
            return 0.0;
        }

        $etapesObligatoires = array_filter($this->etapes, function($etape) {
            return $etape['obligatoire'] ?? true;
        });

        if (empty($etapesObligatoires)) {
            return 100.0;
        }

        $etapesValidees = array_filter($etapesObligatoires, function($etape) {
            return ($etape['statut'] ?? '') === 'validee';
        });

        return (count($etapesValidees) / count($etapesObligatoires)) * 100;
    }

    public function getStatutCouleur(): string
    {
        return match($this->statut) {
            'ouvert' => 'primary',
            'en_cours' => 'warning',
            'cloture' => 'success',
            'archive' => 'secondary',
            default => 'light'
        };
    }

    public function getPrioriteCouleur(): string
    {
        return match($this->priorite) {
            'basse' => 'success',
            'normale' => 'primary',
            'haute' => 'warning',
            'urgente' => 'danger',
            default => 'secondary'
        };
    }
}