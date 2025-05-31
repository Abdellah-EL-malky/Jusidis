<?php
namespace App\Models;

class Client extends Personne
{
    private $clientId;
    private $personneId;
    private $numeroClient;
    private $dateInscription;
    private $statut;

    public function __construct($id, $nom, $email, $telephone = null, $adresse = null, $createdAt = null, $updatedAt = null, $clientId = null, $personneId = null, $numeroClient = null, $dateInscription = null, $statut = 'actif')
    {
        parent::__construct($id, $nom, $email, $telephone, $adresse, 'client', $createdAt, $updatedAt);
        $this->clientId = $clientId;
        $this->personneId = $personneId;
        $this->numeroClient = $numeroClient;
        $this->dateInscription = $dateInscription;
        $this->statut = $statut;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'client_id' => $this->clientId,
            'personne_id' => $this->personneId,
            'numero_client' => $this->numeroClient,
            'date_inscription' => $this->dateInscription,
            'statut' => $this->statut
        ]);
    }

    // Getters
    public function getClientId()
    {
        return $this->clientId;
    }

    public function getPersonneId()
    {
        return $this->personneId;
    }

    public function getNumeroClient()
    {
        return $this->numeroClient;
    }

    public function getDateInscription()
    {
        return $this->dateInscription;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    // Setters
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function setPersonneId($personneId)
    {
        $this->personneId = $personneId;
    }

    public function setNumeroClient($numeroClient)
    {
        $this->numeroClient = $numeroClient;
    }

    public function setDateInscription($dateInscription)
    {
        $this->dateInscription = $dateInscription;
    }

    public function setStatut($statut)
    {
        $this->statut = $statut;
    }

    // Méthodes
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function genererNumeroClient(): string
    {
        return 'CL' . date('Y') . str_pad($this->clientId ?? rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}