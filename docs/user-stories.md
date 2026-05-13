# User Stories — DocConnect (MVP)

Périmètre **MVP serré**, aligné sur la stack et la roadmap. Les stories admin et v2 sont listées en bas pour mémoire mais hors livraison MVP.

---

## Patient

**US-01 — Création de compte.** En tant que patient, je veux créer un compte avec email + mot de passe, afin d'accéder à la plateforme.
- Inscription via Firebase Auth (Email/Password).
- Profil patient créé en base au premier login (prénom, nom, téléphone).
- Erreurs claires si email déjà utilisé ou mot de passe trop faible.

**US-02 — Connexion / déconnexion.** En tant que patient, je veux me connecter et me déconnecter, afin de sécuriser mon espace.
- Login Firebase → cookie httpOnly côté Symfony.
- Redirection vers `/app/mes-rdv` après login.
- Bouton de déconnexion accessible depuis toute page authentifiée.

**US-03 — Rechercher un médecin.** En tant que patient, je veux rechercher un médecin par spécialité et ville, afin de trouver un praticien adapté.
- Filtres : spécialité (select), ville (texte libre), mode (cabinet / visio).
- Résultats paginés (12 par page) : nom, spécialité, ville, prochaine dispo.

**US-04 — Consulter une fiche médecin.** En tant que patient, je veux consulter la fiche d'un médecin avec ses créneaux, afin de choisir un rendez-vous.
- Fiche : photo placeholder, bio, spécialité(s), tarif, adresse(s).
- Calendrier sur 7 jours glissants, navigation semaine précédente / suivante.
- Mode (cabinet / visio) visible sur chaque créneau.

**US-05 — Réserver un créneau (atomique).** En tant que patient, je veux réserver un créneau de manière atomique, afin d'éviter les doubles réservations.
- Transaction SQL avec `SELECT … FOR UPDATE` sur le créneau (`OPEN` → `BOOKED`).
- Si le créneau est pris entre l'affichage et le clic : message d'erreur explicite, retour à la grille.
- Confirmation immédiate à l'écran + récap du RDV.

**US-06 — Annuler un RDV.** En tant que patient, je veux annuler un RDV à venir, afin de libérer le créneau si je ne peux plus venir.
- Annulation possible jusqu'à 2 h avant le RDV.
- Le créneau redevient `OPEN`.
- Mail d'annulation envoyé au patient et au médecin.

**US-07 — Espace "Mes RDV".** En tant que patient, je veux consulter mes RDV, afin de garder une trace de mes consultations.
- Deux sections : à venir / passés, tri date décroissante.
- Chaque ligne : médecin, date, mode, statut (`PENDING`, `CONFIRMED`, `CANCELLED`, `DONE`).
- Lien "Rejoindre la visio" actif 10 min avant un RDV vidéo.

**US-08 — Chatbot d'orientation.** En tant que patient, je veux discuter avec un chatbot d'orientation, afin d'être guidé vers la bonne spécialité.
- Widget flottant, propulsé par OpenRouter en streaming.
- Suggestions de spécialités + lien vers la recherche en fin d'échange.
- System prompt : pas de diagnostic, redirection vers urgences si signaux d'alerte.

**US-09 — Visio.** En tant que patient, je veux rejoindre la visio depuis l'espace patient, afin de réaliser ma téléconsultation.
- Bouton "Rejoindre" actif de T-10 min à T+30 min.
- Jitsi Meet embarqué en iframe, salle nommée par l'ID du RDV.
- Accès refusé hors créneau.

**US-10 — Mails transactionnels.** En tant que patient, je veux recevoir mails de confirmation, rappel et annulation, afin de ne rien oublier.
- Mail de confirmation envoyé à la réservation.
- Mail de rappel envoyé 24 h avant le RDV (commande à lancer manuellement en démo).
- Templates Twig sobres avec récap RDV + lien vers `Mes RDV`.

---

## Médecin

**US-11 — Compte médecin.** En tant que médecin, je veux créer un compte, afin d'être référencé sur la plateforme.
- Inscription Firebase + complétion profil (nom, prénom, spécialité, RPPS factice, tarif, langues, adresse(s) du cabinet).
- Le médecin est actif dès création (validation admin = hors MVP, cf. bonus).

**US-12 — Définir des créneaux.** En tant que médecin, je veux saisir mes créneaux disponibles, afin que les patients puissent réserver.
- Création unitaire d'un créneau (date + heure de début + durée + mode cabinet/visio).
- Suppression possible tant que `Slot.status = OPEN`.
- (Pas de récurrence en MVP, on saisit créneau par créneau.)

**US-13 — Planning hebdomadaire.** En tant que médecin, je veux consulter mon planning de la semaine, afin de voir mes RDV.
- Vue calendrier hebdo, créneaux occupés vs libres.
- Clic sur RDV → patient, motif libre, mode.
- Navigation semaine précédente / suivante.

**US-14 — Confirmer / annuler un RDV.** En tant que médecin, je veux confirmer ou annuler un RDV, afin de gérer mon agenda.
- Bouton "Confirmer" sur les RDV `PENDING`.
- Bouton "Annuler" disponible jusqu'à 2 h avant le RDV → mail aux deux parties.

**US-15 — Visio côté médecin.** En tant que médecin, je veux rejoindre la visio depuis mon planning, afin de réaliser la téléconsultation.
- Bouton "Démarrer la visio" actif 10 min avant le RDV.
- Même salle Jitsi que le patient (ID RDV).

---

## Tableau récap

| ID | Story | Phase ROADMAP |
|---|---|---|
| US-01 | Création de compte patient | 2 |
| US-02 | Connexion / déconnexion | 2 |
| US-03 | Recherche médecin | 4 |
| US-04 | Fiche médecin + créneaux | 4 |
| US-05 | Réservation atomique | 5 |
| US-06 | Annulation RDV (patient) | 5 / 7 |
| US-07 | Espace Mes RDV | 5 |
| US-08 | Chatbot OpenRouter | 6 |
| US-09 | Visio Jitsi (patient) | 8 |
| US-10 | Mails transactionnels | 7 |
| US-11 | Compte médecin | 2 / 3 |
| US-12 | Saisie créneaux | 5 |
| US-13 | Planning médecin | 5 |
| US-14 | Confirmer / annuler côté médecin | 5 / 7 |
| US-15 | Visio Jitsi (médecin) | 8 |

---

## Hors MVP — pour mémoire

- **Admin.** Validation des médecins, désactivation, dashboard KPI. → après Phase 9 si le temps le permet.
- **Compte-rendu post-RDV.** Champ texte privé médecin.
- **Notation / avis** patients sur médecins. Décision produit : non, on n'ouvre pas un TripAdvisor médical.
- **Récap quotidien planning** par mail médecin.
- **Créneaux récurrents.**
- **SMS** (rappel, confirmation). Mail uniquement en MVP.
