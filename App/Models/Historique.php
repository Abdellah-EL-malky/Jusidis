<?php
namespace App\Models;

use JsonSerializable;

class Historique implements JsonSerializable
{
    private $id;
    private $dossierId;
    private $typeAction;
    private $description;
    private $effectueParType;
    private $effectueParId;
    private $ancienneValeur;
    private $nouvelleValeur;
    private $dateAction;

    private $dossier;
    private $utilisateur;

    public function __construct($id, $dossierId, $typeAction, $description, $effectueParType, $effectueParId = null, $ancienneValeur = null, $nouvelleValeur = null, $dateAction = null)
    {
        $this->id = $id;
        $this->dossierId = $dossierId;
        $this->typeAction = $typeAction;
        $this->description = $description;
        $this->effectueParType = $effectueParType;
        $this->effectueParId = $effectueParId;
        $this->ancienneValeur = $ancienneValeur;
        $this->nouvelleValeur = $nouvelleValeur;
        $this->dateAction = $dateAction ?: date('Y-m-d H:i:s');
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'dossier_id' => $this->dossierId,
            'type_action' => $this->typeAction,
            'description' => $this->description,
            'effectue_par_type' => $this->effectueParType,
            'effectue_par_id' => $this->effectueParId,
            'ancienne_valeur' => $this->ancienneValeur,
            'nouvelle_valeur' => $this->nouvelleValeur,
            'date_action' => $this->dateAction,
            'date_action_formatee' => $this->getDateActionFormatee(),
            'icone_action' => $this->getIconeAction(),
            'couleur_action' => $this->getCouleurAction()
        ];

        if ($this->dossier) {
            $data['dossier'] = $this->dossier;
        }
        if ($this->utilisateur) {
            $data['utilisateur'] = $this->utilisateur;
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

    public function getTypeAction() 
    {
         return $this->typeAction; 
    }

    public function getDescription() 
    {
         return $this->description; 
    }

    public function getEffectueParType() 
    {
         return $this->effectueParType; 
    }

    public function getEffectueParId() 
    {
         return $this->effectueParId; 
    }

    public function getAncienneValeur() 
    {
         return $this->ancienneValeur; 
    }

    public function getNouvelleValeur() 
    {
         return $this->nouvelleValeur; 
    }

    public function getDateAction() 
    {
         return $this->dateAction; 
    }

    public function getDossier() 
    {
         return $this->dossier; 
    }

    public function getUtilisateur() 
    {
         return $this->utilisateur; 
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

    public function setTypeAction($typeAction) 
    {
         $this->typeAction = $typeAction; 
    }

    public function setDescription($description) 
    {
         $this->description = $description; 
    }

    public function setEffectueParType($effectueParType) 
    {
         $this->effectueParType = $effectueParType; 
    }

    public function setEffectueParId($effectueParId) 
    {
         $this->effectueParId = $effectueParId; 
    }

    public function setAncienneValeur($ancienneValeur) 
    {
         $this->ancienneValeur = $ancienneValeur; 
    }

    public function setNouvelleValeur($nouvelleValeur) 
    {
         $this->nouvelleValeur = $nouvelleValeur; 
    }

    public function setDateAction($dateAction) 
    {
         $this->dateAction = $dateAction; 
    }

    public function setDossier($dossier) 
    {
         $this->dossier = $dossier; 
    }

    public function setUtilisateur($utilisateur) 
    {
         $this->utilisateur = $utilisateur; 
    }

    // Méthode
    public function getDateActionFormatee(): string
    {
        if (!$this->dateAction) {
            return '';
        }

        $date = new \DateTime($this->dateAction);
        return $date->format('d/m/Y à H:i');
    }

    public function getIconeAction(): string
    {
        return match($this->typeAction) {
            'creation' => 'fas fa-plus',
            'changement_avocat' => 'fas fa-user-tie',
            'ajout_document' => 'fas fa-file-plus',
            'validation_etape' => 'fas fa-check',
            'changement_statut' => 'fas fa-exchange-alt',
            'cloture' => 'fas fa-lock',
            default => 'fas fa-info'
        };
    }

    public function getCouleurAction(): string
    {
        return match($this->typeAction) {
            'creation' => 'success',
            'changement_avocat' => 'warning',
            'ajout_document' => 'info',
            'validation_etape' => 'success',
            'changement_statut' => 'primary',
            'cloture' => 'secondary',
            default => 'light'
        };
    }

    public function getMessageFormate(): string
    {
        $message = $this->description;
        
        if ($this->ancienneValeur && $this->nouvelleValeur) {
            $message .= " (de '{$this->ancienneValeur}' vers '{$this->nouvelleValeur}')";
        } elseif ($this->nouvelleValeur) {
            $message .= " : {$this->nouvelleValeur}";
        }

        return $message;
    }

    public static function creerEntreeCreation($dossierId, $effectueParType, $effectueParId, $titreDossier): self
    {
        return new self(
            null,
            $dossierId,
            'creation',
            "Création du dossier '$titreDossier'",
            $effectueParType,
            $effectueParId
        );
    }

    public static function creerEntreeChangementAvocat($dossierId, $effectueParType, $effectueParId, $ancienAvocat, $nouveauAvocat): self
    {
        return new self(
            null,
            $dossierId,
            'changement_avocat',
            "Changement d'avocat assigné",
            $effectueParType,
            $effectueParId,
            $ancienAvocat,
            $nouveauAvocat
        );
    }

    public static function creerEntreeAjoutDocument($dossierId, $effectueParType, $effectueParId, $nomDocument): self
    {
        return new self(
            null,
            $dossierId,
            'ajout_document',
            "Ajout du document '$nomDocument'",
            $effectueParType,
            $effectueParId,
            null,
            $nomDocument
        );
    }

    public static function creerEntreeValidationEtape($dossierId, $effectueParType, $effectueParId, $nomEtape): self
    {
        return new self(
            null,
            $dossierId,
            'validation_etape',
            "Validation de l'étape '$nomEtape'",
            $effectueParType,
            $effectueParId,
            'en_attente',
            'validee'
        );
    }

    public static function creerEntreeChangementStatut($dossierId, $effectueParType, $effectueParId, $ancienStatut, $nouveauStatut): self
    {
        return new self(
            null,
            $dossierId,
            'changement_statut',
            "Changement du statut du dossier",
            $effectueParType,
            $effectueParId,
            $ancienStatut,
            $nouveauStatut
        );
    }

    public static function creerEntreeCloture($dossierId, $effectueParType, $effectueParId): self
    {
        return new self(
            null,
            $dossierId,
            'cloture',
            "Clôture définitive du dossier",
            $effectueParType,
            $effectueParId
        );
    }
}