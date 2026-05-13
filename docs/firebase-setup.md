# Firebase Setup — DocConnect

Ce guide vous amène de zéro à une authentification Firebase fonctionnelle pour DocConnect. À la fin, vous aurez :

1. Un projet Firebase avec **Email/Password** activé.
2. Une **clé service account** placée dans `config/firebase/service-account.json`.
3. La config web Firebase renseignée dans `.env.local`.

Firebase ne sert qu'à **l'authentification** sur ce projet. Aucune donnée métier n'y est stockée — tout est en MariaDB.

---

## Pré-requis

- Un compte Google (n'importe quel Gmail fait l'affaire — un compte perso suffit pour un projet école).
- Le repo DocConnect cloné localement.
- Accès en écriture au dossier du projet.

---

## Étape 1 — Créer le projet Firebase

1. Aller sur https://console.firebase.google.com.
2. Cliquer sur **"Ajouter un projet"**.
3. Nom du projet : `docconnect-dev` (ou `docconnect-<votre-nom>` si plusieurs étudiants travaillent dessus). Firebase ajoutera un suffixe aléatoire pour garantir l'unicité — c'est normal.
4. À l'écran **Google Analytics**, **désactiver** l'option. C'est inutile pour une app d'auth école et ça évite de devoir lier un compte Analytics.
5. Cliquer sur **"Créer le projet"** et attendre la fin du provisionnement (~30 secondes).
6. Cliquer sur **"Continuer"** pour arriver sur la console du projet.

---

## Étape 2 — Activer Email/Password

1. Dans le menu latéral gauche, ouvrir **"Build" → "Authentication"**.
2. Cliquer sur **"Get started"** (ou **"Commencer"**).
3. Aller dans l'onglet **"Sign-in method"** (ou **"Méthode de connexion"**).
4. Dans la liste des fournisseurs, cliquer sur **"Email/Password"**.
5. Activer le premier toggle **"Email/Password"**.
6. **Laisser désactivé** le second toggle **"Email link (passwordless sign-in)"** — DocConnect utilise un mot de passe classique.
7. Cliquer sur **"Save"**.

À ce stade, votre projet accepte les inscriptions email + mot de passe. La page **Users** est vide, c'est normal.

---

## Étape 3 — Ajouter une web app

Cette étape génère la config publique (apiKey, authDomain…) que le navigateur utilisera côté front.

1. Cliquer sur la **roue dentée** en haut à gauche → **"Project settings"** (**"Paramètres du projet"**).
2. Descendre jusqu'à la section **"Your apps"** (**"Vos applications"**).
3. Cliquer sur l'icône **`</>`** (Web).
4. Surnom de l'app : `docconnect-web`.
5. **NE PAS** cocher **"Also set up Firebase Hosting"**. Nous servons Symfony, pas Firebase.
6. Cliquer sur **"Register app"**.
7. Firebase affiche un bloc de code contenant un objet `firebaseConfig` qui ressemble à ceci :

   ```js
   const firebaseConfig = {
     apiKey: "AIzaSyA...",
     authDomain: "docconnect-dev-xxxxx.firebaseapp.com",
     projectId: "docconnect-dev-xxxxx",
     storageBucket: "docconnect-dev-xxxxx.appspot.com",
     messagingSenderId: "1234567890",
     appId: "1:1234567890:web:abcdef123456"
   };
   ```

8. **Garder cet onglet ouvert** : vous allez recopier ces valeurs à l'étape 5. Si vous le fermez, vous retrouverez ces valeurs à tout moment dans **Project settings → General → Your apps**.

> Ces valeurs sont **publiques par design** : elles atterrissent dans le JS du navigateur. Elles ne donnent pas accès à vos données, elles identifient juste le projet Firebase à contacter. La sécurité d'accès se règle dans la console Firebase et côté serveur, pas en cachant l'`apiKey`.

---

## Étape 4 — Générer la clé service account

C'est la clé **secrète** qui permet à Symfony (côté serveur) de vérifier les tokens Firebase et d'administrer les comptes.

1. Toujours dans **Project settings**, ouvrir l'onglet **"Service accounts"** (**"Comptes de service"**).
2. Vérifier que **"Firebase Admin SDK"** est sélectionné, langage **Node.js** ou **Python** peu importe (on ne télécharge que le JSON).
3. Cliquer sur **"Generate new private key"** (**"Générer une nouvelle clé privée"**).
4. Confirmer dans la pop-up en cliquant sur **"Generate key"**.
5. Un fichier JSON se télécharge automatiquement avec un nom du style `docconnect-dev-xxxxx-firebase-adminsdk-abcde-1234567890.json`.
6. **Renommer** ce fichier en `service-account.json`.
7. Créer le dossier de destination si nécessaire et y placer le fichier :

   ```bash
   mkdir -p config/firebase
   mv ~/Downloads/service-account.json config/firebase/service-account.json
   ```

8. **Vérifier** que `config/firebase/service-account.json` est bien ignoré par Git :

   ```bash
   git check-ignore -v config/firebase/service-account.json
   ```

   La commande doit afficher la ligne de `.gitignore` qui exclut le fichier. Si elle ne renvoie rien, **arrêtez tout** et ajoutez `config/firebase/service-account.json` (ou `config/firebase/*.json`) à `.gitignore` avant de continuer.

> **À ne jamais faire** : committer ce JSON, le coller dans un Discord, le mettre dans un `README.md`, le coller dans `.env.local`. C'est la clé maître de votre projet Firebase. Si elle fuite, révoquez-la immédiatement dans la console (onglet **Service accounts → Manage service account permissions**) et générez-en une nouvelle.

---

## Étape 5 — Remplir `.env.local`

À la racine du projet, ouvrir `.env.local` (le créer en copiant `.env` si besoin) et ajouter le bloc suivant :

```dotenv
###> firebase ###
# Backend (Symfony) — chemin vers le JSON téléchargé à l'étape 4
FIREBASE_CREDENTIALS=config/firebase/service-account.json
FIREBASE_PROJECT_ID=docconnect-dev-xxxxx

# Frontend (web app config) — valeurs publiques de l'étape 3
FIREBASE_API_KEY=AIzaSyA...
FIREBASE_AUTH_DOMAIN=docconnect-dev-xxxxx.firebaseapp.com
FIREBASE_APP_ID=1:1234567890:web:abcdef123456
###< firebase ###
```

### À quoi sert chaque clé

| Clé | Côté | Source | Rôle |
|---|---|---|---|
| `FIREBASE_CREDENTIALS` | Backend | Étape 4 (chemin local) | Indique au bundle `kreait/firebase-bundle` où trouver la clé privée pour signer/vérifier. |
| `FIREBASE_PROJECT_ID` | Backend + Frontend | Étape 3 (`projectId`) | Identifie le projet Firebase. Doit matcher l'`audience` des tokens vérifiés côté serveur. |
| `FIREBASE_API_KEY` | Frontend | Étape 3 (`apiKey`) | Clé publique utilisée par le SDK JS pour appeler les endpoints d'auth Firebase. |
| `FIREBASE_AUTH_DOMAIN` | Frontend | Étape 3 (`authDomain`) | Domaine que le SDK JS contacte pour les flux d'auth. |
| `FIREBASE_APP_ID` | Frontend | Étape 3 (`appId`) | Identifie l'app web spécifique au sein du projet Firebase. |

> `storageBucket` et `messagingSenderId` ne sont **pas nécessaires** ici : pas de Storage ni de FCM dans DocConnect.

### Pièges fréquents

- **Ne pas coller le contenu du JSON dans `.env.local`.** On référence un **chemin** vers le fichier, pas son contenu. Le JSON reste dans `config/firebase/`.
- **Ne pas confondre les deux clés.** La clé web (`FIREBASE_API_KEY`, étape 3) est publique. Le service account (étape 4) est secret. Une fuite de l'une n'a pas la même gravité que l'autre.
- **Pas de guillemets autour des valeurs `.env`** sauf si elles contiennent des espaces (ce qui ne devrait jamais être le cas ici).

---

## Étape 6 — Vérification rapide

### Backend câblé

```bash
docker compose exec php bin/console debug:container --tag=kreait.firebase.auth
```

La commande doit lister au moins un service (typiquement `Kreait\Firebase\Contract\Auth`). Si elle renvoie une liste vide ou une erreur sur `FIREBASE_CREDENTIALS`, vérifier :

- Le chemin dans `.env.local` est correct (relatif à la racine du projet).
- Le fichier `config/firebase/service-account.json` existe bien et est lisible par l'utilisateur du conteneur PHP.
- Le bundle `kreait/firebase-bundle` est installé et activé dans `config/bundles.php`.

### Inscription end-to-end

1. Démarrer la stack : `docker compose up -d`.
2. Ouvrir http://localhost (ou le port exposé par votre `compose.yaml`).
3. Aller sur `/signup`.
4. Créer un compte avec une adresse email factice (`test@example.com`) et un mot de passe d'au moins 6 caractères.
5. Retourner sur la console Firebase → **Authentication → Users**.
6. Le nouvel utilisateur doit apparaître dans la liste avec son UID, son email et la date de création.

Si l'inscription échoue côté front avec une erreur `auth/api-key-not-valid`, c'est que `FIREBASE_API_KEY` est mal recopié. Si elle réussit côté front mais que Symfony refuse l'utilisateur derrière, c'est `FIREBASE_PROJECT_ID` ou `FIREBASE_CREDENTIALS` qui posent problème.

---

## Sécurité

- **`config/firebase/service-account.json`** est la **clé maître** de votre projet Firebase. Quiconque la possède peut créer, modifier et supprimer n'importe quel utilisateur, voire pivoter vers d'autres services Google associés. Jamais en clair sur GitHub, Discord, screenshots, slides de soutenance.
- **`.env.local`** est gitignoré par défaut dans Symfony. Vérifier avant le premier push avec `git status` : aucun de ces deux fichiers ne doit apparaître dans les changements à committer.
- **Si la clé fuite** (même par accident dans un commit ensuite reverté) : la considérer comme compromise. Console Firebase → **Project settings → Service accounts → Manage service account permissions**, supprimer l'ancienne clé, générer-en une nouvelle, mettre à jour `config/firebase/service-account.json`.
- **En production** (hors scope de ce projet école, mais bon réflexe) : ne pas stocker le JSON sur disque. Utiliser `bin/console secrets:set FIREBASE_CREDENTIALS` (Symfony Secrets), ou Google Secret Manager / Vault selon l'hébergeur. Pour DocConnect en dev/démo, le fichier local suffit.
- **Restrictions API key** : optionnel mais sain. Dans la console Google Cloud → **APIs & Services → Credentials**, vous pouvez restreindre la clé web aux domaines `localhost` et celui de votre démo pour limiter l'usage abusif si elle apparaît dans un dépôt public.
