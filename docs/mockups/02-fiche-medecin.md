# 02 — Fiche médecin + calendrier de créneaux

## Objectif de l'écran
Présenter un praticien (identité, cabinets, tarifs) et permettre au patient de réserver un créneau en moins de 3 clics.

## Utilisateur cible & contexte d'usage
Patient qui a cliqué sur un résultat de recherche et compare 1 à 3 fiches avant de décider.
Le but est qu'il valide la confiance (bio, tarif, langues) puis bascule immédiatement sur le calendrier à droite.

## Wireframe ASCII

```
+------------------------------------------------------------------------------+
| DocConnect                                          [ Connexion ] [Inscrire] |
+------------------------------------------------------------------------------+
| <- Retour aux resultats                                                      |
+------------------------------------------------------------------------------+
|                                                                              |
| +----------------------------+ +------------------------------------------+  |
| |                            | |  Prendre rendez-vous                     |  |
| |    [ Photo placeholder ]   | |                                          |  |
| |                            | |  [ Cabinet ] [ Visio ]                   |  |
| | Dr. Aymen Ben Ali        | |                                          |  |
| | Medecin generaliste        | |  < Sem. du 12 au 17 mai 2026         >   |  |
| | Tunis - Belvedere          | |                                          |  |
| |                            | |   Lun 12  Mar 13  Mer 14  Jeu 15  Ven 16 |  |
| | 50 TND  -  CNAM            | |   -----   -----   -----   -----   -----  |  |
| |                            | |   09:00   09:00     -     09:00   09:00  |  |
| | Langues : FR, EN, ES       | |   09:20   09:20     -     09:20     -    |  |
| |                            | |   09:40     -       -       -     09:40  |  |
| | [Visio]                    | |   10:00   10:00   10:00   10:00     -    |  |
| |                            | |     -     10:20   10:20     -     10:20  |  |
| | A propos                   | |   ...     ...     ...     ...     ...    |  |
| | Medecin generaliste        | |                                          |  |
| | depuis 2014, conventionnee | |   [ Voir plus de creneaux v ]            |  |
| | CNAM...                    | |                                          |  |
| |                            | +------------------------------------------+  |
| | Cabinets                   |                                               |
| | -------                    |                                               |
| | 12 av. Habib Bourguiba     |                                               |
| | 1001 Tunis                 |                                               |
| | (Metro Le Passage)         |                                               |
| | [ mini-carte ]             |                                               |
| |                            |                                               |
| +----------------------------+                                                |
|                                                                              |
+------------------------------------------------------------------------------+
| A propos     Mentions legales     CGU     Contact            (c) DocConnect  |
+------------------------------------------------------------------------------+
                                                                       (chat)
```

## Layout détaillé section par section

### Header — hauteur ~72px
Identique à la landing (logo gauche, Connexion + Inscription droite). Si patient connecté, ces deux boutons sont remplacés par un `AvatarMenu` avec son prénom.

### Fil d'Ariane / retour — hauteur ~48px
- Fond `#FAFBFC`, padding 12/24.
- Lien `BreadcrumbBack` : icône Lucide `ArrowLeft` 16px + texte "Retour aux résultats", Inter 14px `#6B7280`. Hover : `#1E5AE8` et soulignement.

### Layout deux colonnes — desktop max 1200px
- Conteneur grid 12 colonnes, gap 32px, padding vertical 32px.
- Colonne gauche : `col-span-5` (info médecin).
- Colonne droite : `col-span-7` (réservation), sticky top 96px sur desktop pour suivre le scroll.

### Colonne gauche — Carte d'identité du médecin

#### Bloc en-tête fiche
- Carte `DoctorHeaderCard` : fond `#FFFFFF`, bordure 1px `#E5E7EB`, radius 8px, padding 24px, ombre `0 1px 3px rgb(0 0 0 / 0.04)`.
- Photo placeholder en haut : carré 120px, radius 8px, fond `#EEF3FE`, icône Lucide `User` 56px centrée en `#1E5AE8` à 40% d'opacité. Si photo réelle, object-fit cover.
- Sous la photo (gap 16px) :
  - Nom : "Dr. Aymen Ben Ali" — Source Serif 4, 28px, weight 600, `#1F2937`.
  - Spécialité : "Médecin généraliste" — Inter 16px weight 500 `#6B7280`, marge top 4px.
  - Ville : icône Lucide `MapPin` 14px + "Tunis — Belvédère" — Inter 14px `#6B7280`.
- Ligne tarif (marge top 24px, séparateur 1px `#F3F4F6` au-dessus, padding top 16px) :
  - "50 TND" en Inter 20px weight 600 `#1F2937`, suivi du séparateur " — " et "Conventionné CNAM" en 14px `#6B7280`.
- Langues : Inter 14px `#6B7280` "Langues parlées : Arabe, Français, Anglais".
- Badges (flex gap 8px, marge top 16px) :
  - `Badge` "Visio" : fond `#EEF3FE`, texte `#1E5AE8`, icône Lucide `Video` 12px, padding 4px/8px, radius 999px, Inter 12px weight 500.

#### Bloc "À propos"
- Marge top 24px.
- Sous-titre H3 "À propos" — Inter 20px weight 600 `#1F2937`, marge bas 8px.
- Paragraphe Inter 16px line-height 1.55 `#1F2937` : "Médecin généraliste depuis 2014, conventionné CNAM. Je consulte adultes et adolescents, avec une attention particulière à la prévention et au suivi des pathologies chroniques. Téléconsultation possible pour les renouvellements d'ordonnance."
- Si bio > 240 caractères, tronquer avec lien "Lire la suite" qui développe le bloc (animation `--motion-base`).

#### Bloc "Cabinets"
- Marge top 32px.
- Sous-titre H3 "Adresses des cabinets" — Inter 20px weight 600.
- Pour chaque adresse (1 à 3), `AddressItem` empilé verticalement, gap 16px :
  - Ligne 1 : Inter 16px weight 500 `#1F2937` : "12 av. Habib Bourguiba".
  - Ligne 2 : Inter 14px `#6B7280` : "1001 Tunis (Belvédère)".
  - Ligne 3 : Inter 14px `#6B7280` : "Station Métro Le Passage · Accès PMR".
  - Mini-carte optionnelle : ratio 16:9, hauteur 120px, radius 8px, OpenStreetMap statique avec marqueur `#1E5AE8`. Pas obligatoire si une seule adresse.

### Colonne droite — Réservation

#### En-tête réservation
- Carte `BookingCard` : fond `#FFFFFF`, bordure 1px `#E5E7EB`, radius 8px, padding 24px, ombre `0 1px 3px rgb(0 0 0 / 0.04)`.
- Titre H2 "Prendre rendez-vous" — Inter 20px weight 600 `#1F2937` (pas de serif ici, la serif est réservée à l'identité du médecin).
- Toggle `SegmentedControl` (marge top 16px) : deux segments "Cabinet" / "Visio", fond `#F3F4F6`, segment actif fond `#FFFFFF` + ombre `0 1px 3px rgb(0 0 0 / 0.04)`, Inter 14px weight 500. Largeur fixe 240px, radius 6px.

#### Navigation semaine
- Marge top 24px, flex space-between, align center.
- Bouton icône gauche `IconButton` : icône Lucide `ChevronLeft` 20px, taille 40px, bordure 1px `#D1D5DB`, radius 6px. Disabled si semaine actuelle.
- Au centre : "Semaine du 12 au 17 mai 2026" — Inter 14px weight 500 `#1F2937`.
- Bouton icône droite : icône `ChevronRight`, mêmes specs.

#### Grille de créneaux
- Marge top 16px.
- Onglets de jours (Lun → Sam) : 56px de large, label `lun.` 12px `#6B7280` + date `14` 18px/600 `#1F2937`. Jour courant : dot bleu `#1E5AE8` 6px sous le chiffre.
- Sous chaque jour, grille `repeat(auto-fill, minmax(96px, 1fr))`, gap 8px.
- `SlotButton` libre : hauteur 40px, fond `#FFFFFF`, bordure 1px `#D1D5DB`, texte `#1F2937` Inter 14px / 500, radius 6px. Hover : bordure `#1E5AE8`, fond `#EEF3FE`. Focus-visible : outline 2px `#1E5AE8`, offset 2px. Sélectionné : fond `#1E5AE8`, texte `#FFFFFF`, bordure `#1E5AE8`.
- `SlotButton` occupé : non rendu (cf. règle "indisponible = caché").
- Empty state par jour : "Aucun créneau libre lundi 12. Voir la semaine suivante." Inter 14px `#6B7280`.
- Lien "Voir plus de créneaux ↓" en bas, centré, Inter 14px `#1E5AE8`, déplie les créneaux supplémentaires par jour.

#### Modal de confirmation `BookingModal`
- Ouverture sur clic créneau, overlay `rgb(15 23 42 / 0.5)`, dialog centré 480px de large, radius 8px, padding 24px, fond `#FFFFFF`, ombre `0 8px 24px rgb(0 0 0 / 0.08)`.
- Titre H2 "Confirmer votre rendez-vous" — Inter 20px weight 600.
- Récap encadré (fond `#FAFBFC`, padding 16px, radius 8px, marge top 16px) :
  - Ligne 1 (Inter 14px `#6B7280` + Inter 16px weight 500 `#1F2937`) : "Médecin · Dr. Aymen Ben Ali".
  - Ligne 2 : "Date · Mercredi 14 mai 2026 à 10:00".
  - Ligne 3 : "Mode · Cabinet — 12 av. Habib Bourguiba, 1001 Tunis".
- Champ motif (marge top 24px) :
  - Label visible Inter 14px weight 500 `#1F2937` : "Motif de consultation".
  - `Textarea` 3 lignes, padding 12px, bordure 1px `#D1D5DB`, focus bordure `#1E5AE8` + ring 3px `rgb(30 90 232 / 0.15)`, radius 6px. Placeholder : "Décrivez brièvement votre motif (ex : renouvellement d'ordonnance, douleur au genou…)".
  - Compteur 0/500 en bas droite Inter 12px `#6B7280`.
- Pied de modal (flex justify-end gap 12px, marge top 24px) :
  - `ButtonGhost` "Annuler" : texte `#1F2937`, hover fond `#F3F4F6`.
  - `ButtonPrimary` "Confirmer le RDV" : fond `#1E5AE8`, hover `#1947C7`.

### États
- **Vide** (aucun créneau cette semaine) : zone calendrier remplacée par un bloc centré padding 48px, illustration Lucide `CalendarX` 48px en `#D1D5DB`, titre Inter 16px weight 600 "Aucun créneau libre cette semaine.", paragraphe `#6B7280` "Essayez la semaine suivante ou activez les alertes par e-mail.", `ButtonPrimary` "Semaine suivante →" + `ButtonGhost` "Recevoir une alerte".
- **Chargement** (changement de semaine) : grille remplacée par 6 colonnes de `SkeletonBlock` 32px de haut, 4 par colonne, fond `#F3F4F6` avec shimmer.
- **Erreur** : bandeau rouge dans la carte, "Impossible de charger les créneaux. Réessayer."
- **Connexion requise** (patient non connecté qui clique sur un créneau) : modal redirige vers login, mais conserve l'intention (créneau + médecin) en session pour reprendre après auth.

## Variante mobile (<768px)
La grille 12 colonnes passe en 1 colonne : carte médecin en haut, calendrier en dessous (plus de sticky). La carte d'identité réduit son padding à 20px, la photo passe à 80px. Le calendrier devient un carrousel jour par jour : un seul jour visible à la fois, swipe horizontal pour naviguer, dots de pagination en bas. Les créneaux du jour sélectionné s'empilent en 2 colonnes, padding plus généreux pour le tap (44px de hauteur min). Modal de confirmation devient un bottom-sheet plein écran, avec poignée de drag en haut et bouton de fermeture `X` en haut droite.

## Interactions
- **Clic sur badge "Visio"** : scrolle vers la carte de réservation et active automatiquement le segment "Visio".
- **Toggle Cabinet / Visio** : rafraîchit la grille de créneaux (les créneaux visio peuvent être différents). Pas d'animation cascade.
- **Hover créneau** : bordure `#1E5AE8` + fond `#EEF3FE` (cf. style-tile).
- **Clic créneau** : ouvre `BookingModal` avec date pré-remplie. Focus auto-posé sur le textarea motif.
- **Submit modal** : si validation OK, redirige vers `/rdv/confirmation/{id}` avec un toast vert "Rendez-vous confirmé pour mardi 14 mai à 10:00. Mail envoyé.".
- **Échec validation** (motif vide, créneau pris entre temps) : alerte inline rouge dans la modal, sans fermeture.
- **Clic mini-carte adresse** : ouvre OpenStreetMap dans un nouvel onglet à l'adresse exacte.
- **Bouton retour** : `history.back()`, sinon `/recherche` en fallback.

## Accessibilité
- Focus order : retour → toggle Cabinet/Visio → nav semaine ← → grille créneaux (par jour puis par heure) → "Voir plus".
- Les boutons créneaux ont `aria-label="Réserver un créneau le mercredi 14 mai 2026 à 10:00 avec le Dr. Aymen Ben Ali en cabinet"` (date + heure + médecin + mode). [médecin déjà migré.]
- Toggle Cabinet/Visio : pattern `role="tablist"` + `role="tab"` + `aria-selected`.
- Modal : `role="dialog"`, `aria-modal="true"`, `aria-labelledby` sur le titre, focus trap, Escape ferme et focus retourne au créneau d'origine.
- Mini-carte : `alt` descriptif "Carte du cabinet du Dr. Ben Ali, 12 av. Habib Bourguiba, Tunis".
- Contraste créneau libre `#1E5AE8` sur blanc = 6.4:1 OK. Bordure 1px suffisamment épaisse pour les daltoniens (taille + couleur).

## Notes de design
On évite :
- Les calendriers "boîte à confettis" colorés style Calendly avec dégradés et emojis : ici sobriété médicale, créneaux sont des **objets sérieux**, pas des stickers.
- Afficher les créneaux occupés en gris : ça pollue visuellement et donne une impression d'indisponibilité. On les masque, point.
- Les photos de médecins en gros plan style LinkedIn (effet "annuaire"). Placeholder neutre par défaut, photo seulement si le praticien l'a explicitement chargée.

Parti pris : **calendrier = focus principal** de la page. La colonne droite est plus large (7/12) que la gauche, et `sticky` pour qu'elle accompagne le scroll quand l'utilisateur lit la bio. Le serif "Source Serif" sur le nom du médecin signale l'**identité personnelle / institutionnelle** (comme une plaque de cabinet), contraste volontaire avec le reste en Inter qui reste technique. Pas de note / avis utilisateurs sur la fiche : DocConnect ne veut pas devenir TripAdvisor de la médecine, donc on ne lance pas un système de notation publique (décision produit, à confirmer en review).
