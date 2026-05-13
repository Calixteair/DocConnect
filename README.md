# 🩺 DocConnect

> Plateforme de prise de rendez-vous médicaux avec téléconsultation, annuaire et chatbot d'orientation. Projet d'école — données fictives.

![Symfony](https://img.shields.io/badge/Symfony-8.0-000000?logo=symfony) ![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php) ![MariaDB](https://img.shields.io/badge/MariaDB-11.4-003545?logo=mariadb) ![Docker](https://img.shields.io/badge/Docker_Compose-✓-2496ED?logo=docker) ![Firebase](https://img.shields.io/badge/Firebase_Auth-FFCA28?logo=firebase) ![License](https://img.shields.io/badge/license-school_project-lightgrey)

---

## 📑 Sommaire

- [✨ Fonctionnalités](#-fonctionnalités)
- [🚀 Démarrage rapide](#-démarrage-rapide)
- [🔑 Comptes de démonstration](#-comptes-de-démonstration)
- [🏗️ Architecture](#️-architecture)
- [🧰 Stack technique](#-stack-technique)
- [📜 Commandes utiles](#-commandes-utiles)
- [🩹 Dépannage](#-dépannage)
- [📚 Documentation](#-documentation)

---

## ✨ Fonctionnalités

| Côté patient | Côté médecin | Côté admin |
|---|---|---|
| 🔍 Recherche par spécialité + ville | 📅 Planning hebdomadaire | 👥 CRUD complet utilisateurs |
| 📇 Fiches médecins (bio, tarif, langues) | ✅ Confirmer / refuser / terminer un RDV | 🩺 CRUD complet médecins |
| 🗓️ Réservation atomique d'un créneau | ❌ Annuler à tout moment | 🎭 Changement de rôle |
| 📧 Mail de confirmation + rappel J-1 | 🎥 Visio Jitsi intégrée | 🛡️ Garde-fou « dernier admin » |
| 🤖 Chatbot d'orientation (OpenRouter SSE) | 📨 Notifications mail patient | 🔥 Sync Firebase Auth |
| 👤 Gestion de compte + mdp Firebase | | |
| 🎥 Téléconsultation depuis l'espace patient | | |

---

## 🚀 Démarrage rapide

> **Prérequis** : Docker + Docker Compose. C'est tout.

```bash
# 1) Cloner et entrer dans le projet
git clone <repo-url> docconnect && cd docconnect

# 2) Copier la config locale (Firebase, OpenRouter, Mailtrap — voir SETUP.md)
cp .env .env.local
# … puis renseigner FIREBASE_*, OPENROUTER_API_KEY, MAILER_DSN dans .env.local

# 3) Démarrer la stack (php-fpm, nginx, mariadb, adminer)
docker compose up -d

# 4) Installer les dépendances et préparer la base
docker compose exec php composer install --no-interaction
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# 5) Provisionner la démo (fixtures + comptes Firebase patient/médecin/admin)
docker compose exec php bin/console app:demo:seed
```

🎉 **C'est prêt** :

| Service | URL | Notes |
|---|---|---|
| Application | http://localhost:8080 | Front Symfony + Twig |
| Adminer | http://localhost:8081 | DB : `docconnect / docconnect` |
| MariaDB (host) | `localhost:3307` | depuis l'hôte, pas le conteneur |

> Pour la config détaillée (Firebase Console, clés OpenRouter, Mailtrap), suivre **[SETUP.md](SETUP.md)**.

---

## 🔑 Comptes de démonstration

Provisionnés par `app:demo:seed` (Firebase + MariaDB synchronisés) :

| Rôle | Email | Mot de passe |
|---|---|---|
| 👤 Patient | `demo@docconnect.tn` | `demo1234` |
| 🩺 Médecin | `medecin@docconnect.tn` | `medecin1234` |
| 🛡️ Admin | `admin@docconnect.tn` | `admin@docconnect` |

> ⚠️ Identifiants **démo uniquement**. Ne jamais utiliser en production.

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                          Navigateur                              │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Twig + Stimulus + Firebase JS SDK (auth seulement)      │    │
│  └─────────────────────────────────────────────────────────┘    │
└───────────────┬───────────────────────────┬─────────────────────┘
                │ HTTP                       │ EventSource (SSE)
                ▼                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    nginx (port 8080) → php-fpm                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Symfony 8                                                │   │
│  │  • FirebaseAuthenticator (vérifie ID token httpOnly)     │   │
│  │  • Controllers + Voters (AppointmentVoter, ROLE_ADMIN)   │   │
│  │  • Services (Booking, ChatOrchestrator, Mailer, Visio…)  │   │
│  └──────────────────┬─────────────────────┬─────────────────┘   │
└─────────────────────┼─────────────────────┼─────────────────────┘
                      │                     │
              ┌───────▼────────┐   ┌────────▼──────────────┐
              │  MariaDB 11.4  │   │  Services externes    │
              │  (Doctrine)    │   │  • Firebase Auth      │
              │                │   │  • OpenRouter (LLM)   │
              │  Adminer:8081  │   │  • Mailtrap (mail)    │
              └────────────────┘   │  • Jitsi (visio)      │
                                   └───────────────────────┘
```

**Source de vérité utilisateur** : Firebase = identité (email, UID, mot de passe). MariaDB = données métier (Patient/Doctor/Appointment/…), reliées au `firebase_uid` stocké dans `users.firebase_uid`.

Pour plus de détails (entités, flux de réservation, sécurité), voir `CLAUDE.md` et `ROADMAP.md`.

---

## 🧰 Stack technique

- **Backend** : Symfony 8 (PHP 8.4), Doctrine ORM 3
- **Templating** : Twig + Stimulus (Hotwire)
- **Base de données** : MariaDB 11.4
- **Authentification** : Firebase Authentication (front JS SDK + back `kreait/firebase-bundle`)
- **Visio** : Jitsi Meet (iframe `meet.jit.si`)
- **Chatbot** : OpenRouter (LLM as a service) avec streaming Server-Sent Events
- **Mail** : Symfony Mailer + Mailtrap (sandbox dev)
- **Conteneurisation** : Docker Compose (php-fpm, nginx, mariadb, adminer)

> 🔍 Hors stack volontairement : Laravel, framework SPA front (React/Vue/Angular), Ollama local. Twig + Stimulus suffisent pour le périmètre.

---

## 📜 Commandes utiles

```bash
# Démarrer / arrêter la stack
docker compose up -d
docker compose down

# Logs en suivi
docker compose logs -f php

# Console Symfony
docker compose exec php bin/console <cmd>

# Migrations
docker compose exec php bin/console doctrine:migrations:diff
docker compose exec php bin/console doctrine:migrations:migrate

# Re-provisionner la démo (purge + seed Firebase)
docker compose exec php bin/console app:demo:seed

# Sans purger la DB (mise à jour des comptes Firebase uniquement)
docker compose exec php bin/console app:demo:seed --keep-db

# Cache clear
docker compose exec php bin/console cache:clear

# Tests
docker compose exec php bin/phpunit
```

---

## 🩹 Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| `localhost:8080` répond 404 sur toutes les routes | nginx pas démarré ou conflit de port | `docker compose ps` + libérer le port 8080 |
| Erreur `firebase_uid invalid` au login | `.env.local` Firebase incomplet | Voir [docs/firebase-setup.md](docs/firebase-setup.md) |
| Le chatbot ne répond pas (timeout) | `OPENROUTER_API_KEY` manquant | Renseigner dans `.env.local` puis `cache:clear` |
| Pas de mail à la réservation | `MAILER_DSN` = `null://null` | Configurer Mailtrap, voir [docs/mailtrap-setup.md](docs/mailtrap-setup.md) |
| Bouton « Rejoindre la visio » invisible | Hors fenêtre `[T-10min, T+endAt+30min]` | Le slot doit être dans le futur proche |
| Salles Jitsi différentes patient/médecin | Bug Phase 8 (corrigé) | `git pull` à jour, `VideoRoomGenerator::ensureFor()` persiste maintenant |
| `Cannot delete user` côté admin | Migration FK CASCADE pas appliquée | `bin/console doctrine:migrations:migrate` |

---

## 📚 Documentation

- **[SETUP.md](SETUP.md)** — installation détaillée pas-à-pas
- **[PRESENTATION.md](PRESENTATION.md)** — plan de soutenance + commandes prêtes
- **[ROADMAP.md](ROADMAP.md)** — phases du projet et état d'avancement
- **[CLAUDE.md](CLAUDE.md)** — guide pour assistants IA (Claude Code) et conventions du projet
- **[docs/firebase-setup.md](docs/firebase-setup.md)** — création du projet Firebase, service account, clés JS
- **[docs/mailtrap-setup.md](docs/mailtrap-setup.md)** — config Mailtrap sandbox
- **[docs/demo-script.md](docs/demo-script.md)** — scénario de démo joué étape par étape
- **[docs/user-stories.md](docs/user-stories.md)** — user stories MVP
- **[docs/style-tile.md](docs/style-tile.md)** — charte visuelle

---

<div align="center">

Projet réalisé dans le cadre d'un cursus d'ingénierie informatique.
Symfony 8 · Twig · MariaDB · Firebase · OpenRouter · Jitsi.

</div>
