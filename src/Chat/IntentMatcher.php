<?php

declare(strict_types=1);

namespace App\Chat;

/**
 * Court-circuit avant LLM : matche les questions ultra-fréquentes
 * sur des réponses pré-écrites (= 0 appel API, 0 latence).
 *
 * Match insensible casse/accents, sur des mots-clés (pas regex complexe).
 */
final class IntentMatcher
{
    /**
     * @var array<string, array{patterns: list<string>, response: string}>
     */
    private const INTENTS = [
        'greeting' => [
            'patterns' => ['bonjour', 'salut', 'hello', 'coucou', 'hey', 'bonsoir'],
            'response' => "Bonjour ! Je suis l'assistant DocConnect. Décrivez-moi votre besoin (symptôme général, recherche d'un type de médecin, question sur la plateforme) et je vous oriente.\n\nRappel : je suis un assistant d'orientation, pas un médecin. En cas d'urgence, composez le 190.",
        ],
        'thanks' => [
            'patterns' => ['merci', 'thanks', 'thx'],
            'response' => "Avec plaisir ! Bonne consultation.",
        ],
        'cancel_rdv' => [
            'patterns' => ['comment annuler', 'annuler mon rdv', 'annuler mon rendez', 'supprimer rdv', 'comment supprimer'],
            'response' => "Pour annuler un rendez-vous : rendez-vous dans **Mes RDV** depuis le menu en haut, repérez le RDV concerné et cliquez sur **Annuler**. L'annulation est possible jusqu'à 2 heures avant le rendez-vous.",
        ],
        'find_rdv' => [
            'patterns' => ['ou sont mes rdv', 'où sont mes rdv', 'voir mes rdv', 'mes rendez-vous', 'liste rdv'],
            'response' => "Vos rendez-vous sont accessibles depuis **Mes RDV** dans le menu en haut. Vous y voyez les RDV à venir et l'historique.",
        ],
        'how_book' => [
            'patterns' => ['comment prendre rdv', 'comment réserver', 'comment prendre rendez', 'reserver rdv'],
            'response' => "Pour prendre rendez-vous :\n1. Cherchez un médecin via la barre de recherche (spécialité + ville).\n2. Cliquez sur la fiche du praticien.\n3. Choisissez un créneau dans le calendrier et confirmez.\nVous recevez un mail de confirmation immédiat.",
        ],
        'visio_help' => [
            'patterns' => ['comment visio', 'téléconsultation comment', 'rejoindre visio', 'comment fonctionne visio'],
            'response' => "La téléconsultation se fait via Jitsi Meet directement depuis votre espace **Mes RDV**. Le bouton **Rejoindre la visio** apparaît 10 minutes avant l'heure du rendez-vous.",
        ],
        'urgent' => [
            'patterns' => ['urgence', 'urgent', 'samu', 'mourir', 'crise cardiaque', 'avc'],
            'response' => "**En cas d'urgence vitale, composez le 190 (SAMU Tunisie) ou rendez-vous aux urgences les plus proches.**\n\nDocConnect est destiné aux consultations programmées, pas aux urgences.",
        ],
    ];

    public function match(string $question): ?string
    {
        $normalized = $this->normalize($question);
        if ('' === $normalized) {
            return null;
        }

        foreach (self::INTENTS as $intent) {
            foreach ($intent['patterns'] as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    return $intent['response'];
                }
            }
        }

        return null;
    }

    private function normalize(string $input): string
    {
        $lower = mb_strtolower(trim($input), 'UTF-8');
        // Remplace accents pour matcher "où" → "ou", "à" → "a", etc.
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);
        return $transliterated !== false ? $transliterated : $lower;
    }
}
