# 🎤 PRESENTATION.md — Soutenance DocConnect

Document de référence pour la présentation orale : préparation, plan, commandes prêtes à copier, points à pitcher, FAQ jury.

> Pour le **scénario joué** (qui clique sur quoi, dans quel ordre), voir **[docs/demo-script.md](docs/demo-script.md)**.

---

## ⏱️ Pré-soutenance (J-1, 15 min)

```bash
# 1) Stack démarrée
docker compose up -d
docker compose ps     # tout doit être Up

# 2) Comptes démo provisionnés et frais
docker compose exec php bin/console app:demo:seed

# 3) Slot visio prêt dans la fenêtre de démo
# (à ajuster à l'heure réelle du jury, exemple ici pour démo à 14h30)
docker compose exec php bin/console dbal:run-sql \
  "UPDATE slots SET start_at='2026-05-14 14:30:00', end_at='2026-05-14 14:50:00' \
   WHERE id = (SELECT MIN(s.id) FROM (SELECT id FROM slots WHERE mode='VIDEO') s)"

# 4) Mailtrap : ouvrir l'inbox dans un onglet
# https://mailtrap.io/inboxes/<id>/messages

# 5) Vider la file pour avoir une démo propre
# (pas de commande symfony, juste cliquer "Clear all" dans Mailtrap)

# 6) Cache clear pour repartir frais
docker compose exec php bin/console cache:clear
```

**Avant de parler** : 3 onglets ouverts dans le navigateur, profils séparés ou fenêtres privées :
1. 🟦 **Patient** — connecté en `demo@docconnect.tn`, sur la page d'accueil
2. 🟩 **Médecin** — connecté en `medecin@docconnect.tn`, sur `/app/planning`
3. 🟧 **Admin** — connecté en `admin@docconnect.tn`, sur `/admin`

Un 4e onglet **Mailtrap** + un 5e onglet **Adminer** (`localhost:8081`) si jamais on veut montrer la persistance.

---

## 🗺️ Plan de présentation (8 min)

### 1️⃣ Pitch & contexte (45 s)

> « DocConnect est une plateforme de prise de rendez-vous médicaux et téléconsultation. Projet d'école, Symfony 8 + Firebase Auth + MariaDB, données fictives, hors UE — pas de contrainte HDS/RGPD. L'objectif : montrer un flow complet patient/médecin/admin en moins de 8 minutes. »

### 2️⃣ Stack en 30 secondes (30 s)

Montrer le **README.md § Stack technique**. Insister sur :
- Symfony 8 + Twig (pas de SPA — choix assumé pour un MVP école)
- Firebase **uniquement pour l'auth** (pas Firestore comme DB métier)
- OpenRouter en streaming SSE pour le chatbot
- Jitsi `meet.jit.si` en iframe (zéro infra visio à gérer)

### 3️⃣ Démo (5 min) — voir [docs/demo-script.md](docs/demo-script.md)

1. 🏠 Landing + recherche annuaire (45 s)
2. 📇 Fiche médecin + réservation d'un créneau visio (60 s)
3. 📧 Mail de confirmation visible dans Mailtrap (15 s)
4. 🤖 Chatbot : poser une question, montrer le streaming + lien spécialité (45 s)
5. 🩺 Côté médecin : RDV en attente → **Confirmer** → mail reçu (45 s)
6. 🎥 **Rejoindre la visio** des 2 côtés → ils sont dans la même salle Jitsi (45 s)
7. 🛡️ Côté admin : CRUD users + création d'un médecin from scratch (45 s)

### 4️⃣ Choix techniques notables (60 s)

À pitcher si le jury demande « pourquoi » :

| Choix | Justification |
|---|---|
| **Firebase Auth** (vs Symfony Security mots de passe) | Externalise la gestion des mots de passe, MFA, reset email — gain de temps + sécurité. MariaDB ne stocke aucun secret. |
| **Pas de framework JS front** | Twig + Stimulus + Hotwire couvrent tout le périmètre. Aucune complexité de bundler côté front. |
| **Streaming SSE chatbot** | Token-par-token = perception instantanée. Cache symfony 1 h sur question identique → 1 LLM + 2 cache hits sur 3 questions. |
| **Jitsi public `meet.jit.si`** | Zéro infra visio à provisionner. La salle est générée à la confirmation du RDV (`VideoRoomGenerator::ensureFor`). |
| **Hard delete + CASCADE FK** | Projet école → pas de soft delete. Migration `Version20260513215401` passe `appointments.patient_id` en CASCADE pour permettre la suppression admin d'un patient. |
| **Voters Symfony** | `AppointmentVoter` (VIEW/CANCEL/CONFIRM/MARK_DONE) — un patient ne voit que ses RDV, un médecin que les siens, un admin tout. |
| **Atomicité réservation** | `SELECT ... FOR UPDATE` (LockMode::PESSIMISTIC_WRITE) dans `AppointmentBookingService::book()`. Deux patients qui réservent le même créneau → un seul réussit. |

### 5️⃣ Limites & hors scope (30 s)

À mentionner pour montrer qu'on connaît ses propres limites :

- ❌ Pas de paiement (Stripe etc.)
- ❌ Pas d'ordonnance électronique
- ❌ Pas de 2FA (mais Firebase peut l'activer en 1 clic)
- ❌ Pas d'application mobile native
- ❌ Pas de conformité HDS / RGPD strict (projet école hors UE)
- 🟡 Mailer **synchrone** — bloque la requête HTTP, à passer en Messenger async en prod
- 🟡 Profiler Symfony actif en dev — l'app va plus vite en `APP_ENV=prod`

---

## ❓ FAQ jury (probables)

<details>
<summary>« Comment vous gérez la concurrence sur les réservations ? »</summary>

`AppointmentBookingService::book()` ouvre une transaction Doctrine, fait un `SELECT ... FOR UPDATE` sur le `Slot`, vérifie qu'il est `OPEN`, crée l'`Appointment` + marque le slot `BOOKED`, commit. Si 2 onglets tentent de réserver le même créneau, le 2e attend la fin de la transaction puis voit le slot en `BOOKED` et reçoit une `SlotAlreadyBookedException`.
</details>

<details>
<summary>« Pourquoi Firebase et pas Symfony Security natif ? »</summary>

Externaliser les credentials (mot de passe haché, reset email, MFA optionnelle) à un service prouvé. MariaDB ne stocke **aucun mot de passe**. Le `FirebaseAuthenticator` Symfony vérifie l'ID token JWT signé par Google à chaque requête. Coût : dépendance externe + un cookie httpOnly côté serveur pour persister la session.
</details>

<details>
<summary>« Comment le chatbot évite-t-il de donner un faux diagnostic ? »</summary>

Le system prompt strict (`src/Service/Chat/ChatOrchestrator.php`) interdit explicitement le diagnostic. Le LLM est instruit d'orienter vers une des 6 spécialités MVP et de terminer chaque réponse par un lien markdown `[label](/medecins?specialty=...)`. En cas de symptômes d'alerte (douleur thoracique, malaise…), il redirige vers le **190 (SAMU Tunisie)**.
</details>

<details>
<summary>« Pourquoi pas de tests automatisés visibles ? »</summary>

L'effort a été mis sur le périmètre fonctionnel. Quelques tests unitaires sur `AppointmentBookingService` (concurrence) seraient le premier ajout — la structure PHPUnit est en place (`docker compose exec php bin/phpunit`).
</details>

<details>
<summary>« Comment vous protégez contre la prise de contrôle d'un compte par l'admin ? »</summary>

Deux garde-fous :
- **Anti-suicide admin** (`AdminController::userDelete:131`) : un admin ne peut pas supprimer son propre compte depuis l'UI.
- **Anti-dernier-admin** (`UserAdminService::assertNotLastAdmin`) : impossible de rétrograder ou supprimer le dernier ADMIN.
- **Anti-hijack médecin** (`DoctorAdminService::assertEmailNotHijacked`) : créer un médecin avec l'email d'un PATIENT existant lève une `DomainException`.
</details>

<details>
<summary>« Et si Firebase tombe ? »</summary>

L'app exige Firebase pour l'auth (login impossible). Mais les pages publiques (recherche médecins, fiches, à-propos) restent accessibles puisqu'elles ne consultent pas Firebase. Pour une vraie résilience il faudrait un fallback Symfony Security natif — out of scope projet école.
</details>

---

## 🧯 Plan B en cas de bug pendant la démo

| Si… | Alors |
|---|---|
| Le chatbot ne répond pas | Montrer que le cache est en place (`badges debug` à côté des messages) et passer à l'étape suivante |
| Firebase rate-limit (429) | Attendre 30 s, sinon passer en réservation directe sans login (lecture seule sur l'annuaire suffit pour pitcher) |
| Mailtrap ne montre rien | Cliquer **Refresh** dans l'inbox. Sinon, montrer le log : `docker compose logs php | grep "Mailer"` |
| Jitsi demande un mot de passe / lobby | Cliquer **Ask to join** côté 2e onglet, le 1er admet — ça arrive ~1 fois sur 5 sur le free tier |
| L'app rame | Désactiver le profiler : `APP_ENV=prod APP_DEBUG=0 docker compose exec php bin/console cache:clear` |

---

## 📎 Liens internes

- **[README.md](README.md)** — vue d'ensemble + démarrage rapide
- **[SETUP.md](SETUP.md)** — installation pas-à-pas
- **[docs/demo-script.md](docs/demo-script.md)** — scénario joué étape par étape
- **[ROADMAP.md](ROADMAP.md)** — phases du projet
- **[CLAUDE.md](CLAUDE.md)** — conventions internes
