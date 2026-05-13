# CLAUDE.md — DocConnect

Ce fichier est lu par Claude Code à chaque session sur ce projet. Il décrit la stack, les conventions, les skills à activer, et la définition de "fini" pour chaque livraison.

---

## 1. Pitch

**DocConnect** = plateforme web de **prise de rendez-vous médicaux** (patient ↔ médecin) avec :

- annuaire et recherche de médecins,
- réservation de créneaux,
- **téléconsultation** via visio embarquée,
- **chatbot d'orientation** (pré-tri symptômes, FAQ, choix de spécialité),
- notifications mail (confirmation, rappel, annulation).

**Contexte** : projet d'école, **hors UE**, pas de contrainte RGPD/HDS. Données fictives.

**Cible visuelle** : médical sobre et rassurant, dans l'esprit Doctolib (blanc dominant, bleu doux), **sans le look "généré par IA"** : typographie soignée, espacements généreux, hiérarchie nette, aucune emoji-soup, aucun gradient violet/rose.

---

## 2. Stack imposée

| Couche | Choix |
|---|---|
| Backend | **Symfony 8** (PHP 8.4) |
| Templating | **Twig** + Stimulus pour les interactions |
| Base de données | **MariaDB 11** (compatible MySQL) |
| Admin DB | **Adminer** |
| Authentification | **Firebase Authentication** (front JS SDK + backend `kreait/firebase-bundle`) |
| Chatbot | **OpenRouter** (LLM via API) avec streaming SSE |
| Mailer | **Symfony Mailer** + Mailtrap en dev |
| Visio | **Jitsi Meet** (iframe embed, salle générée par RDV) |
| Conteneurisation | **Docker Compose** (php-fpm, mariadb, adminer, nginx) |

**Hors stack** (à ne pas proposer) : Laravel, Auth0/Supabase/Keycloak côté auth, Firebase Firestore comme DB métier (Firebase ne sert **que** pour l'auth), Ollama local, frontend SPA (pas de React/Vue/Angular — Twig + Stimulus suffit).

---

## 3. Architecture en deux mots

```
Browser
  ├─ Firebase JS SDK ──► login/signup ──► retourne un ID token (JWT signé Google)
  ├─ Twig pages (rendues par Symfony) + Stimulus controllers
  └─ EventSource (SSE) ──► /api/chat/stream ──► OpenRouter

Symfony 8 (php-fpm)
  ├─ FirebaseAuthenticator : vérifie l'ID token à chaque requête, hydrate User
  ├─ Controllers (HomeController, AppointmentController, DoctorController, ChatController…)
  ├─ Voters : un patient ne voit que SES RDV, un médecin que les siens
  ├─ Mailer : confirmations / rappels / annulations
  └─ Doctrine ORM ──► MariaDB

Adminer ──► MariaDB (port 8080 en dev)
```

**Source de vérité utilisateur** : Firebase = identité (email, UID, mot de passe). MariaDB = données métier (`Patient`/`Doctor`/`Appointment`…), liées au `firebase_uid` stocké dans `users.firebase_uid`.

---

## 4. Conventions de code

### PHP / Symfony

- PHP **8.4**, types stricts (`declare(strict_types=1);` en tête de chaque fichier).
- Entités Doctrine dans `src/Entity/`, repos dans `src/Repository/`, controllers dans `src/Controller/`.
- **Pas de logique métier dans les controllers** : extraire en `src/Service/`.
- Fonctions > 30 lignes = signal de découpage.
- Pas de `mixed`/`array` shape opaque sans typage : préférer DTOs ou objets de valeur.
- Async / opérations externes (OpenRouter, Mailer, Firebase) toujours en try/catch avec log structuré (`monolog`).

### Twig

- **Components Twig** réutilisables dans `templates/components/` : `_button.html.twig`, `_card.html.twig`, `_avatar.html.twig`, `_slot-picker.html.twig`, etc.
- Pas de logique métier en Twig — uniquement présentation.
- Tous les formulaires passent par Symfony Forms (CSRF gratuit).
- i18n FR uniquement (pas de traduction prévue).

### Front

- **Stimulus** par feature (`assets/controllers/chat_controller.js`, `appointment_controller.js`…).
- Tailwind CSS **non requis** — design tokens CSS custom dans `assets/styles/tokens.css` (couleurs, espacements, typo), puis classes utilitaires maison ou CSS modulaire. Décision finale au scaffold (`@symfony/ux-turbo` + CSS custom > Tailwind pour un projet école sobre).
- **EventSource** pour le stream chatbot.
- **Pas** de framework JS (pas de React/Vue).

### Naming

- Branches : `feat/<slug>`, `fix/<slug>`, `chore/<slug>`.
- Commits : convention courte FR, ex. `feat(rdv): réservation atomique avec verrou`, `fix(chat): gestion erreur OpenRouter`.

---

## 5. Design — règles non négociables

**Toute tâche UI** déclenche la stack design dans cet ordre (cf. CLAUDE.md global) :

1. **`impeccable` (mode `product`)** = premier réflexe. Anti-AI-slop, distillation, typographie, espacements.
2. **`frontend-design`** ou **`ui-ux-pro-max`** pour le craft / patterns / composants.
3. **`huashu-design`** uniquement si on prototype un écran complexe avant Twig (rare).

### Charte visuelle DocConnect

- **Palette** : blanc cassé `#FAFBFC` fond, bleu primaire `#1E5AE8` (calme, médical, pas turquoise), gris texte `#1F2937` / `#6B7280`, succès `#059669`, erreur `#DC2626`. Une seule couleur d'accent — pas de second bleu.
- **Typo** : Inter (UI) + une serif sobre type Source Serif pour les titres de fiches médecin (différenciation éditoriale légère). Variable font, weights 400/500/600/700 max.
- **Espacements** : échelle 4/8/12/16/24/32/48/64. Pas de magic numbers.
- **Radius** : 8px (cards), 6px (boutons), 999px (avatars/pills).
- **Ombres** : très subtiles, une seule par composant max (`0 1px 3px rgb(0 0 0 / 0.04)`).
- **Pas de** : gradients tape-à-l'œil, glassmorphism, néon, emojis dans l'UI prod, illustrations 3D génériques.

### Patterns UI

- **Mobile-first**, breakpoints `sm 640 / md 768 / lg 1024 / xl 1280`.
- **États systématiques** par composant : default, hover, focus-visible (contour 2px bleu), active, disabled, loading, empty, error.
- **Skeletons** sur tout fetch > 200ms (recherche médecins, chargement créneaux).
- **Microcopy** humaine : "Aucun créneau libre cette semaine — essayez la semaine suivante." pas "No data".
- **Accessibilité** : contraste AA min, focus visibles, `aria-*` sur composants custom, navigation clavier complète sur le calendrier de RDV.

### Définition de "beau" pour ce projet

> Si un médecin de 55 ans peut prendre un RDV sans hésiter et si un jury design passe l'écran sans lever un sourcil "ça pue l'IA", c'est gagné.

---

## 6. Orchestration des sous-agents

Sur ce projet, **je code tout** ; l'utilisateur valide scope et UX. Je parallélise via le tool `Agent` dès qu'une tâche se décompose en travaux **indépendants** :

**Cas où je parallélise** :
- Scaffold simultané de plusieurs entités Doctrine sans dépendance croisée.
- Création de plusieurs Twig components UI atomiques.
- Recherche/exploration multi-fichiers (subagent `Explore` ou `general-purpose`).
- Audit sécu (`firstparty-secret-scanner` ou `snyk_code_scan`) pendant que j'écris du code ailleurs.

**Cas où je reste séquentiel** :
- Toute chaîne avec dépendance réelle (entité avant repo avant service avant controller).
- Modifications du `compose.yaml` ou de la config Symfony (un seul écrivain).

Briefer chaque agent comme un collègue qui débarque : but, contexte, contraintes, format de retour court.

---

## 7. Sécurité — minimal mais pas zéro

Projet école, on garde :

- **CSRF** sur tous les formulaires (gratuit via Symfony Forms).
- **Voters Symfony** pour toute lecture RDV / fiche patient.
- **Rate limit** sur `/api/chat/*` (10 req/min/user via `symfony/rate-limiter`) — sinon démo = facture OpenRouter.
- **Validation** : Symfony Validator sur tous les inputs (Assert\NotBlank, Email, Length…).
- **Secrets** : `.env.local` (gitignored) + `secrets:set` Symfony en prod. **Jamais** de clé Firebase/OpenRouter en clair dans Git ou un `.md`.
- **Snyk** : `snyk_code_scan` après tout gros bloc PHP/JS first-party. Corriger jusqu'à zéro nouveau finding.

On ne fait **pas** : 2FA, audit log complet, chiffrement applicatif des données médicales, gestion HDS, RGPD complet.

---

## 8. Définition de "fini" (DoD) par feature

Avant de marquer une tâche `completed` :

- [ ] **Happy path** testé manuellement dans le navigateur.
- [ ] **1 edge case** testé (input vide, conflit, erreur réseau…).
- [ ] **Mobile** check à 375px de large.
- [ ] **Accessibilité** : tab order OK, focus visible, contraste AA.
- [ ] **`/simplify`** sur le diff si > 50 lignes nouvelles.
- [ ] **`snyk_code_scan`** sur le code first-party PHP/JS ajouté.
- [ ] **Pas de secret** dans le diff (grep préventif).
- [ ] **Commit** en français, message décrivant le *pourquoi* pas seulement le *quoi*.

---

## 9. Commandes utiles (réflexes)

```bash
# Démarrer la stack
docker compose up -d

# Logs Symfony
docker compose logs -f php

# Console Symfony
docker compose exec php bin/console <cmd>

# Migrations
docker compose exec php bin/console doctrine:migrations:diff
docker compose exec php bin/console doctrine:migrations:migrate

# Cache clear
docker compose exec php bin/console cache:clear

# Adminer
open http://localhost:8081

# Tests
docker compose exec php bin/phpunit
```

---

## 10. Liens internes

- `ROADMAP.md` — phases, jalons, tâches détaillées.
- `docs/architecture.md` — diagramme + décisions techniques (à créer en Phase 1).
- `docs/firebase-setup.md` — pas-à-pas projet Firebase + service account (à créer en Phase 2).

---

**Rappel** : si une décision sort de ce cadre, je le signale à l'utilisateur **avant** d'écrire le code.
