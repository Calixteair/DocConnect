import { Controller } from '@hotwired/stimulus';
import { initializeApp } from 'firebase/app';
import {
    getAuth,
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    sendEmailVerification,
    setPersistence,
    browserSessionPersistence,
} from 'firebase/auth';

/**
 * Stimulus controller branché sur le formulaire login OU signup.
 * Lit les data-* attributs pour récupérer la config Firebase et le mode.
 *
 * Workflow :
 *   1. Firebase JS SDK crée le compte (signup) ou authentifie (login).
 *   2. On récupère l'idToken.
 *   3. POST /auth/sync avec l'idToken (+ prénom/nom pour signup).
 *   4. Symfony pose le cookie httpOnly et persiste le User en DB.
 *   5. Redirection vers /app/mes-rdv.
 */
export default class extends Controller {
    static values = {
        mode: String,              // 'login' | 'signup'
        apiKey: String,
        authDomain: String,
        projectId: String,
        appId: String,
    };

    static targets = ['error', 'submit'];

    connect() {
        // Marque les champs comme "touched" au premier blur pour éviter le rouge initial.
        this.element.querySelectorAll('.field__input').forEach((input) => {
            input.addEventListener('blur', () => input.classList.add('is-touched'), { once: true });
        });

        if (!this.apiKeyValue) {
            this.showError('Configuration Firebase manquante. Voir docs/firebase-setup.md.');
            this.disableForm();
            return;
        }

        try {
            this.app = initializeApp({
                apiKey: this.apiKeyValue,
                authDomain: this.authDomainValue,
                projectId: this.projectIdValue,
                appId: this.appIdValue,
            }, 'docconnect');
            this.auth = getAuth(this.app);
            setPersistence(this.auth, browserSessionPersistence).catch(() => {});
        } catch (e) {
            this.showError('Impossible d\'initialiser Firebase.');
            this.disableForm();
        }
    }

    async submit(event) {
        event.preventDefault();
        this.clearError();
        this.setLoading(true);

        const form = event.currentTarget;
        const data = new FormData(form);
        const email = (data.get('email') || '').toString().trim();
        const password = (data.get('password') || '').toString();
        const firstName = (data.get('firstName') || '').toString().trim();
        const lastName = (data.get('lastName') || '').toString().trim();

        try {
            const credential = this.modeValue === 'signup'
                ? await createUserWithEmailAndPassword(this.auth, email, password)
                : await signInWithEmailAndPassword(this.auth, email, password);

            if (this.modeValue === 'signup') {
                // Email de vérification non bloquant (cf. décision Phase 2).
                sendEmailVerification(credential.user).catch(() => {});
            }

            const idToken = await credential.user.getIdToken(/* forceRefresh */ true);

            const res = await fetch('/auth/sync', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ idToken, firstName, lastName }),
            });

            if (!res.ok) {
                const payload = await res.json().catch(() => ({}));
                throw new Error(payload.error || 'Synchronisation impossible.');
            }

            window.location.assign('/app/mes-rdv');
        } catch (e) {
            this.showError(this.humanizeError(e));
        } finally {
            this.setLoading(false);
        }
    }

    showError(message) {
        if (!this.hasErrorTarget) return;
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
    }

    clearError() {
        if (!this.hasErrorTarget) return;
        this.errorTarget.textContent = '';
        this.errorTarget.hidden = true;
    }

    setLoading(loading) {
        if (!this.hasSubmitTarget) return;
        this.submitTarget.disabled = loading;
        this.submitTarget.dataset.originalLabel ??= this.submitTarget.textContent;
        this.submitTarget.textContent = loading
            ? (this.modeValue === 'signup' ? 'Création…' : 'Connexion…')
            : this.submitTarget.dataset.originalLabel;
    }

    disableForm() {
        const submit = this.element.querySelector('button[type="submit"]');
        if (submit) submit.disabled = true;
    }

    humanizeError(error) {
        const code = error && error.code ? String(error.code) : '';
        const map = {
            'auth/email-already-in-use': 'Cette adresse e-mail est déjà utilisée.',
            'auth/invalid-email': 'Indiquez une adresse e-mail valide.',
            'auth/weak-password': 'Le mot de passe doit contenir au moins 6 caractères.',
            'auth/invalid-credential': 'E-mail ou mot de passe incorrect.',
            'auth/wrong-password': 'Mot de passe incorrect.',
            'auth/user-not-found': 'Aucun compte ne correspond à cette adresse.',
            'auth/too-many-requests': 'Trop de tentatives. Réessayez dans quelques minutes.',
            'auth/network-request-failed': 'Connexion impossible. Vérifiez votre réseau.',
        };
        return map[code] || (error?.message ?? 'Une erreur est survenue.');
    }
}
