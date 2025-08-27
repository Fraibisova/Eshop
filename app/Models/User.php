<?php

namespace App\Models;

class User
{
    private int $id;
    private string $name;
    private string $surname;
    private string $email;
    private string $phone;
    private string $countryCode;
    private string $street;
    private string $houseNumber;
    private string $city;
    private string $zipcode;
    private string $country;
    private string $password;
    private int $role;
    private \DateTime $createdAt;
    private \DateTime $updatedAt;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $data): void
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->name = $data['name'] ?? '';
        $this->surname = $data['surname'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->countryCode = $data['country_code'] ?? '+420';
        $this->street = $data['street'] ?? '';
        $this->houseNumber = $data['house_number'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->zipcode = $data['zipcode'] ?? '';
        $this->country = $data['country'] ?? 'Česká republika';
        $this->password = $data['password'] ?? '';
        $this->role = (int)($data['role_level'] ?? $data['role'] ?? 0);
        
        if (isset($data['created_at'])) {
            $this->createdAt = new \DateTime($data['created_at']);
        }
        if (isset($data['updated_at'])) {
            $this->updatedAt = new \DateTime($data['updated_at']);
        }
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSurname(): string { return $this->surname; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getCountryCode(): string { return $this->countryCode; }
    public function getStreet(): string { return $this->street; }
    public function getHouseNumber(): string { return $this->houseNumber; }
    public function getCity(): string { return $this->city; }
    public function getZipcode(): string { return $this->zipcode; }
    public function getCountry(): string { return $this->country; }
    public function getPassword(): string { return $this->password; }
    public function getRole(): int { return $this->role; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt ?? null; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt ?? null; }

    public function setId(int $id): void { $this->id = $id; }
    public function setName(string $name): void { $this->name = $name; }
    public function setSurname(string $surname): void { $this->surname = $surname; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setPhone(string $phone): void { $this->phone = $phone; }
    public function setCountryCode(string $countryCode): void { $this->countryCode = $countryCode; }
    public function setStreet(string $street): void { $this->street = $street; }
    public function setHouseNumber(string $houseNumber): void { $this->houseNumber = $houseNumber; }
    public function setCity(string $city): void { $this->city = $city; }
    public function setZipcode(string $zipcode): void { $this->zipcode = $zipcode; }
    public function setCountry(string $country): void { $this->country = $country; }
    public function setRole(int $role): void { $this->role = $role; }
    public function setPassword(string $password): void { $this->password = password_hash($password, PASSWORD_BCRYPT); }

    public function getFullName(): string
    {
        return trim($this->name . ' ' . $this->surname);
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->houseNumber,
            $this->city,
            $this->zipcode,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->countryCode,
            'street' => $this->street,
            'house_number' => $this->houseNumber,
            'city' => $this->city,
            'zipcode' => $this->zipcode,
            'country' => $this->country,
            'full_name' => $this->getFullName(),
            'full_address' => $this->getFullAddress()
        ];
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function hasCompleteProfile(): bool
    {
        return !empty($this->name) && 
               !empty($this->surname) && 
               !empty($this->email);
    }

    public function hasCompleteBillingAddress(): bool
    {
        return !empty($this->street) && 
               !empty($this->houseNumber) && 
               !empty($this->city) && 
               !empty($this->zipcode) && 
               !empty($this->country);
    }
}