# 04 — Espace médecin « Planning »

## Objectif de l'écran
Permettre à un médecin connecté de voir son planning hebdomadaire, traiter les demandes de RDV en attente, démarrer une téléconsultation à l'heure dite, et bloquer ou annuler des créneaux.

## Utilisateur cible & contexte d'usage
Médecin connecté entre deux consultations. Il consulte 5 fois par jour son agenda pour valider les nouvelles demandes, voir le RDV suivant, ou démarrer une visio.

## Wireframe ASCII

```
+------------------------------------------------------------------------------+
| DocConnect                                Planning  Dr. Ben Ali ▼  [ Sortir ]|
+------------------------------------------------------------------------------+
|                                                                              |
| Mon planning                                                                 |
| Dr. Aymen Ben Ali  -  Medecine generale                                    |
|                                                                              |
| +--------------------------------------------------------------------------+ |
| | A confirmer (2)                                                          | |
| +--------------------------------------------------------------------------+ |
| | +---------------------------------------------------+ +----------------+ | |
| | | Mer. 14 mai - 10:00 - Cabinet                     | | [ Confirmer ]  | | |
| | | Yasmine Jendoubi  -  motif : "renouvellement..."    | | [ Refuser   ]  | | |
| | +---------------------------------------------------+ +----------------+ | |
| | +---------------------------------------------------+ +----------------+ | |
| | | Ven. 16 mai - 14:30 - Visio                       | | [ Confirmer ]  | | |
| | | Karim Mejri   -  motif : "douleur au genou"     | | [ Refuser   ]  | | |
| | +---------------------------------------------------+ +----------------+ | |
| +--------------------------------------------------------------------------+ |
|                                                                              |
| <  Semaine du 12 au 17 mai 2026  >                       [ + Ajouter slot ] |
|                                                                              |
| +-------+-------+-------+-------+-------+-------+-------+                    |
| | lun.  | mar.  | mer.  | jeu.  | ven.  | sam.  |                            |
| |  12   |  13   |  14   |  15   |  16   |  17   |                            |
| +-------+-------+-------+-------+-------+-------+                            |
| | 09:00 | 09:00 |       | 09:00 | 09:00 |       |                            |
| | LIBRE | A.M.  |       | LIBRE | C.D.  |       |                            |
| +-------+-------+-------+-------+-------+-------+                            |
| | 09:30 | 09:30 |       |       |       |       |                            |
| | C.D.  | LIBRE |       |       |       |       |                            |
| +-------+-------+-------+-------+-------+-------+                            |
| | 10:00 | 10:00 | 10:00 | 10:00 |       |       |                            |
| | LIBRE | LIBRE | C.D.  | LIBRE |       |       |                            |
| +-------+-------+-------+-------+-------+-------+                            |
| | ...   | ...   | ...   | ...   | ...   | ...   |                            |
| +-------+-------+-------+-------+-------+-------+                            |
|                                                                              |
+------------------------------------------------------------------------------+
| A propos     Mentions legales     CGU     Contact            (c) DocConnect  |
+------------------------------------------------------------------------------+
```

## Layout détaillé section par section

### Header authentifié — hauteur 72px
- Identique au header patient. Lien actif "Planning" (souligné 2px `#1E5AE8`).
- `AvatarMenu` "Dr. Ben Ali" avec dropdown (Mon profil, Déconnexion). Pas d'option "Mes RDV" : le médecin **n'a qu'un seul espace**, son planning.

### En-tête de page — padding 32px top / 24px bottom
- Titre H1 "Mon planning" — Inter 32px / 40px / 600 / `#1F2937`.
- Sous-titre Inter 16px / 24px / 400 / `#6B7280` : "Dr. Aymen Ben Ali — Médecine générale".

### Section "À confirmer" — marge top 24px

Mise en haut volontairement : c'est l'action quotidienne du médecin.

- Sous-titre H2 "À confirmer (2)" — Inter 20px / 28px / 600 / `#1F2937`. Le chiffre entre parenthèses passe en `--color-warning` si > 0.
- Liste verticale de `PendingAppointmentCard`, gap 12px.

#### `PendingAppointmentCard`

- Fond `#FFFFFF`, bordure 1px `#E5E7EB`, radius 8px, padding 20px, ombre `0 1px 3px rgb(0 0 0 / 0.04)`.
- Layout `md+` : info gauche (flex 1) + actions droite (flex shrink). Stack vertical `< md`.
- **Bloc info** :
  - Ligne 1 : `Badge` "Visio" ou icône `MapPin` 14px + date complète Inter 16px / 500 / `#1F2937` ("Mer. 14 mai — 10:00 — Cabinet").
  - Ligne 2 : Inter 14px `#1F2937` "Yasmine Jendoubi" en weight 500 + Inter 14px `#6B7280` ` — motif : « renouvellement d'ordonnance »`.
- **Bloc actions** :
  - `ButtonPrimary md` "Confirmer".
  - `ButtonDanger md` "Refuser" (variant outline).

Bouton Refuser → ouvre la modal d'annulation côté médecin (texte adapté : "Refuser cette demande de RDV ?").

### Toolbar agenda — marge top 32px

- Flex row, align center, space-between.
- Gauche : nav semaine.
  - `IconButton` `ChevronLeft` 40×40, bordure 1px `#D1D5DB`, radius 6px. Désactivé sur la semaine en cours si l'on ne veut pas reculer.
  - Label "Semaine du 12 au 17 mai 2026" Inter 16px / 500 / `#1F2937`.
  - `IconButton` `ChevronRight` mêmes specs.
- Droite : `ButtonPrimary md` "Ajouter un créneau" avec icône `Plus` 16px à gauche → ouvre la modal `AddSlotModal`.

### Grille planning hebdo

- Fond `#FFFFFF`, bordure 1px `#E5E7EB`, radius 8px, padding 0 (la grille a ses propres bordures), ombre `0 1px 3px rgb(0 0 0 / 0.04)`.
- 6 colonnes (Lun → Sam) `md+`. Sur mobile, on bascule en vue jour unique avec onglets (cf. variante).
- En-tête de colonne (hauteur 56px) : nom court "lun." Inter 12px / 500 / `#6B7280` + numéro "12" Inter 18px / 600 / `#1F2937`. Aujourd'hui : dot bleu `#1E5AE8` 6px sous le chiffre.
- Cellules de la grille (chaque ligne = un créneau) :

#### `SlotCell` — états

| État | Fond | Bordure | Contenu |
|---|---|---|---|
| `OPEN` (libre) | `#FFFFFF` | 1px `#E5E7EB` | Heure Inter 13px / 500 / `#6B7280` + texte "Libre" Inter 12px / 500 / `#6B7280`. Hover : fond `#F3F4F6`, curseur "More" pour ouvrir le menu (Bloquer, Supprimer). |
| `BOOKED` (confirmé) | `#EEF3FE` | 1px `#C7D7FA` | Heure 13px / 500 / `#1E5AE8` + initiales patient "C.D." Inter 14px / 600 / `#1E5AE8`. Clic → drawer détail RDV. |
| `BOOKED` (pending) | `#FFFBEB` | 1px `#FCD34D` | Heure 13px / 500 / `#B45309` + initiales 14px / 600 / `#B45309`. Indique qu'une action est attendue. |
| `BLOCKED` | `#F3F4F6` | 1px `#E5E7EB` (dashed) | Texte "Bloqué" Inter 12px / 500 / `#6B7280` italique. Pas cliquable. |
| Visio | Idem `BOOKED` + icône `Video` 12px à gauche des initiales. |

- Hauteur cellule : 64px `md+`, 56px `sm`.
- Focus-visible sur cellule cliquable : outline 2px `#1E5AE8`, offset 2px.

### Drawer détail RDV (`AppointmentDrawer`)

Ouverture sur clic d'un slot `BOOKED`. Drawer ancré à droite, 420px de large, full-height, fond `#FFFFFF`, bordure gauche 1px `#E5E7EB`, ombre `-8px 0 24px rgb(0 0 0 / 0.04)`.

- En-tête (padding 24px, bordure basse 1px `#E5E7EB`) :
  - Titre Inter 20px / 600 "Rendez-vous".
  - Bouton close `X` Lucide 20px en haut à droite.
- Corps (padding 24px, gap 24px) :
  - Bloc patient : avatar 56px initiales sur `#EEF3FE` + nom Inter 18px / 600 + tel 14px `#6B7280`.
  - Bloc RDV : date complète, mode, adresse / lien visio, badge statut.
  - Bloc motif : texte du motif libre dans une carte `#FAFBFC` padding 16px radius 8px.
- Pied (padding 24px, bordure haute 1px `#E5E7EB`, flex justify end gap 12px) :
  - `ButtonPrimary` "Démarrer la visio" : visible uniquement si mode = visio **et** T-10 min < now < T+30 min.
  - `ButtonDanger outline` "Annuler ce RDV".

### Modal `AddSlotModal`

- Overlay `rgb(15 23 42 / 0.5)`, dialog 480px, radius 8px, padding 24px, fond `#FFFFFF`.
- Titre Inter 20px / 600 "Ajouter un créneau".
- Champs (gap 16px) :
  - `DateInput` "Date" — `<input type="date">` stylé selon style-tile §5.2.
  - `TimeInput` "Heure de début" — `<input type="time">`.
  - `Select` "Durée" — options 15 / 20 / 30 / 45 min, défaut 20 min.
  - `Segmented` "Mode" — `Cabinet` / `Visio`, défaut Cabinet.
- Pied (gap 12px) : `ButtonGhost` "Annuler" + `ButtonPrimary` "Créer le créneau".

## États

- **Empty (aucun slot dans la semaine)** : bloc centré dans la grille, icône `CalendarPlus` 48px `#D1D5DB`, titre "Aucun créneau cette semaine.", paragraphe `#6B7280` "Ajoutez votre premier créneau pour permettre aux patients de réserver.", `ButtonPrimary` "Ajouter un créneau".
- **Empty (rien à confirmer)** : section "À confirmer" remplacée par un message inline 14px `#6B7280` : "Tous vos rendez-vous sont à jour.".
- **Loading semaine** : skeleton de 6 colonnes × 6 lignes (fond `#F3F4F6`, shimmer).
- **Erreur confirmation** : alerte rouge en haut de la section À confirmer, "Impossible de confirmer ce RDV. Réessayer.".

## Variante mobile (<768px)

- Section "À confirmer" : cards pleine largeur, actions empilées sous l'info, boutons hauteur 48px.
- Grille planning : on passe en **vue jour unique** avec onglets horizontaux scrollables en haut (Lun 12, Mar 13, …). La colonne du jour sélectionné occupe toute la largeur, hauteur de cellule 56px.
- `AppointmentDrawer` devient un bottom-sheet plein écran (poignée drag en haut, close en haut droite).
- `AddSlotModal` devient un bottom-sheet plein écran.

## Interactions

- **Clic "Confirmer"** sur une `PendingAppointmentCard` → `PATCH /app/appointments/{id}/confirm` → toast succès "RDV confirmé. Mail envoyé à Yasmine Jendoubi." + la card disparaît de "À confirmer".
- **Clic "Refuser"** → modal de confirmation → `PATCH /app/appointments/{id}/cancel` côté médecin → toast + mail.
- **Clic cellule `BOOKED`** → ouvre `AppointmentDrawer`.
- **Clic cellule `OPEN`** → menu contextuel (Bloquer / Supprimer le créneau).
- **Clic "Démarrer la visio"** → `/app/visio/{appointmentId}`.
- **Clic "Ajouter un créneau"** → `AddSlotModal`.
- **Hover cellule** : voir tableau d'états.

## Accessibilité

- Focus order : skip link → logo → "Planning" → menu avatar → cards "À confirmer" (boutons) → nav semaine → "Ajouter un créneau" → cellules grille (parcours par ligne, jour par jour) → footer.
- Cellules cliquables = `<button>`, jamais des `<div>` sur `onclick`.
- `AppointmentDrawer` : `role="dialog"`, `aria-modal="true"`, focus trap, Escape ferme et focus revient à la cellule.
- Status couleur + texte (jamais couleur seule).
- Grille : `aria-label="Planning semaine du 12 au 17 mai 2026"`. Chaque cellule a un `aria-label` complet ("Mercredi 14 mai 10:00, rendez-vous avec Yasmine Jendoubi, mode cabinet").

## Notes de design

- **Priorité visuelle inversée** par rapport au patient : ici l'action quotidienne (confirmer) est en haut, le planning hebdo en dessous. Le médecin scanne d'abord "que dois-je faire ?" avant "qu'est-ce qui m'attend ?".
- Pas de couleurs vives sur la grille : on garde le bleu pour les RDV confirmés (lien avec la marque), warning pour les pendings (signal d'action), gris pour les slots libres. **Pas de palette arc-en-ciel par spécialité ou par patient** : on n'est pas Trello.
- **Pas de drag-and-drop** pour déplacer un RDV en MVP : ça paraît simple mais déclenche tout un système d'invalidation, mails, notif patient. Hors scope. Le médecin annule + recrée si besoin.
- Pas de vue mensuelle : la vue semaine couvre 95% des besoins.
