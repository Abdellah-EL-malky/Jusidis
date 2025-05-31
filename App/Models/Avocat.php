<?php
namespace App\Models;

class Avocat extends Personne
{
    private $avocatId;
    private $personneId;
    private $numeroBarreau;
    private $specialisation;
    private $certifie;
    private $maxDossiersActifs;
    private $dossiersActifsActuels;
    private $dateInscriptionBarreau;

    public function __construct($id, $nom, $email, $telephone = null, $adresse = null, $createdAt = null, $updatedAt = null, $avocatId = null, $personneId = null, $numeroBarreau = null, $specialisation = null, $certifie = false, $maxDossiersActifs = 10, $dossiersActifsActuels = 0, $dateInscriptionBarreau = null)
    {
        parent::__construct($id, $nom, $email, $telephone, $adresse, 'avocat', $createdAt, $updatedAt);
        $this->avocatId = $avocatId;
        $this->personneId = $personneId;
        $this->numeroBarreau = $numeroBarreau;
        $this->specialisation = $specialisation;
        $this->certifie = $certifie;
        $this->maxDossiersActifs = $maxDossiersActifs;
        $this->dossiersActifsActuels = $dossiersActifsActuels;
        $this->dateInscriptionBarreau = $dateInscriptionBarreau;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'avocat_id' => $this->avocatId,
            'personne_id' => $this->personneId,
            'numero_barreau' => $this->numeroBarreau,
            'specialisation' => $this->specialisation,
            'certifie' => $this->certifie,
            'max_dossiers_actifs' => $this->maxDossiersActifs,
            'dossiers_actifs_actuels' => $this->dossiersActifsActuels,
            'date_inscription_barreau' => $this->dateInscriptionBarreau,
            'disponible' => $this->estDisponible()
        ]);
    }

    // Getters
    public function getAvocatId()
    {
        return $this->avocatId;
    }

    public function getPersonneId()
    {
        return $this->personneId;
    }

    public function getNumeroBarreau()
    {
        return $this->numeroBarreau;
    }

    public function getSpecialisation()
    {
        return $this->specialisation;
    }

    public function isCertifie(): bool
    {
        return $this->certifie;
    }

    public function getMaxDossiersActifs()
    {
        return $this->maxDossiersActifs;
    }

    public function getDossiersActifsActuels()
    {
        return $this->dossiersActifsActuels;
    }

    public function getDateInscriptionBarreau()
    {
        return $this->dateInscriptionBarreau;
    }

    // Setters
    public function setAvocatId($avocatId)
    {
        $this->avocatId = $avocatId;
    }

    public function setPersonneId($personneId)
    {
        $this->personneId = $personneId;
    }

    public function setNumeroBarreau($numeroBarreau)
    {
        $this->numeroBarreau = $numeroBarreau;
    }

    public function setSpecialisation($specialisation)
    {
        $this->specialisation = $specialisation;
    }

    public function setCertifie($certifie)
    {
        $this->certifie = $certifie;
    }

    public function setMaxDossiersActifs($maxDossiersActifs)
    {
        $this->maxDossiersActifs = $maxDossiersActifs;
    }

    public function setDossiersActifsActuels($dossiersActifsActuels)
    {
        $this->dossiersActifsActuels = $dossiersActifsActuels;
    }

    public function setDateInscriptionBarreau($dateInscriptionBarreau)
    {
        $this->dateInscriptionBarreau = $dateInscriptionBarreau;
    }

    // Méthodes
    public function estDisponible(): bool
    {
        return $this->dossiersActifsActuels < $this->maxDossiersActifs;
    }

    public function peutTraiterDossierCertifie(): bool
    {
        return $this->certifie;
    }

    public function ajouterDossierActif(): bool
    {
        if ($this->estDisponible()) {
            $this->dossiersActifsActuels++;
            return true;
        }
        return false;
    }

    public function retirerDossierActif(): bool
    {
        if ($this->dossiersActifsActuels > 0) {
            $this->dossiersActifsActuels--;
            return true;
        }
        return false;
    }

    public function getNombreEmplacementsDisponibles(): int
    {
        return max(0, $this->maxDossiersActifs - $this->dossiersActifsActuels);
    }
}