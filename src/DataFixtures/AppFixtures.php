<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Patient;
use App\Entity\Slot;
use App\Entity\Specialty;
use App\Entity\User;
use App\Enum\AppointmentMode;
use App\Enum\Gender;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Seed démo DocConnect — contexte tunisien.
 *
 * Volume : 6 spécialités, 8 médecins, 5 patients, slots sur 2 semaines,
 * ~20 RDV répartis sur PENDING/CONFIRMED/CANCELLED/DONE.
 *
 * Note Firebase : les `firebase_uid` sont factices (préfixe `fixture-`).
 * Ces comptes ne peuvent pas se connecter via Firebase Auth réel ;
 * ils servent uniquement à peupler la DB pour visualiser l'app.
 */
final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $specialties = $this->loadSpecialties($manager);
        $doctors = $this->loadDoctors($manager, $specialties);
        $patients = $this->loadPatients($manager);

        $this->loadSlotsAndAppointments($manager, $doctors, $patients);

        $manager->flush();
    }

    /** @return array<string, Specialty> */
    private function loadSpecialties(ObjectManager $manager): array
    {
        $entries = [
            ['Médecine générale', 'medecine-generale'],
            ['Dermatologie', 'dermatologie'],
            ['Pédiatrie', 'pediatrie'],
            ['Cardiologie', 'cardiologie'],
            ['Gynécologie', 'gynecologie'],
            ['Ophtalmologie', 'ophtalmologie'],
        ];

        $map = [];
        foreach ($entries as [$label, $slug]) {
            $specialty = new Specialty($label, $slug);
            $manager->persist($specialty);
            $map[$slug] = $specialty;
        }
        return $map;
    }

    /**
     * @param array<string, Specialty> $specialties
     * @return list<Doctor>
     */
    private function loadDoctors(ObjectManager $manager, array $specialties): array
    {
        $entries = [
            ['Aymen', 'Ben Ali', 'dr-aymen-ben-ali', ['medecine-generale'], 50, 'Tunis', '1001', '12 av. Habib Bourguiba'],
            ['Sonia', 'Trabelsi', 'dr-sonia-trabelsi', ['dermatologie'], 70, 'Sousse', '4000', '8 av. de la République'],
            ['Mehdi', 'Khaldi', 'dr-mehdi-khaldi', ['medecine-generale'], 50, 'Sfax', '3000', '24 rue Hédi Chaker'],
            ['Sarra', 'Bouazizi', 'dr-sarra-bouazizi', ['pediatrie'], 60, 'Tunis', '1082', '5 rue de Carthage'],
            ['Karim', 'Mejri', 'dr-karim-mejri', ['cardiologie'], 90, 'Tunis', '1002', '17 av. Mohamed V'],
            ['Yasmine', 'Jendoubi', 'dr-yasmine-jendoubi', ['gynecologie'], 80, 'Sousse', '4002', '3 rue Khaled Ibn Walid'],
            ['Walid', 'Sassi', 'dr-walid-sassi', ['ophtalmologie'], 75, 'Sfax', '3027', '11 av. Majida Boulila'],
            ['Houda', 'Belhaj', 'dr-houda-belhaj', ['medecine-generale', 'pediatrie'], 55, 'Tunis', '1004', '42 rue Ibn Khaldoun'],
        ];

        $doctors = [];
        foreach ($entries as [$firstName, $lastName, $slug, $specialtySlugs, $price, $city, $postal, $street]) {
            $user = new User(
                firebaseUid: 'fixture-' . $slug,
                email: $slug . '@docconnect.demo',
                firstName: $firstName,
                lastName: $lastName,
            );
            $user->setRole(UserRole::DOCTOR);
            $user->setPhone('+216 ' . random_int(20000000, 99999999));
            $manager->persist($user);

            $doctor = new Doctor($user, $slug, $price);
            $doctor->setBio($this->buildBio($specialtySlugs[0]));
            $doctor->setLanguages(['ar', 'fr', random_int(0, 1) === 0 ? 'en' : 'it']);
            $doctor->setRpps((string) random_int(10000000000, 99999999999));
            $doctor->setAcceptVisio(true);

            foreach ($specialtySlugs as $slugSpec) {
                $doctor->addSpecialty($specialties[$slugSpec]);
            }

            $address = new Address($street, $city, $postal);
            $doctor->addAddress($address);

            $manager->persist($doctor);
            $doctors[] = $doctor;
        }

        return $doctors;
    }

    /** @return list<Patient> */
    private function loadPatients(ObjectManager $manager): array
    {
        $entries = [
            ['Sarra', 'Bouazizi', 'sarra.bouazizi@example.tn', '1990-03-12', Gender::FEMALE],
            ['Mohamed-Ali', 'Saidi', 'mohamed.saidi@example.tn', '1968-11-05', Gender::MALE],
            ['Ines', 'Hamdi', 'ines.hamdi@example.tn', '1999-08-22', Gender::FEMALE],
            ['Tarek', 'Nasri', 'tarek.nasri@example.tn', '1982-01-30', Gender::MALE],
            ['Amal', 'Gharbi', 'amal.gharbi@example.tn', '1995-07-14', Gender::FEMALE],
        ];

        $patients = [];
        foreach ($entries as [$firstName, $lastName, $email, $birthdate, $gender]) {
            $slug = strtolower(str_replace([' ', '-'], '', $firstName . $lastName));
            $user = new User(
                firebaseUid: 'fixture-patient-' . $slug,
                email: $email,
                firstName: $firstName,
                lastName: $lastName,
            );
            $user->setRole(UserRole::PATIENT);
            $user->setPhone('+216 ' . random_int(20000000, 99999999));
            $manager->persist($user);

            $patient = new Patient(
                user: $user,
                birthdate: new \DateTimeImmutable($birthdate),
                gender: $gender,
            );
            $manager->persist($patient);
            $patients[] = $patient;
        }

        return $patients;
    }

    /**
     * @param list<Doctor> $doctors
     * @param list<Patient> $patients
     */
    private function loadSlotsAndAppointments(ObjectManager $manager, array $doctors, array $patients): void
    {
        $today = new \DateTimeImmutable('today');
        $weekStart = $today->modify('monday this week');

        $times = ['09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00', '15:30', '16:00'];
        $durationMin = 20;
        $appointmentsCreated = 0;

        // Fenêtre : 8 semaines (~2 mois), lun-sam. Génère ~2 400 slots/médecin.
        $weeks = 8;
        $totalDays = $weeks * 7;

        foreach ($doctors as $doctor) {
            for ($dayOffset = 0; $dayOffset < $totalDays; $dayOffset++) {
                $day = $weekStart->modify('+' . $dayOffset . ' days');
                if ((int) $day->format('N') === 7) {
                    continue; // pas de consultation le dimanche
                }

                foreach ($times as $time) {
                    [$h, $m] = explode(':', $time);
                    $startAt = $day->setTime((int) $h, (int) $m);
                    $endAt = $startAt->modify('+' . $durationMin . ' minutes');

                    $mode = random_int(1, 10) <= 3 ? AppointmentMode::VIDEO : AppointmentMode::PHYSICAL;
                    $slot = new Slot($doctor, $startAt, $endAt, $mode);
                    $manager->persist($slot);

                    if (random_int(1, 100) <= 25 && $appointmentsCreated < 20) {
                        $patient = $patients[array_rand($patients)];
                        $appointment = new Appointment($slot, $patient);
                        $appointment->setMotif($this->randomMotif());

                        $r = random_int(1, 100);
                        if ($startAt < $today) {
                            if ($r <= 80) {
                                $appointment->confirm();
                                $appointment->markDone();
                                $slot->markBooked();
                            } else {
                                $appointment->cancel();
                            }
                        } else {
                            if ($r <= 50) {
                                $appointment->confirm();
                                $slot->markBooked();
                            } elseif ($r <= 75) {
                                $slot->markBooked();
                            } elseif ($r <= 90) {
                                $appointment->cancel();
                            } else {
                                $appointment->confirm();
                                $slot->markBooked();
                            }
                        }

                        $manager->persist($appointment);
                        $appointmentsCreated++;
                    }
                }
            }
        }
    }

    private function buildBio(string $specialtySlug): string
    {
        $intros = [
            'medecine-generale' => 'Médecin généraliste exerçant depuis 2014, conventionné CNAM.',
            'dermatologie' => 'Dermatologue diplômée de la Faculté de médecine de Tunis, exercice depuis 2012.',
            'pediatrie' => 'Pédiatre, suivi des nourrissons et adolescents.',
            'cardiologie' => 'Cardiologue, suivi des pathologies chroniques et bilans.',
            'gynecologie' => 'Gynécologue obstétricienne, suivi de grossesse et planning familial.',
            'ophtalmologie' => 'Ophtalmologue, bilans visuels et chirurgie de la cataracte.',
        ];
        $base = $intros[$specialtySlug] ?? 'Médecin conventionné CNAM.';
        return $base . ' Consultations en cabinet ou en téléconsultation.';
    }

    private function randomMotif(): string
    {
        $motifs = [
            'Renouvellement d\'ordonnance pour traitement chronique.',
            'Douleur persistante au genou droit.',
            'Toux sèche depuis 10 jours, sans fièvre.',
            'Bilan annuel de santé.',
            'Suivi tension artérielle.',
            'Consultation post-opératoire.',
            'Maux de tête fréquents depuis 3 semaines.',
            'Vaccination rappel.',
            'Eczéma persistant sur les avant-bras.',
            'Contrôle après prise de sang.',
        ];
        return $motifs[array_rand($motifs)];
    }
}
