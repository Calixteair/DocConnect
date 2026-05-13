# Style Tile — DocConnect

Spec de design système pour la plateforme de prise de RDV médecin-patient. À lire avant toute tâche UI.

---

## 1. Principes

1. **Calme avant tout.** Si en doute, retirer.
2. **Un seul accent.** Le bleu primaire est la seule couleur vive. Tout le reste est neutre.
3. **La hiérarchie est typographique, pas chromatique.** On ne colore pas pour hiérarchiser.
4. **Espacer plus que serrer.** Le vide est un composant.
5. **Lisible à 55 ans, comme à 25.** 16px minimum sur du texte courant, contraste AA partout.

---

## 2. Couleurs

Une palette restreinte. **Aucun gradient, aucune deuxième teinte d'accent.**

### Neutres

| Token | Hex | Usage | Contraste vs `#FFFFFF` | Contraste vs `#1F2937` |
|---|---|---|---|---|
| `--color-bg` | `#FAFBFC` | Fond global de l'app | 1.02:1 (n/a) | 14.5:1 |
| `--color-surface` | `#FFFFFF` | Cards, modales, popovers, inputs | 1:1 | 14.8:1 |
| `--color-border` | `#E5E7EB` | Bordures fines, séparateurs | 1.3:1 (décoratif) | 11.3:1 |
| `--color-border-strong` | `#D1D5DB` | Bordure d'input au repos | 1.5:1 (décoratif) | 9.8:1 |
| `--color-text` | `#1F2937` | Texte principal, titres | **14.8:1** AAA | 1:1 |
| `--color-text-muted` | `#6B7280` | Texte secondaire, helpers, captions | **4.9:1** AA | 3.0:1 |
| `--color-text-subtle` | `#9CA3AF` | Placeholder, labels désactivés | 3.0:1 (large only) | 1.6:1 |

### Accent — bleu médical

| Token | Hex | Usage | Contraste vs `#FFFFFF` |
|---|---|---|---|
| `--color-primary` | `#1E5AE8` | Boutons primaires, liens, focus ring | **5.6:1** AA |
| `--color-primary-hover` | `#1947C7` | Hover bouton primaire, lien actif | **7.0:1** AAA |
| `--color-primary-soft` | `#EEF3FE` | Fond de badge info, surbrillance créneau sélectionné | 1.05:1 (décoratif) |
| `--color-primary-border` | `#C7D7FA` | Bordure d'input focus alt, badge info | 1.3:1 (décoratif) |

### Statuts sémantiques

| Token | Hex | Usage | Contraste vs `#FFFFFF` |
|---|---|---|---|
| `--color-success` | `#059669` | Confirmation RDV, badge confirmé | **4.5:1** AA |
| `--color-success-soft` | `#ECFDF5` | Fond de toast succès | 1.05:1 |
| `--color-warning` | `#B45309` | RDV à confirmer, avertissement doux | **5.9:1** AA |
| `--color-warning-soft` | `#FFFBEB` | Fond de toast warning | 1.04:1 |
| `--color-danger` | `#DC2626` | Erreur, annulation, destructive | **4.8:1** AA |
| `--color-danger-soft` | `#FEF2F2` | Fond toast erreur, fond input invalide | 1.03:1 |

**Règle d'or** : on n'introduit **jamais** une nouvelle couleur. Une nuance manque ? On joue sur la typographie ou l'espacement avant d'ajouter un hex.

---

## 3. Typographie

### Familles

- **UI / corps** : `Inter` — variable font, weights `400 / 500 / 600 / 700`. Fallback : `-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif`.
- **Titres de fiches médecin** : `Source Serif 4` — variable, weights `500 / 600`. Fallback : `Georgia, "Times New Roman", serif`.

La serif n'apparaît **que** sur le nom du médecin dans la card et l'en-tête de la fiche profil. Partout ailleurs : Inter.

### Échelle modulaire (ratio ~1.2)

| Token | Taille | Line-height | Weight | Usage |
|---|---|---|---|---|
| `--text-display` | 40px / 2.5rem | 48px (1.2) | 600 | Titre de page d'accueil patient, hero rare |
| `--text-h1` | 32px / 2rem | 40px (1.25) | 600 | Titre de page (Mes RDV, Annuaire) |
| `--text-h2` | 24px / 1.5rem | 32px (1.33) | 600 | Sections principales (Disponibilités, À propos) |
| `--text-h3` | 20px / 1.25rem | 28px (1.4) | 600 | Sous-sections, titre de card médecin |
| `--text-h4` | 18px / 1.125rem | 26px (1.44) | 500 | Petits titres, en-tête de modale |
| `--text-body-lg` | 18px / 1.125rem | 28px (1.55) | 400 | Texte de lecture longue (bio médecin) |
| `--text-body` | 16px / 1rem | 24px (1.5) | 400 | Texte courant, paragraphes, boutons md |
| `--text-body-sm` | 14px / 0.875rem | 20px (1.43) | 400 | Métadonnées, helpers, badges |
| `--text-caption` | 12px / 0.75rem | 16px (1.33) | 500 | Labels micro, timestamps |

### Paires titre / texte (exemples)

**Card médecin** (serif + sans)
- Nom : `Source Serif 4` 20px / 28px / 600 / `--color-text`
- Spécialité : `Inter` 14px / 20px / 500 / `--color-text-muted`
- Adresse : `Inter` 14px / 20px / 400 / `--color-text-muted`

**En-tête de page "Mes rendez-vous"**
- H1 : `Inter` 32px / 40px / 600 / `--color-text`
- Sous-titre : `Inter` 16px / 24px / 400 / `--color-text-muted`

**Bloc CTA réservation**
- Label : `Inter` 14px / 20px / 500 / `--color-text-muted` (uppercase 0.04em letter-spacing autorisé ici, **et seulement ici**)
- Date sélectionnée : `Inter` 18px / 26px / 600 / `--color-text`

### Règles typo

- Pas de `text-transform: uppercase` généralisé. Réservé aux labels de section très courts (≤ 3 mots).
- Pas de letter-spacing négatif manuel — la variable font gère.
- Italique réservé à : citation patient, mention "modifié le", placeholder de bio vide.
- Pas plus de 3 niveaux de hiérarchie sur un même écran.

---

## 4. Espacement & layout

### Échelle (base 4)

| Token | px | Usage typique |
|---|---|---|
| `--space-1` | 4 | Gap entre icône et label, padding micro |
| `--space-2` | 8 | Padding interne bouton sm, gap entre badges |
| `--space-3` | 12 | Padding input vertical, gap items liste |
| `--space-4` | 16 | Padding card mobile, gap card-content |
| `--space-6` | 24 | Padding card desktop, gap entre sections d'une card |
| `--space-8` | 32 | Margin entre sections d'une page |
| `--space-12` | 48 | Margin entre blocs majeurs (hero / contenu) |
| `--space-16` | 64 | Padding vertical des sections marketing / landing |

Jamais de valeur hors échelle. Si on a besoin de 20px, on choisit 16 ou 24.

### Grille

- **Desktop ≥ 1024px** : 12 colonnes, gutter 24px, max-width `1200px`, centrée. Padding latéral du conteneur : 32px à partir de `lg`, 48px à partir de `xl`.
- **Tablet 768–1023px** : 8 colonnes, gutter 16px, padding latéral 24px.
- **Mobile < 768px** : 4 colonnes, gutter 16px, padding latéral 16px.
- Breakpoints : `sm 640 / md 768 / lg 1024 / xl 1280`.

### Layouts types

- **Annuaire médecins** : sidebar filtres 280px (sticky `lg+`) + grille de cards `2col lg`, `1col md`.
- **Fiche médecin** : 8 colonnes pour le contenu, 4 pour le slot picker (sticky `lg+`).
- **Dashboard patient** : pleine largeur jusqu'à 1200px, liste verticale de RDV.

---

## 5. Composants

### 5.1 Bouton

**Hauteurs fixes** : sm 32px, md 40px (défaut), lg 48px. Radius 6px.

**Padding horizontal** : sm 12px, md 16px, lg 20px. Gap icône-label : 8px.

**Typo** : 14px / 500 (sm), 16px / 500 (md, lg).

#### Variants

**Primary** — action principale unique par écran.
- Default : fond `--color-primary`, texte `#FFFFFF`, pas de border.
- Hover : fond `--color-primary-hover`, transition 150ms.
- Focus-visible : outline 2px `--color-primary`, offset 2px. Le hover ne suffit pas pour le clavier.
- Active : fond `--color-primary-hover`, translate-y `0` (pas de "press" exagéré).
- Disabled : fond `#E5E7EB`, texte `#9CA3AF`, cursor `not-allowed`, opacity full (pas 0.5).
- Loading : spinner 16px à gauche du label, label conservé ("Réservation…"), bouton non cliquable.

**Secondary** — action alternative.
- Default : fond `#FFFFFF`, border 1px `--color-border-strong`, texte `--color-text`.
- Hover : fond `#F9FAFB`, border `--color-text-muted`.
- Focus-visible : outline 2px `--color-primary`, offset 2px.
- Disabled : fond `#FFFFFF`, border `--color-border`, texte `--color-text-subtle`.

**Ghost** — action tertiaire, dans une toolbar ou à côté d'un primary.
- Default : fond transparent, texte `--color-text`.
- Hover : fond `#F3F4F6`.
- Focus-visible : outline 2px `--color-primary`, offset 2px.

**Danger** — destructive (annuler un RDV, supprimer).
- Default : fond `#FFFFFF`, border 1px `--color-danger`, texte `--color-danger`.
- Hover : fond `--color-danger-soft`.
- Variante pleine `--danger-solid` (rare, en modale de confirm) : fond `--color-danger`, texte blanc.

**Règles** :
- Un seul primary par écran (sauf form multi-étapes).
- L'icône à droite signale une navigation, à gauche une action.
- Largeur min 96px sauf cas en barre d'outils.

### 5.2 Input texte

**Décision** : **label au-dessus**, pas flottant.

Raison : le label flottant pose 3 problèmes pour la cible (médecins seniors + grand public) :
1. Position du label qui bouge = surcharge cognitive.
2. Lecture vocale moins prévisible (screen reader).
3. Le label "rentré" se confond avec un placeholder rempli, source d'hésitation.

Le label classique au-dessus est sobre, accessible, prévisible. C'est ce que fait Doctolib et ce n'est pas un hasard.

**Anatomie** :
- Label : 14px / 20px / 500 / `--color-text`, margin-bottom 6px.
- Helper (sous le champ) : 12px / 16px / 400 / `--color-text-muted`, margin-top 6px.
- Champ : hauteur 40px (md), padding 12px horizontal, border 1px `--color-border-strong`, radius 6px, fond `#FFFFFF`.
- Placeholder : `--color-text-subtle`. **Jamais** d'info critique en placeholder uniquement.
- Astérisque rouge (`--color-danger`) après le label si requis, sans le mot "requis".

**États** :
- Focus : border `--color-primary`, ring 3px `rgb(30 90 232 / 0.15)`, pas de fond modifié.
- Erreur : border `--color-danger`, helper devient `--color-danger`. Message commence par l'action attendue ("Indiquez votre email") pas par "Erreur".
- Disabled : fond `#F9FAFB`, texte `--color-text-muted`, cursor `not-allowed`.
- Readonly : fond `#FFFFFF`, border `--color-border` (plus claire), pas de focus ring.

### 5.3 Card médecin

Bloc de présentation dans l'annuaire ou en suggestion.

- Fond `#FFFFFF`, border 1px `--color-border`, radius 8px, padding 24px (`md+`) / 16px (`sm`).
- Shadow `0 1px 3px rgb(0 0 0 / 0.04)`. **Une seule** ombre, jamais empilée.
- Hover (sur card cliquable uniquement) : border `--color-border-strong`, shadow `0 4px 12px rgb(0 0 0 / 0.06)`, transition 150ms. Pas de translate.

**Layout intérieur** :
- Avatar 56px circle à gauche (radius 999px, fallback initiales sur fond `--color-primary-soft` texte `--color-primary`).
- Bloc texte : nom (serif 20px/600), spécialité (sans 14px/500 muted), adresse + ville (sans 14px/400 muted).
- Tags secondaires en bas : pills `--color-primary-soft` texte `--color-primary` 12px / 500, gap 6px (ex : "Téléconsultation", "Conventionné CNAM").
- CTA "Prendre RDV" en bas à droite (`sm+`) ou pleine largeur (`< sm`), variant primary md.

### 5.4 Badge / Pill — statut RDV

Inline, 24px de haut, padding 8px horizontal, radius 999px, 12px / 500 / line-height 1.

| Statut | Texte | Fond | Texte |
|---|---|---|---|
| Confirmé | "Confirmé" | `--color-success-soft` | `--color-success` |
| En attente | "En attente" | `--color-warning-soft` | `--color-warning` |
| Annulé | "Annulé" | `--color-danger-soft` | `--color-danger` |
| Passé | "Terminé" | `#F3F4F6` | `--color-text-muted` |
| Téléconsultation | "Visio" | `--color-primary-soft` | `--color-primary` |

Pas d'icône dans le badge sauf "Visio" (icône `video` Lucide 12px à gauche, gap 4px).

### 5.5 Slot picker (créneaux)

L'élément le plus important de l'UI. Doit être lisible au premier coup d'œil.

**Layout** :
- Onglets de jours horizontaux sur 7 jours visibles (`md+`), scroll horizontal sur mobile. Chaque onglet : 56px de large, label `lun.` 12px muted + date `14` 18px/600.
- Sous chaque jour, grille de créneaux : `repeat(auto-fill, minmax(96px, 1fr))`, gap 8px.

**Créneau (bouton)** :
- Hauteur 40px, radius 6px, border 1px `--color-border-strong`, fond `#FFFFFF`, texte `--color-text` 14px / 500.
- Label = heure `09:30` (toujours format 24h, deux chiffres).
- Hover : border `--color-primary`, fond `--color-primary-soft`.
- Focus-visible : outline 2px `--color-primary`, offset 2px.
- Sélectionné : fond `--color-primary`, texte `#FFFFFF`, border `--color-primary`.
- Indisponible : caché (pas affiché grisé — on n'encombre pas).

**Empty state du jour** : message court centré, 14px muted : "Aucun créneau libre lundi 12. Voir la semaine suivante."

### 5.6 Alert / Toast

**Alert** = bloc statique dans la page (succès de form, message persistant).

- Padding 16px, radius 8px, border 1px, gap 12px entre icône et contenu.
- Icône 20px à gauche, à la couleur sémantique.
- Titre 14px / 600 (optionnel) + message 14px / 400.
- Bouton close 16px en haut à droite si dismissible (icône `x` Lucide).

| Variant | Fond | Border | Icône / texte titre |
|---|---|---|---|
| Info | `--color-primary-soft` | `--color-primary-border` | `--color-primary` |
| Succès | `--color-success-soft` | `#A7F3D0` | `--color-success` |
| Warning | `--color-warning-soft` | `#FCD34D` | `--color-warning` |
| Erreur | `--color-danger-soft` | `#FECACA` | `--color-danger` |

**Toast** = notification éphémère, en bas à droite (desktop), en haut (mobile sous header).

- Largeur 360px max, fond `#FFFFFF`, border 1px `--color-border`, shadow `0 8px 24px rgb(0 0 0 / 0.08)` (seule exception à la règle "une seule ombre douce" — un toast doit décoller).
- Barre verticale 3px à gauche à la couleur sémantique (succès / erreur / info).
- Durée par défaut : 5s (succès), 8s (erreur), persistant tant que non lu (warning critique).
- Animation : slide-in depuis le bas-droite, fade-out. 250ms.
- Empilable : 3 max visibles, les suivants en queue.

---

## 6. Iconographie

**Famille imposée** : **Lucide** (`lucide-icons`, mit-licensed, cohérent stroke).

- Stroke width : `1.75` (par défaut Lucide). Pas de mélange de poids dans la même vue.
- Tailles standards : 16px (inline texte 14px), 20px (boutons md, inputs), 24px (header, titres de section).
- Couleur : hérite de `currentColor`. Par défaut `--color-text-muted` ; `--color-text` au hover si l'icône est seule (icon button).
- Padding cliquable : icon-only button = 40×40px (zone touch), icône centrée à 20px.

**Aucune emoji dans l'UI prod.** Ni dans les boutons, ni dans les états vides, ni dans les toasts, ni dans les emails transactionnels. Une emoji = un look de side-project amateur.

**Illustrations** : si nécessaire pour empty states ou onboarding, traits fins monochrome `--color-text-subtle`, pas de couleur en pastels. Source : style maison ou `unDraw` en désaturé. Jamais de 3D Memphis générique.

---

## 7. Motion

### Durées

| Token | Durée | Usage |
|---|---|---|
| `--motion-fast` | 150ms | Hover bouton, focus ring apparition, tooltip in |
| `--motion-base` | 250ms | Toast in/out, modale fade, panneau slide, accordéon |
| `--motion-slow` | 400ms | Page transition Turbo, transition de section longue |

### Easing

- Standard : `cubic-bezier(0.4, 0, 0.2, 1)` (Material `ease-out`). Une seule courbe pour 95% des cas.
- Entrée d'un toast / popover : `cubic-bezier(0.16, 1, 0.3, 1)` (ease-out plus marqué) pour l'arrivée, standard pour la sortie.

### Ce qu'on anime

- Hover bouton (background-color, border-color).
- Focus ring (opacité 0 → 1).
- Apparition de toast, modale, popover, tooltip.
- Skeleton (shimmer subtil, 1.5s loop, opacité 0.6 ↔ 1).
- Sélection d'un créneau (background-color 150ms).

### Ce qu'on n'anime PAS

- Position du label d'un input (cf. décision label dessus).
- Translation d'icônes "qui bougent au hover" sur les liens.
- Rotation décorative de quoi que ce soit qui ne soit pas un loader.
- Apparition cascade des items de liste (`stagger`) au chargement de page : amateurisme et coût accessibilité.
- Effet parallaxe.

### `prefers-reduced-motion`

Toutes les animations sont neutralisées (durée `0.01ms`) si l'utilisateur a activé cette préférence système. Aucune exception.

---

## 8. Ton de voix — microcopy

Français, vouvoiement (médical = formel par défaut), phrases courtes, factuel et chaleureux.

### Do's

1. **Dire ce qui se passe et la prochaine action.**
   > "Aucun créneau libre cette semaine. Essayez la semaine suivante."
2. **Pour une erreur, dire quoi corriger, pas que c'est cassé.**
   > "Indiquez une adresse email valide."
3. **Confirmer l'action effectuée, pas féliciter.**
   > "Rendez-vous confirmé pour mardi 14 mai à 10:30."
4. **Utiliser les noms propres et les vraies infos.**
   > "Le Dr Martin a annulé votre rendez-vous du 14 mai."
5. **Préfixer les actions destructives par leur effet, pas par "Attention".**
   > "Annuler ce rendez-vous le supprimera de votre planning et préviendra le Dr Martin."

### Don'ts

1. ~~"Oups ! Quelque chose s'est mal passé."~~ → Inutile, ne dit rien.
2. ~~"No data"~~ / ~~"Error 500"~~ → Anglais brut, technique, anxiogène.
3. ~~"Vous êtes un champion ! RDV pris !"~~ → Le médical n'a pas besoin d'enthousiasme forcé.
4. ~~"Cliquez ici pour..."~~ → Le label du bouton suffit ("Réserver", "Voir mes RDV").
5. ~~"Veuillez nous excuser pour la gêne occasionnée…"~~ → Verbeux, robotique. Une phrase concise vaut mieux.

### Cas types

- **Empty state RDV** : "Vous n'avez aucun rendez-vous à venir. Trouvez un médecin pour réserver."
- **Loading initial** : skeleton, pas de texte "Chargement…".
- **Loading bouton** : conserver le label, ajouter spinner. Ex : "Réservation…" pas "Loading".
- **Email de confirmation** : objet `Rendez-vous confirmé — Dr Martin, mardi 14 mai`. Pas de "✅", pas d'exclamation.

---

## 9. Anti-patterns DocConnect

À bannir sans débat — c'est ce qui transforme un projet en démo IA générique.

1. **Gradients colorés.** Pas de violet → rose, pas de bleu → cyan, pas de "mesh gradient" en hero. Le fond est plat. L'accent est plat.
2. **Glassmorphism.** `backdrop-filter: blur()` sur les modales, headers, popovers : non. Surface opaque, c'est tout.
3. **Ombres exagérées et empilées.** Une seule ombre fine par composant. Pas de `box-shadow` à `rgba(0,0,0,0.25)` ni de double ombre "neumorphism".
4. **Glow / neon / halo.** Aucun `box-shadow` coloré (`0 0 20px rgba(30,90,232,0.5)`). Le focus ring fin suffit.
5. **Emoji-soup dans l'UI ou les emails.** Pas d'emoji dans les boutons, badges, titres, toasts, objets d'email. Lucide pour les pictos.
6. **Illustrations 3D génériques type "office workers in pastel".** Si illustration, traits monochrome fins.
7. **Animations cascade (`stagger`) au chargement.** Les items apparaissent ensemble ou pas. Pas de chorégraphie.
8. **Border radius dramatique.** Pas de `border-radius: 24px` sur des cards de 300px. On garde 8px partout sauf pills/avatars.
9. **Mix de polices "designer".** Pas de Poppins, pas de Montserrat, pas de Manrope. Inter + Source Serif, point.
10. **Texte en majuscules sur des blocs entiers.** Le label de section court (≤ 3 mots) à la rigueur, jamais une phrase.

Si tu hésites devant un effet visuel : **retire-le et regarde**. Si l'écran est plus calme et toujours compréhensible, garde le retrait.
