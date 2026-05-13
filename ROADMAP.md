# ROADMAP — DocConnect

Roadmap en **9 phases**. Chaque phase a un **objectif**, des **tâches** (avec marqueur `[//]` = parallélisable via sous-agent), des **livrables**, une **DoD**.

> Pas de deadline → on avance phase par phase, validation utilisateur à chaque jalon.

---

## Phase 0 — Cadrage & maquettes (avant code)

**Objectif** : aligner sur le scope MVP et figer 3-4 écrans clés en maquette avant de coder.

**Tâches**
- [ ] Lister les **user stories MVP** (patient + médecin + admin) — 1 page.
- [ ] Maquetter **4 écrans clés** via `huashu-design` ou `imagegen-frontend-web` :
  - Landing publique + recherche médecin
  - Fiche médecin + calendrier de créneaux
  - Espace patient (mes RDV)
  - Espace médecin (planning de la semaine)
- [ ] Valider la charte (palette, typo) avec un mini *style tile*.

**Livrables** : `docs/user-stories.md`, `docs/mockups/` (4 images), `docs/style-tile.md`.
**DoD** : utilisateur valide les maquettes ou demande des itérations.

---

## Phase 1 — Setup Docker + Symfony

**Objectif** : projet bootable, "hello world" Twig, Adminer accessible.

**Tâches** (largement parallélisables)
- [//] Rédiger `compose.yaml` (php-fpm 8.4, mariadb 11, adminer, nginx).
- [//] Rédiger `Dockerfile` PHP (extensions : `intl`, `pdo_mysql`, `opcache`, `zip`, composer).
- [//] Rédiger `nginx.conf` front-controller Symfony.
- [ ] Init Symfony 8 (`composer create-project symfony/skeleton:^8.0 .` dans le conteneur).
- [ ] Installer packs : `webapp` (Twig, Doctrine, Mailer, Validator), `symfony/ux-stimulus-bundle`, `symfony/asset-mapper`.
- [ ] Configurer `.env` + `.env.local` (DATABASE_URL, MAILER_DSN, APP_SECRET).
- [ ] Page d'accueil minimale `HomeController` + `home.html.twig` (juste "DocConnect" + base layout).
- [ ] Commit initial.

**Livrables** : `compose.yaml`, `docker/Dockerfile`, `docker/nginx.conf`, projet Symfony bootstrap.
**DoD** :
- `docker compose up -d` → `http://localhost` affiche la page d'accueil.
- `http://localhost:8081` → Adminer connecté à `db:3306` (root/root en dev).
- `bin/console about` répond sans erreur.

---

## Phase 2 — Authentification Firebase

**Objectif** : un user peut s'inscrire/se connecter via Firebase et Symfony reconnaît son identité.

**Tâches**
- [ ] Créer un projet Firebase + activer **Email/Password** auth (manuel, je guide).
- [ ] Récupérer **clé service account** (JSON) → `config/firebase/service-account.json` (gitignored).
- [ ] Installer `kreait/firebase-bundle`.
- [ ] Coder `FirebaseAuthenticator` (Security Bundle custom authenticator) :
  - lit le header `Authorization: Bearer <idToken>` ou un cookie httpOnly,
  - vérifie le token via Firebase Admin SDK,
  - charge/crée l'entité `User` côté MariaDB par `firebase_uid`.
- [ ] Entité `User` minimale (id, firebase_uid, email, role, created_at).
- [ ] Pages **login** et **signup** Twig avec Firebase JS SDK :
  - signup → crée user Firebase + POST `/auth/sync` pour créer la row MariaDB,
  - login → récupère idToken → stocké en cookie httpOnly via endpoint Symfony.
- [ ] `LogoutController` (clear cookie + Firebase signOut côté front).
- [ ] Middleware : routes `/app/**` exigent un user authentifié.

**Parallélisable**
- [//] Pendant que je code l'authenticator PHP → un agent rédige `docs/firebase-setup.md` (pas-à-pas).

**Livrables** : auth fonctionnelle, doc setup.
**DoD** : je peux m'inscrire, me déconnecter, me reconnecter ; un user non connecté est redirigé depuis `/app/*`.

---

## Phase 3 — Modèle de données métier

**Objectif** : entités Doctrine complètes, migrations, fixtures de démo.

**Entités**
- `User` (étendu : nom, prénom, téléphone, role enum `PATIENT|DOCTOR|ADMIN`)
- `Patient` (1-1 avec User côté `PATIENT`) — date de naissance, sexe, allergies (text)
- `Doctor` (1-1 avec User côté `DOCTOR`) — bio, numéro RPPS (factice), tarif, langues
- `Specialty` (id, label, slug) — many-to-many avec Doctor
- `Address` (rue, ville, code postal, lat/lng) — 1-N avec Doctor (cabinets)
- `Slot` (doctor_id, start_at, end_at, status `OPEN|BOOKED|BLOCKED`, mode `PHYSICAL|VIDEO`)
- `Appointment` (slot_id 1-1, patient_id, motif text, status `PENDING|CONFIRMED|CANCELLED|DONE`, created_at)
- `ChatSession` (user_id, started_at) + `ChatMessage` (session_id, role `USER|ASSISTANT|SYSTEM`, content, tokens, created_at)

**Tâches**
- [//] Scaffold entité `Patient` (agent #1)
- [//] Scaffold entité `Doctor` + `Specialty` + `Address` (agent #2)
- [//] Scaffold entité `Slot` + `Appointment` (agent #3)
- [//] Scaffold entité `ChatSession` + `ChatMessage` (agent #4)
- [ ] **Voters** : `AppointmentVoter` (vue/annulation), `SlotVoter` (médecin propriétaire).
- [ ] **Fixtures** (`doctrine-fixtures-bundle`) : 8 médecins, 6 spécialités, 30 créneaux, 5 patients démo.
- [ ] Migration initiale + relancer `migrate`.
- [ ] Index DB : `slot(doctor_id, start_at)`, `appointment(patient_id, status)`.

**Livrables** : schéma DB stable, jeu de données démo riche.
**DoD** :
- `doctrine:schema:validate` OK.
- Adminer montre toutes les tables avec leurs FK.
- `bin/console doctrine:fixtures:load --no-interaction` repeuple en < 5s.

---

## Phase 4 — Annuaire & recherche médecin

**Objectif** : un patient peut chercher un médecin et voir sa fiche.

**Tâches**
- [ ] **Landing** publique : hero clair + barre de recherche (spécialité + ville).
- [ ] Page **résultats** `/medecins?specialty=...&city=...` :
  - liste cards médecins (photo placeholder, nom, spécialité, ville, prochain créneau),
  - pagination (12/page),
  - skeleton pendant le fetch.
- [ ] Page **fiche médecin** `/medecin/{slug}` :
  - infos pro, bio, cabinets, tarif,
  - **calendrier de la semaine** avec créneaux cliquables (Stimulus controller),
  - navigation semaine précédente / suivante,
  - bouton "Prendre rendez-vous" sur créneau → ouvre modal de confirmation.

**Parallélisable** (UI atomiques)
- [//] Twig component `_doctor-card.html.twig` (agent #1, applique skill `impeccable` + charte)
- [//] Twig component `_slot-grid.html.twig` (agent #2)
- [//] Twig component `_search-bar.html.twig` (agent #3)

**Livrables** : 3 routes publiques navigables, design conforme charte.
**DoD** : DoD générale + recherche fonctionne sur 2 critères + accessibilité clavier sur grille créneaux.

---

## Phase 5 — Prise de RDV (cœur fonctionnel)

**Objectif** : un patient peut réserver un créneau ; un médecin voit ses RDV à valider.

**Tâches**
- [ ] **Service `AppointmentBookingService`** :
  - transaction DB avec `SELECT ... FOR UPDATE` sur le Slot,
  - vérifie `Slot.status = OPEN`,
  - crée `Appointment` + passe Slot à `BOOKED`,
  - throw `SlotAlreadyBookedException` sinon.
- [ ] Endpoint `POST /app/appointments` (patient).
- [ ] Endpoint `PATCH /app/appointments/{id}/cancel` (patient ou médecin).
- [ ] Endpoint `PATCH /app/appointments/{id}/confirm` (médecin).
- [ ] **Espace patient** `/app/mes-rdv` : liste RDV à venir + passés, statut, bouton annuler.
- [ ] **Espace médecin** `/app/planning` : agenda de la semaine, RDV à valider en haut.
- [ ] Choix **physique vs visio** dans le formulaire de réservation.

**Parallélisable**
- [//] Twig de l'espace patient (agent #1)
- [//] Twig de l'espace médecin (agent #2)
- [//] Tests unitaires du service de booking (agent #3) — happy path + double-booking race

**Livrables** : flow complet réservation/annulation/confirmation.
**DoD** : 2 onglets navigateur tentent de réserver le même créneau → un seul réussit (test manuel reproductible).

---

## Phase 6 — Chatbot OpenRouter ✅

**Objectif** : chatbot d'orientation accessible depuis l'espace patient.

**Livré**
- `ChatOrchestrator` chaîne **IntentMatcher → cache Symfony (sha256 question, TTL 1 h) → LLM OpenRouter**, avec mode streaming SSE token par token.
- Modèle par défaut : `openai/gpt-oss-20b:free` (switchable via `OPENROUTER_MODEL`). Llama 3.3 / Gemma free trop saturés.
- System prompt strict : pas de diagnostic, oriente vers les 6 spécialités MVP, redirection 190 (SAMU Tunisie) en cas de signaux d'alerte.
- **Liens vers l'app** : le LLM est instruit de terminer chaque orientation par un lien markdown `[label](/medecins?specialty=...)`. Le widget les rend en bouton bleu cliquable qui amène l'utilisateur sur la page de recherche pré-filtrée. Pas d'exécution d'action côté chat (pas de tool calling) — c'est volontaire pour rester safe et démo-friendly.
- Rate limit : 10 messages / min / user via `framework.rate_limiter` token bucket.
- Endpoints `POST /api/chat/stream` (SSE), `POST /api/chat/message` (non-stream), `GET /api/chat/history`.
- Widget : FAB bottom-right, panneau 380×520 desktop / fullscreen mobile, historique scrollable, indicateur "écrit…" qui disparaît au premier chunk, markdown léger (gras/italique/listes/liens internes), badges debug `intent`/`cache`/`llm`.
- Profiler Symfony désactivé sur la route stream (sinon il bufferise toute la réponse, ruinant le SSE). `fastcgi_buffering off` côté nginx, padding initial 2 ko + `ob_implicit_flush` côté PHP.
- Persistence `ChatSession` + `ChatMessage` en MariaDB pour reprendre la conversation.

**DoD validée** : 3 questions identiques → 1 LLM + 2 cache (visible via badges). Streaming token-par-token fonctionnel. Liens vers spécialités générés et cliquables.

---

## Phase 7 — Notifications mail

**Objectif** : confirmation + rappel + annulation par mail.

**Tâches**
- [ ] Mailtrap account → `MAILER_DSN` dans `.env.local`.
- [ ] **Templates Twig** `templates/email/` :
  - `appointment_confirmed.html.twig` + `.txt.twig`
  - `appointment_reminder.html.twig` + `.txt.twig`
  - `appointment_cancelled.html.twig` + `.txt.twig`
- [ ] **Listeners** Doctrine sur Appointment :
  - postPersist (status=CONFIRMED) → mail patient + médecin,
  - postUpdate (status=CANCELLED) → mail des deux parties.
- [ ] **Commande** `app:appointments:remind` :
  - envoie un rappel 24h avant le RDV,
  - à lancer via cron (en dev : manuel ; en démo : on lance à la main).
- [ ] Design mail : sobre, mono-colonne, max 600px, logo en header, CTA "Voir mon RDV".

**Parallélisable**
- [//] 3 templates HTML en parallèle (agent par mail, skill `impeccable` brand)

**Livrables** : 3 types de mails fonctionnels visibles dans Mailtrap.
**DoD** : réserver un RDV → mail visible dans Mailtrap, formaté, lien fonctionnel.

---

## Phase 8 — Visio Jitsi

**Objectif** : un RDV en mode `VIDEO` ouvre une salle Jitsi à l'heure dite.

**Tâches**
- [ ] Génération nom de salle : `docconnect-<appointment_id>-<random>` au moment du booking.
- [ ] Stocker `Appointment.video_room` (string nullable).
- [ ] Page `/app/visio/{appointmentId}` :
  - vérifie via Voter que l'user est patient OU médecin du RDV,
  - vérifie que l'heure est dans la fenêtre `[start - 10min, end + 30min]`,
  - iframe Jitsi `https://meet.jit.si/<room>` plein écran avec sidebar latérale (infos RDV, bouton "raccrocher").
- [ ] Bouton "Rejoindre la consultation" dans l'espace patient/médecin (visible 10 min avant).

**Livrables** : flow visio bout-en-bout.
**DoD** : 2 navigateurs (un patient, un médecin) entrent dans la même salle, audio+video fonctionnent.

---

## Phase 9 — Polish & démo

**Objectif** : projet propre, démo-ready.

**Tâches**
- [ ] **Pass design final** sur toutes les pages : skill `impeccable` mode `polish` page par page.
- [ ] **404 + 500 + accès refusé** custom Twig.
- [ ] **Page "À propos"** + footer minimal.
- [ ] **Seed démo** scriptée (`make demo-data`) : 1 patient `demo@docconnect.tn / demo1234`, 1 médecin `medecin@docconnect.tn / demo1234`.
  - **Synchro Firebase** : commande qui crée ces 2 comptes côté Firebase Auth (via `Kreait\Firebase\Auth::createUser`) **et** côté MariaDB, puis lie le User médecin à un Doctor existant des fixtures (ex. `dr-aymen-ben-ali`). C'est la seule façon d'avoir une démo où le médecin peut se logger et confirmer un vrai RDV.
  - Les fixtures actuelles génèrent uniquement des `firebase_uid` factices (préfixe `fixture-`) pour peupler l'annuaire — ces comptes ne peuvent pas se connecter via Firebase.
- [ ] **README projet** : prérequis, `docker compose up`, accès, identifiants démo.
- [ ] **Scénario de démo** rédigé (`docs/demo-script.md`) :
  1. Landing → recherche
  2. Réservation
  3. Mail confirmation (Mailtrap)
  4. Chatbot
  5. Espace médecin → confirmation
  6. Visio
- [ ] Pass `/simplify` global.
- [ ] Pass `snyk_code_scan` global.
- [ ] Capture vidéo (optionnelle) ou screenshots pour le rendu.

**Livrables** : projet présentable.
**DoD** : un jury suit le scénario de démo en 8 min sans accroc.

---

## Hors scope (volontairement)

- App mobile native.
- Paiement (Stripe, etc.).
- Ordonnance électronique.
- Tiers payant / sécurité sociale.
- Multilingue.
- Push notifications navigateur.
- 2FA.
- Conformité HDS / RGPD strict.

Si le temps le permet après Phase 9 : **dashboard admin** (modération médecins, stats simples) en bonus.

---

## Index des phases

| # | Phase | Statut |
|---|---|---|
| 0 | Cadrage & maquettes | ✅ |
| 1 | Setup Docker + Symfony | ✅ |
| 2 | Auth Firebase | ✅ |
| 3 | Modèle de données | ✅ |
| 4 | Annuaire & recherche | ✅ |
| 5 | Prise de RDV | ✅ |
| 6 | Chatbot OpenRouter | ✅ |
| 7 | Notifications mail | ✅ |
| 8 | Visio Jitsi | ⬜ |
| 9 | Polish & démo | ⬜ |

**Prochaine étape proposée** : Phase 0 — je sors les user stories MVP et 4 maquettes pour validation, **ou** on saute direct en Phase 1 (Setup Docker) si tu préfères voir du code tout de suite.
