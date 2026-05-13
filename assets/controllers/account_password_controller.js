import { Controller } from '@hotwired/stimulus';
import { initializeApp, getApps } from 'firebase/app';
import { getAuth, EmailAuthProvider, reauthenticateWithCredential, updatePassword } from 'firebase/auth';

/**
 * Change le mot de passe Firebase de l'utilisateur courant.
 * Firebase impose une re-authentification récente : on demande l'ancien mot
 * de passe, on reauth puis on update. Pas de round-trip backend nécessaire
 * (le mot de passe vit dans Firebase, pas en base).
 */
export default class extends Controller {
    static targets = ['current', 'next', 'confirm', 'feedback', 'submit'];
    static values = {
        apiKey: String,
        authDomain: String,
        projectId: String,
        appId: String,
        email: String,
    };

    async submit(event) {
        event.preventDefault();

        const current = this.currentTarget.value;
        const next = this.nextTarget.value;
        const confirm = this.confirmTarget.value;

        if (!current || !next) {
            this.report('Renseignez les deux mots de passe.', 'error');
            return;
        }
        if (next.length < 6) {
            this.report('Le nouveau mot de passe doit faire au moins 6 caractères.', 'error');
            return;
        }
        if (next !== confirm) {
            this.report('La confirmation ne correspond pas.', 'error');
            return;
        }

        this.toggleSubmitting(true);

        try {
            const app = getApps().find((a) => a.name === 'docconnect')
                ?? initializeApp({
                    apiKey: this.apiKeyValue,
                    authDomain: this.authDomainValue,
                    projectId: this.projectIdValue,
                    appId: this.appIdValue,
                }, 'docconnect');

            const auth = getAuth(app);
            const user = auth.currentUser;
            if (!user) {
                this.report('Session expirée, reconnectez-vous.', 'error');
                this.toggleSubmitting(false);
                return;
            }

            const cred = EmailAuthProvider.credential(this.emailValue, current);
            await reauthenticateWithCredential(user, cred);
            await updatePassword(user, next);

            this.report('Mot de passe mis à jour.', 'success');
            this.currentTarget.value = '';
            this.nextTarget.value = '';
            this.confirmTarget.value = '';
        } catch (e) {
            const code = e?.code ?? '';
            if (code === 'auth/wrong-password' || code === 'auth/invalid-credential') {
                this.report('Mot de passe actuel incorrect.', 'error');
            } else if (code === 'auth/weak-password') {
                this.report('Mot de passe trop faible.', 'error');
            } else {
                this.report('Erreur : ' + (e?.message ?? 'inconnue'), 'error');
            }
        } finally {
            this.toggleSubmitting(false);
        }
    }

    report(text, kind) {
        this.feedbackTarget.textContent = text;
        this.feedbackTarget.dataset.kind = kind;
    }

    toggleSubmitting(on) {
        this.submitTarget.disabled = on;
        this.submitTarget.textContent = on ? 'Mise à jour…' : 'Mettre à jour';
    }
}
