<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Données saisies dans le formulaire admin "créer/modifier un médecin".
 *
 * @phpstan-type SpecialtyId int
 */
final class DoctorFormData
{
    /**
     * @param list<SpecialtyId> $specialtyIds
     * @param list<string>      $languages
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone,
        public string $slug,
        public ?string $rpps,
        public ?string $bio,
        public int $price,
        public bool $acceptVisio,
        public array $languages,
        public array $specialtyIds,
        public string $street,
        public string $city,
        public string $postalCode,
    ) {}
}
