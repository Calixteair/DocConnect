# ⚙️ SETUP.md — Installation détaillée

Guide pas-à-pas pour démarrer DocConnect sur un poste vierge. Comptez **20–30 minutes** la première fois (les comptes Firebase / OpenRouter / Mailtrap prennent un peu de temps à créer).

> Pour un démarrage express si tu as déjà toutes les clés, vois le [README.md § Démarrage rapide](README.md#-démarrage-rapide).

---

## 📋 Sommaire

1. [Prérequis machine](#1-prérequis-machine)
2. [Cloner le projet](#2-cloner-le-projet)
3. [Créer `.env.local`](#3-créer-envlocal)
4. [Firebase Authentication](#4-firebase-authentication)
5. [OpenRouter (chatbot)](#5-openrouter-chatbot)
6. [Mailtrap (mails)](#6-mailtrap-mails)
7. [Lancer la stack Docker](#7-lancer-la-stack-docker)
8. [Installer dépendances + DB](#8-installer-dépendances--db)
9. [Provisionner la démo](#9-provisionner-la-démo)
10. [Vérifier que tout marche](#10-vérifier-que-tout-marche)

---

## 1. Prérequis machine

- **Docker** ≥ 24 (Docker Desktop sur Mac/Windows, ou Docker Engine sur Linux)
- **Docker Compose** ≥ 2.20 (intégré à Docker Desktop, sinon `docker-compose-plugin`)
- **Git**
- Un éditeur de texte
- Ports libres : `8080` (app), `8081` (Adminer), `3307` (MariaDB depuis l'hôte)

> Aucune installation locale de PHP, Composer ou Node n'est nécessaire — tout tourne dans Docker.

---

## 2. Cloner le projet

```bash
git clone <repo-url> docconnect
cd docconnect
```

---

## 3. Créer `.env.local`

`.env` est versionné avec les valeurs par défaut. **Ne le modifie pas.** Crée un `.env.local` (gitignored) qui surcharge ce qu'il faut :

```bash
cp .env .env.local
```

Édite `.env.local` et garde uniquement les lignes que tu veux surcharger. Tu vas y mettre, dans les sections suivantes :

```env
# Firebase (sections 4)
FIREBASE_PROJECT_ID=docconnect-xxxxx
FIREBASE_API_KEY=AIza...
FIREBASE_AUTH_DOMAIN=docconnect-xxxxx.firebaseapp.com
FIREBASE_APP_ID=1:1234567890:web:abcdef

# OpenRouter (section 5)
OPENROUTER_API_KEY=sk-or-v1-xxxxx

# Mailtrap (section 6)
MAILER_DSN=smtp://user:pass@sandbox.smtp.mailtrap.io:2525
```

---

## 4. Firebase Authentication

Suivre **[docs/firebase-setup.md](docs/firebase-setup.md)** pour créer le projet Firebase pas-à-pas. Résumé :

1. Aller sur https://console.firebase.google.com → **Ajouter un projet**.
2. Activer **Authentication → Email/Password**.
3. **Paramètres du projet** → **Vos applications** → Web → récupérer la config :
   - `apiKey`, `authDomain`, `projectId`, `appId`
   - Les coller dans `.env.local` (variables `FIREBASE_*` ci-dessus).
4. **Paramètres du projet → Comptes de service** → **Générer une nouvelle clé privée** :
   - Télécharger le JSON.
   - Le placer dans `config/firebase/service-account.json` (gitignored).

> Cette clé est sensible : ne **jamais** la commiter. Le `.gitignore` du projet l'exclut déjà.

---

## 5. OpenRouter (chatbot)

Suivre la doc OpenRouter ou résumé :

1. Créer un compte sur https://openrouter.ai.
2. **Settings → API Keys** → générer une clé.
3. Copier dans `.env.local` :
   ```env
   OPENROUTER_API_KEY=sk-or-v1-xxxxx
   ```
4. Modèle par défaut : `openai/gpt-oss-20b:free` (gratuit). Pour changer, surcharger `OPENROUTER_MODEL` dans `.env.local`.

> ⚠️ Le free tier d'OpenRouter est rate-limité globalement. Si tu as des 429 répétés en démo, prends une clé payante avec quelques dollars de crédit.

---

## 6. Mailtrap (mails)

Suivre **[docs/mailtrap-setup.md](docs/mailtrap-setup.md)** pour créer une inbox Mailtrap sandbox. Résumé :

1. Compte gratuit sur https://mailtrap.io.
2. **Sandbox → Inboxes → My Inbox** → onglet **SMTP Settings** → choisir **Symfony**.
3. Copier le `MAILER_DSN` proposé dans `.env.local` :
   ```env
   MAILER_DSN=smtp://USER:PASS@sandbox.smtp.mailtrap.io:2525
   ```
4. Les mails de confirmation / annulation / rappel apparaîtront dans cette inbox.

> Pas de Mailtrap = `MAILER_DSN=null://null` (les mails partent dans le vide, l'app reste fonctionnelle).

---

## 7. Lancer la stack Docker

```bash
docker compose up -d
```

Cela démarre **4 conteneurs** :

| Conteneur | Image | Rôle |
|---|---|---|
| `docconnect-php` | PHP 8.4 FPM custom | Symfony |
| `docconnect-nginx` | `nginx:1.27-alpine` | Reverse proxy front-controller |
| `docconnect-db` | `mariadb:11.4` | Base de données |
| `docconnect-adminer` | `adminer:4.8.1` | UI DB |

Vérifier :

```bash
docker compose ps
```

Tous les services doivent être `Up` ou `Up (healthy)`.

---

## 8. Installer dépendances + DB

```bash
# Dépendances PHP
docker compose exec php composer install --no-interaction

# Appliquer les migrations
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

> Si tu vois `[OK] Successfully migrated`, c'est bon. Sinon vérifier la `DATABASE_URL` dans `.env`.

---

## 9. Provisionner la démo

```bash
docker compose exec php bin/console app:demo:seed
```

Cette commande **purge la DB**, recharge les fixtures (8 médecins fictifs, 6 spécialités, ~30 créneaux), puis crée 3 comptes Firebase synchronisés avec MariaDB :

| Rôle | Email | Mot de passe |
|---|---|---|
| Patient | `demo@docconnect.tn` | `demo1234` |
| Médecin | `medecin@docconnect.tn` | `medecin1234` |
| Admin | `admin@docconnect.tn` | `admin@docconnect` |

Pour relancer sans purger :

```bash
docker compose exec php bin/console app:demo:seed --keep-db
```

---

## 10. Vérifier que tout marche

| Étape | Action | Attendu |
|---|---|---|
| 1 | Ouvrir http://localhost:8080 | Page d'accueil avec recherche de médecins |
| 2 | Cliquer **Connexion** → `demo@docconnect.tn / demo1234` | Redirection vers l'app |
| 3 | Cliquer **Mes RDV** | Liste vide (le patient démo n'a pas de RDV initial) |
| 4 | Lancer le chatbot (icône en bas à droite) | Réponse en streaming token par token |
| 5 | Se déconnecter, se reconnecter en admin | Bouton **Administration** dans le header |
| 6 | Aller sur http://localhost:8081 → user `docconnect / docconnect / docconnect` | Adminer : tables visibles |

🎉 Tout est bon, tu peux passer au [scénario de démo](docs/demo-script.md).

---

## 🆘 Quelque chose cloche ?

Voir la section [Dépannage du README](README.md#-dépannage) ou ouvrir une issue.
