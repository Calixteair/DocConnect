import { Controller } from '@hotwired/stimulus';
import { initializeApp, getApps, getApp } from 'firebase/app';
import { getAuth, signOut } from 'firebase/auth';

/**
 * Logout : signOut Firebase côté JS (vide IndexedDB / session storage)
 * PUIS redirige vers /logout côté Symfony qui efface le cookie httpOnly.
 *
 * Sans le signOut JS, Firebase JS garde la session et au prochain login
 * Firebase l'utiliserait pour repérer la session précédente.
 */
export default class extends Controller {
    static values = {
        apiKey: String,
        authDomain: String,
        projectId: String,
        appId: String,
        logoutUrl: String,
    };

    async signOut(event) {
        event.preventDefault();

        try {
            const app = getApps().find((a) => a.name === 'docconnect')
                ?? initializeApp({
                    apiKey: this.apiKeyValue,
                    authDomain: this.authDomainValue,
                    projectId: this.projectIdValue,
                    appId: this.appIdValue,
                }, 'docconnect');
            await signOut(getAuth(app));
        } catch (e) {
            // Si l'app Firebase n'est pas initialisée ou échoue, on continue
            // quand même vers /logout — l'important c'est le cookie côté serveur.
        }

        window.location.assign(this.logoutUrlValue);
    }
}
