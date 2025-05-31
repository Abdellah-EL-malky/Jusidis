<?php
namespace App\Models;

use JsonSerializable;

abstract class Personne implements JsonSerializable
{
    protected $id;
    protected $nom;
    protected $email;
    protected $telephone;
    protected $adresse;
    protected $typePersonne;
    protected $createdAt;
    protected $updatedAt;

    public function __construct($id, $nom, $email, $telephone = null, $adresse = null, $typePersonne = null, $createdAt = null, $updatedAt = null)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->adresse = $adresse;
        $this->typePersonne = $typePersonne;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'type_personne' => $this->typePersonne,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function getAdresse()
    {
        return $this->adresse;
    }

    public function getTypePersonne()
    {
        return $this->typePersonne;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    public function setAdresse($adresse)
    {
        $this->adresse = $adresse;
    }

    public function setTypePersonne($typePersonne)
    {
        $this->typePersonne = $typePersonne;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }
}