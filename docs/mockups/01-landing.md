# 01 — Landing publique + recherche médecin

## Objectif de l'écran
Permettre à un visiteur non connecté de trouver rapidement un médecin par spécialité et ville, et d'amorcer une prise de rendez-vous.

## Utilisateur cible & contexte d'usage
Patient anonyme, souvent pressé, parfois en mobilité (mobile), qui arrive par recherche Google ou bouche-à-oreille.
Premier contact avec DocConnect : il doit comprendre la promesse en moins de 5 secondes et lancer une recherche en un clic.

## Wireframe ASCII

```
+------------------------------------------------------------------------------+
| DocConnect                                          [ Connexion ] [Inscrire] |
+------------------------------------------------------------------------------+
|                                                                              |
|                                                                              |
|              Trouvez votre medecin, prenez rendez-vous.                      |
|              -----------------------------------------                       |
|              Annuaire de praticiens en cabinet                               |
|              ou en teleconsultation.                                         |
|                                                                              |
|     +------------------------------------------------------------------+     |
|     |  [ Specialite, nom du medecin... ] | [ Ville, code postal ] | OK |     |
|     +------------------------------------------------------------------+     |
|                                                                              |
|                                                                              |
+------------------------------------------------------------------------------+
|                                                                              |
|                          Comment ca marche                                   |
|                                                                              |
|     [ Search ]              [ CalendarCheck ]            [ Stethoscope ]     |
|     1. Cherchez             2. Reservez                  3. Consultez        |
|     un specialiste          en quelques clics            sereinement         |
|     pres de chez vous       un creneau qui vous          en cabinet ou       |
|                             convient                     en visio            |
|                                                                              |
+------------------------------------------------------------------------------+
|                                                                              |
|                       Specialites les plus demandees                         |
|                                                                              |
|   ( Medecin generaliste )  ( Dentiste )  ( Dermatologue )  ( Ophtalmo )      |
|   ( Pediatre )  ( Gynecologue )  ( Cardiologue )  ( Psychiatre )             |
|   ( Kine )  ( ORL )  ( Voir toutes les specialites -> )                      |
|                                                                              |
+------------------------------------------------------------------------------+
|                                                                              |
|                          Ils nous font confiance                             |
|                                                                              |
|   +---------------------+ +---------------------+ +---------------------+    |
|   | "RDV pris en 2 min, | | "Tres simple a      | | "Le rappel par mail |    |
|   | super pratique."    | | utiliser, je        | | la veille est top." |    |
|   |                     | | recommande."        | |                     |    |
|   | Salma, 34 ans       | | Mohamed-Ali, 58 ans | | Ines, 27 ans        |    |
|   +---------------------+ +---------------------+ +---------------------+    |
|                                                                              |
+------------------------------------------------------------------------------+
| A propos     Mentions legales     CGU     Contact            (c) DocConnect  |
+------------------------------------------------------------------------------+
                                                                       (chat)
```

## Layout détaillé section par section

### Header — hauteur 72px
- Fond `#FFFFFF`, bordure basse 1px `#E5E7EB`.
- Conteneur centré max-width 1200px, padding horizontal 32px (lg+).
- Gauche : logo texte "DocConnect" en Inter Semibold 20px `#1F2937`, picto carré 24px en accent `#1E5AE8` à gauche du mot.
- Droite : deux boutons espacés de 12px.
  - `ButtonGhost` "Connexion" : texte `#1F2937`, fond transparent, hover fond `#F3F4F6`.
  - `ButtonPrimary` "Inscription" : fond `#1E5AE8`, texte blanc, radius 6px, hauteur 40px, padding horizontal 16px.

### Hero — hauteur ~520px desktop
- Fond `#FAFBFC` **plat**, pas de gradient (anti-pattern n°1 du style-tile).
- Padding vertical 96px haut / 64px bas, contenu centré, largeur max 720px pour le texte.
- Titre H1 : "Trouvez votre médecin, prenez rendez-vous." — Source Serif 48px desktop / 32px mobile, weight 600, couleur `#1F2937`, line-height 1.15.
- Sous-titre : "Annuaire de praticiens en cabinet ou en téléconsultation." — Inter 18px, couleur `#6B7280`, marge top 16px, max 560px.
- Barre de recherche `SearchBar` — hauteur 64px, fond `#FFFFFF`, radius 8px, ombre `0 1px 3px rgb(0 0 0 / 0.04)`, bordure 1px `#E5E7EB` :
  - Champ 1 `InputSearch` : placeholder "Spécialité, nom du médecin…" — icône Lucide `Search` 16px à gauche, couleur `#6B7280`.
  - Séparateur vertical 1px `#E5E7EB`.
  - Champ 2 `InputSearch` : placeholder "Ville, code postal" — icône Lucide `MapPin` 16px à gauche.
  - `ButtonPrimary` à droite : "Rechercher", largeur 140px, fond `#1E5AE8`, hover `#1947C7`.
- Sous la barre, micro-texte : "Cabinet ou téléconsultation. Sans inscription pour rechercher." — Inter 13px, `#6B7280`, marge top 12px.

### Section "Comment ça marche" — hauteur ~320px
- Fond `#FFFFFF`, padding vertical 64px.
- Titre H2 centré : "Comment ça marche" — Inter 24px, weight 600, marge bas 48px.
- Trois colonnes égales (4/12 chacune), gap 32px.
  - Chaque colonne : icône Lucide 24px en accent `#1E5AE8` dans un cercle 56px fond `#EEF3FE`, puis titre H3 puis description.
  - Col 1 : icône `Search`. Titre : "Cherchez". Texte : "Un spécialiste près de chez vous, filtré par spécialité ou par mode de consultation."
  - Col 2 : icône `CalendarCheck`. Titre : "Réservez". Texte : "En quelques clics, un créneau qui vous convient. Confirmation immédiate par mail."
  - Col 3 : icône `Stethoscope`. Titre : "Consultez". Texte : "Sereinement, en cabinet ou en visio. Rappel par mail 24 h avant."

### Section "Spécialités populaires" — hauteur ~240px
- Fond `#FAFBFC`, padding vertical 64px.
- Titre H2 centré : "Spécialités les plus demandées" — Inter 24px, weight 600, marge bas 32px.
- Grille flex centrée, gap 8px, max 800px.
- Composant `Chip` cliquable : padding 8px/16px, fond `#FFFFFF`, bordure 1px `#E5E7EB`, radius 999px, texte `#1F2937` Inter 14px / 500. Hover : bordure `#1E5AE8`, texte `#1E5AE8`. Focus-visible : outline 2px `#1E5AE8`, offset 2px.
- Libellés : "Médecin généraliste", "Dentiste", "Dermatologue", "Ophtalmologue", "Pédiatre", "Gynécologue", "Cardiologue", "Psychiatre", "Kinésithérapeute", "ORL".
- Dernier chip variante texte-lien : "Voir toutes les spécialités →" avec icône Lucide `ArrowRight` 14px.

### Section témoignages — hauteur ~280px
- Fond `#FFFFFF`, padding vertical 64px.
- Titre H2 centré : "Ils nous font confiance" — Inter 24px, weight 600.
- Trois cards `TestimonialCard` (4/12 chacune), gap 24px, max-width 1080px.
- Card : fond `#FAFBFC`, bordure 1px `#E5E7EB`, radius 8px, padding 24px, ombre `0 1px 3px rgb(0 0 0 / 0.04)`.
  - Citation Inter 16px italique `#1F2937` (sans guillemets typographiques excessifs).
  - Auteur en bas, Inter 14px weight 500 `#6B7280` : "Salma, 34 ans" / "Mohamed-Ali, 58 ans" / "Ines, 27 ans".
- Citations exactes :
  - "RDV pris en 2 minutes, super pratique pour une urgence dermato."
  - "Très simple à utiliser, je recommande à mes parents."
  - "Le rappel par mail la veille est top, je n'oublie plus aucun RDV."

### Footer — hauteur ~88px
- Fond `#1F2937`, texte `#D1D5DB`, padding vertical 32px.
- Conteneur 1200px, flex horizontal : à gauche liens "À propos · Mentions légales · CGU · Contact" (Inter 14px, séparateurs centrés `·`), à droite "© DocConnect 2026".
- Hover lien : couleur blanche.

### Bouton flottant chatbot
- Position fixed bottom 24px right 24px.
- Cercle 56px fond `#1E5AE8`, icône Lucide `MessageCircle` 24px blanc, ombre `0 8px 24px rgb(0 0 0 / 0.08)` (pas de glow coloré : cf. anti-pattern n°4).
- Au survol : fond `#1947C7` (pas de scale, cf. règle "rien de translaté").
- Focus-visible : outline 2px `#1E5AE8`, offset 2px.
- `aria-label="Ouvrir l'assistant DocConnect"`.

### États
- **Chargement** sur "Rechercher" : bouton devient gris `#9CA3AF`, texte remplacé par `Spinner` 16px + "Recherche...", inputs disabled.
- **Erreur réseau** sur recherche : toast en bas centre, fond `#FEF2F2`, bordure `#FCA5A5`, texte "Connexion impossible. Réessayez dans un instant." + icône `AlertTriangle`.
- **Vide** (saisie incomplète) : bouton submit reste actif, mais focus retourne sur le premier champ vide avec message inline 13px rouge `#B91C1C` : "Indiquez au moins une spécialité ou une ville."

## Variante mobile (<768px)
Header collapse : logo à gauche, icône `Menu` à droite ouvrant un drawer avec "Connexion" et "Inscription" en boutons pleine largeur. Hero : titre 32px, sous-titre 16px, padding vertical 48px. Barre de recherche passe en stack vertical (3 lignes : spécialité, ville, bouton "Rechercher" pleine largeur), hauteur de chaque champ 52px, gap 8px, plus de séparateurs verticaux. Section "Comment ça marche" empile les 3 colonnes verticalement avec gap 32px. Chips spécialités scrollables horizontalement (overflow-x auto, snap), pas de wrap. Témoignages : carrousel horizontal avec dots de pagination centrés. Footer empile les liens verticalement.

## Interactions
- **Click chip spécialité** : pré-remplit le champ "Spécialité" et scrolle vers la barre de recherche, focus posé sur le champ "Ville".
- **Focus input recherche** : bordure `#1E5AE8` (1px), ring 3px `rgb(30 90 232 / 0.15)`.
- **Hover bouton primaire** : fond `#1947C7`, transition 150ms (pas de translateY, cf. règle motion).
- **Submit (Enter ou clic)** : redirige vers `/recherche?specialty=X&city=Y` avec les valeurs URL-encodées.
- **Hover card témoignage** : pas d'effet (card non cliquable).
- **Click chatbot** : ouvre un panneau ancré bottom-right de 380×520px (mobile : sheet plein écran) avec une intro "Bonjour, je peux vous aider à trouver un médecin. Que cherchez-vous ?".

## Accessibilité
- Focus order : skip link "Aller au contenu principal" → logo → Connexion → Inscription → champ spécialité → champ ville → bouton Rechercher → chips → footer.
- Tous les inputs ont un `<label>` visible-hidden lié par `for`/`id` (placeholder seul interdit).
- Bouton "Rechercher" : `aria-label="Rechercher un médecin"`.
- Chatbot flottant : trappe focus quand ouvert, `Escape` ferme et rend focus au bouton.
- Contraste titre `#1F2937` sur `#FAFBFC` = 14.2:1 OK. Texte secondaire `#6B7280` sur `#FAFBFC` = 4.8:1 (AA OK pour 14px+).
- Chips : contour visible 2px `#1E5AE8` au focus clavier (`:focus-visible`).
- Icônes Lucide en décoration : `aria-hidden="true"`. Icônes porteuses de sens : avec `aria-label`.

## Notes de design
On évite délibérément :
- Toute photo stock de médecin en blouse blanche stéthoscope au cou, c'est le marqueur n°1 d'un site de santé "AI-slop" / template Bootstrap 2018.
- Les gradients colorés flashy : on garde un fond presque blanc avec un seul accent bleu, pour signaler le sérieux et la sobriété médicale (codes Doctolib / Qare).
- Les illustrations 3D ou personnages cartoon : icônes Lucide line uniquement, pour la cohérence et la légèreté.

Parti pris : la **barre de recherche est l'unique élément à fort poids visuel** du hero. C'est elle qui doit attirer l'œil en premier — pas un visuel décoratif. Le serif sur le titre (Source Serif) signale l'institutionnel rassurant, le sans-serif (Inter) sur le reste reste lisible et neutre. Témoignages en option : à retirer si les tests utilisateurs montrent une fatigue de scroll, le hero + recherche + 3 étapes + chips suffit largement à convertir.
