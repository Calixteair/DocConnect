# Mailtrap — setup en 3 minutes

Mailtrap permet d'intercepter les mails envoyés en dev sans rien spammer dans la vraie boîte des destinataires. Idéal pour la démo : on voit les mails dans une UI propre.

## Étape 1 — Créer le compte

1. Aller sur https://mailtrap.io
2. Sign up (gratuit, aucune carte requise)
3. Confirmer l'email reçu

## Étape 2 — Récupérer le SMTP DSN

1. Dans le menu de gauche, cliquer sur **Email Testing** → **Inboxes**
2. Mailtrap a créé un inbox par défaut "My Inbox". Si tu veux un nom propre, clique **Add Inbox** → nom : `DocConnect`
3. Ouvre l'inbox, onglet **Integrations** ou **SMTP Settings**
4. Sélectionner **Symfony** dans le dropdown des intégrations
5. Copier la ligne `MAILER_DSN=smtp://...:...@sandbox.smtp.mailtrap.io:2525`

Le DSN ressemble à ceci :
```
MAILER_DSN=smtp://abc123def456:zzzwwwxxxyyy@sandbox.smtp.mailtrap.io:2525
```

## Étape 3 — Configurer DocConnect

Coller le DSN dans `.env.local` :

```env
MAILER_DSN=smtp://<login>:<password>@sandbox.smtp.mailtrap.io:2525
MAILER_FROM_ADDRESS=no-reply@docconnect.tn
MAILER_FROM_NAME=DocConnect
```

Puis recharger PHP :

```bash
docker compose restart php
```

## Vérification

1. Connecte-toi sur DocConnect, réserve un RDV.
2. Va sur https://mailtrap.io → ton inbox.
3. Tu dois voir un mail "Rendez-vous confirmé" arriver dans les 2 secondes.

## Sécurité

- Le DSN Mailtrap contient un mot de passe SMTP — il reste dans `.env.local` (gitignored).
- Mailtrap free : 100 mails / mois, 1 inbox, suffisant pour un projet école.
- En production réelle, on switcherait sur un vrai SMTP (SendGrid, SES, etc.) — hors scope MVP.
