# 🎬 docs/demo-script.md — Scénario de démo joué

Script étape par étape pour la démonstration de 8 minutes. Chaque étape liste : **ce qu'on clique**, **ce qu'on dit**, **ce que le jury voit**.

> ⏱️ Total cible : **8 min**. Timer dans `[crochets]` en début de chaque étape.

> Pour la préparation et les onglets à ouvrir avant la démo, voir **[PRESENTATION.md § Pré-soutenance](../PRESENTATION.md#️-pré-soutenance-j-1-15-min)**.

---

## 🎯 Onglets ouverts au démarrage

1. 🟦 **Patient** — http://localhost:8080 (déconnecté pour montrer le login)
2. 🟩 **Médecin** — http://localhost:8080/app/planning (connecté `medecin@docconnect.tn`)
3. 🟧 **Admin** — http://localhost:8080/admin (connecté `admin@docconnect.tn`)
4. 📧 **Mailtrap** — https://mailtrap.io/inboxes/<id>/messages (vidée juste avant)
5. 🗄️ **Adminer** — http://localhost:8081 (en réserve, à n'ouvrir que si on parle de la DB)

---

## Étape 1 — Landing & recherche · [0:00 → 0:45]

**Onglet** : 🟦 Patient

**Ce qu'on fait** :
1. Pointer la **hero section** + la barre de recherche.
2. Sélectionner spécialité **« Cardiologie »** + ville **« Tunis »**.
3. Cliquer **Rechercher**.

**Ce qu'on dit** :
> « Page d'accueil sobre, palette médicale. Recherche par spécialité et ville, ce sont les deux critères qui comptent pour un patient. Pas de inscription requise pour parcourir l'annuaire. »

**Ce que le jury voit** : page d'accueil + résultats avec 1-2 cards médecins.

---

## Étape 2 — Fiche médecin + réservation · [0:45 → 1:45]

**Onglet** : 🟦 Patient

**Ce qu'on fait** :
1. Cliquer sur la card **Dr Aymen Ben Ali**.
2. Pointer la **bio**, **tarif**, **cabinet**, **langues**.
3. Sur le calendrier hebdomadaire, choisir un créneau **VIDEO** (icône caméra).
4. Modal de confirmation → motif : « Bilan annuel ».
5. Cliquer **Confirmer la réservation**.
6. Se faire rediriger sur `/app/mes-rdv`.

**Ce qu'on dit** :
> « La fiche médecin donne toutes les infos utiles. Le calendrier sépare visuellement les créneaux physiques et téléconsultation. Réservation atomique côté serveur — deux patients qui cliquent le même créneau, un seul gagne, l'autre voit une erreur claire. »

**Ce que le jury voit** : fiche → calendrier → modal → liste « Mes RDV » avec 1 entrée.

---

## Étape 3 — Mail de confirmation · [1:45 → 2:00]

**Onglet** : 📧 Mailtrap

**Ce qu'on fait** :
1. Refresh l'inbox.
2. Ouvrir le mail **« Rendez-vous confirmé »**.

**Ce qu'on dit** :
> « Mail envoyé via Symfony Mailer + Mailtrap. En prod on bascule sur Messenger async pour ne pas bloquer la requête HTTP. »

**Ce que le jury voit** : mail HTML sobre, infos RDV, lien retour.

---

## Étape 4 — Chatbot d'orientation · [2:00 → 2:45]

**Onglet** : 🟦 Patient (revenir sur `/app/mes-rdv`)

**Ce qu'on fait** :
1. Cliquer le **FAB chatbot** (bouton bleu en bas à droite).
2. Taper : **« J'ai mal au ventre depuis 3 jours, vers qui aller ? »**.
3. Pointer le **streaming token par token** + le lien spécialité en fin de réponse.
4. Cliquer le lien généré → redirige vers `/medecins?specialty=...`.

**Ce qu'on dit** :
> « OpenRouter en streaming SSE. Le system prompt interdit le diagnostic — le bot oriente vers une spécialité et termine par un lien markdown cliquable. En cas de signaux d'alerte (douleur thoracique, malaise), il redirige vers le 190, SAMU Tunisie. Cache 1h sur question identique : 1 LLM puis hit cache, on évite de cramer le crédit OpenRouter. »

**Ce que le jury voit** : chat qui s'écrit en direct, bouton cliquable vers l'annuaire pré-filtré.

---

## Étape 5 — Côté médecin : confirmer le RDV · [2:45 → 3:30]

**Onglet** : 🟩 Médecin

**Ce qu'on fait** :
1. Refresh `/app/planning`.
2. Repérer le RDV en attente (badge orange **« En attente »**) sur le slot que le patient vient de réserver.
3. Cliquer **Confirmer**.
4. Badge passe à **« Confirmé »** (vert).
5. Bouton **« Rejoindre »** apparaît (si on est dans la fenêtre).

**Ce qu'on dit** :
> « Le médecin gère son planning hebdomadaire. Quatre actions par cellule selon l'état : confirmer un RDV en attente, refuser, annuler un RDV confirmé, marquer terminé après la consultation. À la confirmation, un mail part au patient et la salle Jitsi est générée. »

**Ce que le jury voit** : planning, transition d'état, apparition du bouton visio.

---

## Étape 6 — Visio Jitsi des 2 côtés · [3:30 → 4:30]

**Onglets** : 🟦 Patient ET 🟩 Médecin (alterner)

**Ce qu'on fait** :
1. 🟩 Médecin clique **Rejoindre** → tab Jitsi s'ouvre, on accepte mic/cam.
2. 🟦 Patient : refresh `/app/mes-rdv` → bouton **Rejoindre la visio** est apparu sur le RDV.
3. Patient clique **Rejoindre** → 2e tab Jitsi → on accepte mic/cam.
4. Les 2 participants voient leur vidéo l'un l'autre.

**Ce qu'on dit** :
> « Jitsi `meet.jit.si` en iframe. La salle est générée à la confirmation, son nom est unique et persisté en base — patient et médecin tombent toujours dans la **même** salle. La fenêtre d'ouverture est `[start − 10 min, end + 30 min]`. »

**Ce que le jury voit** : 2 fenêtres avec vidéo, prouvant que la visio fonctionne vraiment.

**⚠️ Plan B** : si Jitsi demande un lobby, cliquer **Ask to join** côté patient, **Admit** côté médecin. Si ça plante, passer à l'étape suivante en disant « la salle est générée, le lien marche, on a déjà testé ».

---

## Étape 7 — Côté admin : CRUD users + création médecin · [4:30 → 6:00]

**Onglet** : 🟧 Admin

**Ce qu'on fait** :
1. Dashboard `/admin` → pointer les compteurs (patients / médecins / admins).
2. **Sidebar → Utilisateurs** → filtre par rôle = `DOCTOR`.
3. Cliquer **Éditer** sur un médecin → montrer formulaire (nom, email, téléphone, rôle).
4. Retour, **Sidebar → Médecins**.
5. Cliquer **+ Ajouter un médecin**.
6. Remplir le formulaire :
   - Compte : « Sonia », « Trabelsi », `dr.trabelsi@docconnect.tn`, mot de passe `demo1234`.
   - Slug : `dr-sonia-trabelsi`.
   - Tarif : `60`, accepte visio coché.
   - Cocher **Pédiatrie**, **Médecine générale**.
   - Cabinet : « 12 avenue de la République », « Tunis », `1000`.
7. **Créer le médecin**.
8. Le médecin apparaît dans la liste.
9. Cliquer **Supprimer** sur le médecin qu'on vient de créer → confirm JS → disparu.

**Ce qu'on dit** :
> « L'admin a un CRUD complet sur les utilisateurs et les médecins. La création d'un médecin crée d'un coup le compte Firebase, l'entité User en MariaDB, la fiche Doctor, ses spécialités, et son premier cabinet. La suppression cascade jusqu'aux RDV — c'est volontaire, projet école hors UE, pas d'enjeu de conservation légale. Garde-fous : on ne peut pas supprimer le dernier admin, ni voler l'email d'un patient existant pour créer un médecin. »

**Ce que le jury voit** : dashboard, liste filtrable, formulaire combiné, création réussie, suppression cascade.

---

## Étape 8 — Conclusion · [6:00 → 7:00]

**Onglet** : revenir sur 🟦 Patient page d'accueil

**Ce qu'on dit** :
> « En 6 minutes on a couvert le flow complet : recherche → réservation → mail → chatbot → confirmation médecin → visio → administration. La stack est volontairement légère : Symfony 8, Twig, Doctrine, Firebase pour l'auth, OpenRouter pour le chatbot, Jitsi pour la visio. Tout tourne dans Docker, démarrage en une commande, démo provisionnée avec `app:demo:seed`. »

> « Hors scope volontaire : paiement, ordonnance, conformité HDS / RGPD strict. Le code est dans le repo, conventionnel Symfony, documenté dans le README et le SETUP. Des questions ? »

---

## 🪤 Pièges à éviter

| Piège | Comment l'éviter |
|---|---|
| Lancer la démo sur un slot **passé** | Mettre à jour le slot **juste avant** la soutenance (cf. cmd dans PRESENTATION.md) |
| Mailtrap qui ne rafraîchit pas | Refresh manuel + onglet ouvert sur la bonne inbox |
| Saisir un mot de passe Firebase < 6 caractères | Toujours `demo1234` ou plus |
| Le navigateur garde une session Firebase d'une autre démo | Onglets en **fenêtre privée** ou profils Chrome séparés |
| `localhost:8080` répond bizarrement | `docker compose restart` 10 s avant de commencer |
| Le chatbot rate-limit 429 | Limiter à 1 question pendant la démo, ou prendre une clé OpenRouter payante |

---

## ⏲️ Mémo timings

```
0:00 ─┬─ Pitch & contexte (45 s)
0:45 ─┼─ Étape 1 : Landing & recherche
1:30 ─┼─ Étape 2 : Fiche + réservation
2:30 ─┼─ Étape 3 : Mail Mailtrap
2:45 ─┼─ Étape 4 : Chatbot
3:30 ─┼─ Étape 5 : Médecin confirme
4:30 ─┼─ Étape 6 : Visio Jitsi
6:00 ─┼─ Étape 7 : Admin CRUD
7:00 ─┴─ Étape 8 : Conclusion + Q/R
```

Garde **1 minute de marge** pour les Q/R immédiates ou un imprévu.
