# 03 — Espace patient « Mes RDV »

## Objectif de l'écran
Permettre à un patient connecté de visualiser ses rendez-vous (à venir et passés), de rejoindre une visio à l'heure dite, ou d'annuler un RDV.

## Utilisateur cible & contexte d'usage
Patient connecté qui revient sur la plateforme pour vérifier ou gérer un RDV existant. Le plus souvent : la veille du RDV (rappel reçu par mail), ou juste avant une visio.

## Wireframe ASCII

```
+------------------------------------------------------------------------------+
| DocConnect                          [ Mes RDV ]  Sarra B. ▼    [ Deconnex ]  |
+------------------------------------------------------------------------------+
|                                                                              |
| Mes rendez-vous                                                              |
| Retrouvez vos rendez-vous a venir et l historique de vos consultations.      |
|                                                                              |
| +--------------------------------------------------------------------------+ |
| | A venir (2)                                                              | |
| +--------------------------------------------------------------------------+ |
| |                                                                          | |
| | +----------------------------------------------------------------------+ | |
| | |   MER     Dr. Aymen Ben Ali                                          | | |
| | |   14      Medecine generale                                          | | |
| | |   MAI     Mer. 14 mai 2026 -  10:00     [ Confirme ]    [ Visio ]   | | |
| | |          12 av. Habib Bourguiba, 1001 Tunis                         | | |
| | |                                                                      | | |
| | |          [ Rejoindre la visio ]   [ Annuler le RDV ]                | | |
| | +----------------------------------------------------------------------+ | |
| |                                                                          | |
| | +----------------------------------------------------------------------+ | |
| | |   LUN     Dr. Sonia Trabelsi                                         | | |
| | |   26      Dermatologie                                               | | |
| | |   MAI     Lun. 26 mai 2026 -  16:30     [ En attente ]              | | |
| | |          Cabinet 8 av. de la Republique, 4000 Sousse                | | |
| | |                                                                      | | |
| | |          [ Voir la fiche du medecin ]   [ Annuler le RDV ]          | | |
| | +----------------------------------------------------------------------+ | |
| +--------------------------------------------------------------------------+ |
|                                                                              |
| +--------------------------------------------------------------------------+ |
| | Historique (1)                                                           | |
| +--------------------------------------------------------------------------+ |
| | +----------------------------------------------------------------------+ | |
| | |   JEU     Dr. Mehdi Khaldi                                           | | |
| | |   17      Generaliste                                                | | |
| | |   AVR     Jeu. 17 avril 2026 -  09:30   [ Termine ]                 | | |
| | +----------------------------------------------------------------------+ | |
| +--------------------------------------------------------------------------+ |
|                                                                              |
+------------------------------------------------------------------------------+
| A propos     Mentions legales     CGU     Contact            (c) DocConnect  |
+------------------------------------------------------------------------------+
                                                                       (chat)
```

## Layout détaillé section par section

### Header authentifié — hauteur 72px
- Fond `#FFFFFF`, bordure basse 1px `#E5E7EB`.
- Conteneur centré max-width 1200px, padding horizontal 32px.
- Gauche : logo "DocConnect" en Inter Semibold 20px `#1F2937`, picto 24px `#1E5AE8`.
- Centre / droite : lien "Mes RDV" actif (souligné 2px `#1E5AE8` en bas) + `AvatarMenu` "Sarra B." avec dropdown (Profil, Déconnexion). Avatar = cercle 32px initiales "SB" sur fond `#EEF3FE` texte `#1E5AE8`.

### En-tête de page — padding 32px top / 24px bottom
- Conteneur 1200px, padding horizontal 32px (lg+).
- Titre H1 "Mes rendez-vous" — Inter 32px / 40px / 600 / `#1F2937`.
- Sous-titre Inter 16px / 24px / 400 / `#6B7280` : "Retrouvez vos rendez-vous à venir et l'historique de vos consultations.".

### Section "À venir" — marge top 24px

- Sous-titre H2 "À venir (2)" — Inter 20px / 28px / 600 `#1F2937`.
- Liste verticale de `AppointmentCard`, gap 16px.

#### `AppointmentCard` (à venir)

- Fond `#FFFFFF`, bordure 1px `#E5E7EB`, radius 8px, padding 24px, ombre `0 1px 3px rgb(0 0 0 / 0.04)`.
- Layout horizontal `md+` : bloc date à gauche (88px de large), bloc info au centre (flex 1), actions à droite. Stack vertical `< md`.
- **Bloc date** : carré 88×88, fond `#FAFBFC`, radius 8px, contenu centré :
  - Jour court "MER" — Inter 12px / 500 / `#6B7280` / uppercase / letter-spacing 0.04em.
  - Numéro "14" — Inter 28px / 600 / `#1F2937`.
  - Mois "MAI" — Inter 12px / 500 / `#6B7280` / uppercase.
- **Bloc info** :
  - Nom médecin Source Serif 4 20px / 600 / `#1F2937`.
  - Spécialité Inter 14px / 500 / `#6B7280`.
  - Date longue + heure Inter 14px / 400 / `#1F2937` ("Mercredi 14 mai 2026 — 10:00") avec badge statut inline à droite (cf. style-tile §5.4).
  - Adresse ou mention "Téléconsultation" Inter 14px / 400 / `#6B7280` avec icône `MapPin` ou `Video` 14px.
- **Bloc actions** (flex gap 8px, justify end) :
  - `ButtonPrimary md` "Rejoindre la visio" : visible uniquement si mode = visio **et** T-10 min < now < T+30 min. Icône `Video` 16px à gauche.
  - `ButtonSecondary md` "Voir la fiche du médecin" : lien vers `/medecin/{slug}`.
  - `ButtonDanger md` "Annuler le RDV" : ouvre la modal d'annulation. Disabled si <2 h du RDV.

#### Badges statut (cf. style-tile §5.4)
- `PENDING` → badge "En attente" (warning soft).
- `CONFIRMED` → badge "Confirmé" (success soft).
- `CANCELLED` → badge "Annulé" (danger soft).
- `DONE` → badge "Terminé" (neutre).

### Section "Historique" — marge top 48px

- Sous-titre H2 "Historique (1)" — mêmes specs.
- `AppointmentCard` allégée :
  - Pas de bouton d'action (lecture seule).
  - Bloc date en gris `#6B7280`, info en `#6B7280`, opacité globale 0.85.
  - Badge "Terminé" toujours présent.

### Modal d'annulation `CancelModal`

- Overlay `rgb(15 23 42 / 0.5)`, dialog 480px de large, radius 8px, padding 24px, fond `#FFFFFF`, ombre `0 8px 24px rgb(0 0 0 / 0.08)`.
- Titre Inter 20px / 600 : "Annuler ce rendez-vous ?".
- Récap court (fond `#FAFBFC`, padding 16px, radius 8px, marge top 16px) : médecin / date / mode.
- Texte explicatif Inter 14px `#1F2937` : "L'annulation libère le créneau et prévient le Dr Ben Ali par mail. Cette action est définitive.".
- Pied (justify end, gap 12px, marge top 24px) :
  - `ButtonGhost` "Conserver le RDV".
  - `ButtonDanger solid` "Annuler le RDV" : fond `#DC2626`, texte blanc.

## États

- **Empty (aucun RDV à venir)** : section "À venir" remplacée par carte centrée, padding 48px, icône Lucide `CalendarOff` 48px `#D1D5DB`, titre Inter 16px / 600 "Vous n'avez aucun rendez-vous à venir.", paragraphe Inter 14px `#6B7280` "Trouvez un médecin pour réserver.", `ButtonPrimary` "Rechercher un médecin" → `/`.
- **Empty (aucun historique)** : section masquée.
- **Loading initial** : skeleton de 2 `AppointmentCard` (fond `#F3F4F6`, shimmer 1.5 s loop).
- **Erreur fetch** : `Alert` variant erreur en haut de la page : "Impossible de charger vos RDV. Réessayer.".
- **RDV juste annulé** : `Toast` succès 5 s "Rendez-vous du 14 mai annulé. Mail envoyé au Dr Ben Ali.".

## Variante mobile (<768px)

- Stack vertical complet. Bloc date passe en horizontal (badge `MER 14 MAI` Inter 14px) au-dessus du bloc info, pas de carré 88×88.
- Padding card 16px.
- Actions empilées verticalement, pleine largeur, hauteur 48px (touch target).
- Header replie le menu en `Menu` Lucide qui ouvre un drawer (Mes RDV / Profil / Déconnexion).

## Interactions

- **Hover card** (lecture seule) : aucun effet (la card n'est pas cliquable globalement, seuls les boutons le sont).
- **Clic "Rejoindre la visio"** : redirige vers `/app/visio/{appointmentId}`.
- **Clic "Annuler"** : ouvre `CancelModal`. Confirmation → `PATCH /app/appointments/{id}/cancel` → toast succès + carte passe en `CANCELLED` (ou disparaît après 5 s avec animation fade `--motion-base`).
- **Clic "Voir la fiche du médecin"** : `/medecin/{slug}` dans le même onglet.
- **Bouton "Rejoindre la visio"** : disabled hors fenêtre, tooltip "Disponible 10 minutes avant le rendez-vous.".

## Accessibilité

- Focus order : skip link → logo → "Mes RDV" → avatar menu → première card (boutons dans l'ordre visuel) → cards suivantes → footer.
- Chaque `AppointmentCard` est un `<article>` avec `aria-labelledby` pointant sur le nom du médecin.
- Badges statut : texte explicite, pas seulement la couleur (cf. règle daltonisme).
- Modal d'annulation : `role="dialog"`, `aria-modal="true"`, focus trap, Escape ferme.
- Contraste : tous les badges respectent AA (cf. style-tile §2).

## Notes de design

- **Bloc date à gauche** = repère temporel fort. Doctolib utilise une vignette similaire ; c'est efficace, lisible en diagonale.
- Pas de liste compacte type "tableau" : on est dans une UI patient, pas un back-office.
- Pas de filtres ni de tri en MVP. La séparation "À venir / Historique" suffit pour la quantité de RDV qu'un patient aura.
- **Pas d'avis à laisser** sur le RDV terminé : décision produit, on ne devient pas TripAdvisor.
