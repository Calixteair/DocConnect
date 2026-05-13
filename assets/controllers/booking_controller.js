import { Controller } from '@hotwired/stimulus';

/**
 * Modal de confirmation de réservation.
 * Au clic sur un .slot dans la fiche médecin, on ouvre la modal, on remplit
 * le récap (date, heure, mode) et on pose slot_id sur le form caché.
 *
 * Le form POST /app/appointments est rendu côté Symfony — Stimulus ne gère
 * que l'UX (ouverture, fermeture, focus trap léger).
 */
export default class extends Controller {
    static targets = ['modal', 'form', 'slotIdInput', 'recapDate', 'recapMode', 'cancelButton', 'motifInput'];
    static values = {
        isAuthenticated: Boolean,
        loginUrl: String,
        afterLoginIntent: String,
    };

    connect() {
        // Délégation : tous les .slot dans la grille ouvrent la modal.
        this.boundOpen = this.openFromSlot.bind(this);
        document.querySelectorAll('.slot[data-slot-id]').forEach((slot) => {
            slot.addEventListener('click', this.boundOpen);
        });
        this.boundEscape = (e) => { if (e.key === 'Escape' && this.modalIsOpen()) this.close(); };
        document.addEventListener('keydown', this.boundEscape);
    }

    disconnect() {
        document.removeEventListener('keydown', this.boundEscape);
    }

    openFromSlot(event) {
        const button = event.currentTarget;
        const slotId = button.dataset.slotId;
        const slotLabel = button.getAttribute('aria-label') || button.textContent.trim();

        if (!this.isAuthenticatedValue) {
            // Sauvegarde de l'intent pour redirect post-login.
            sessionStorage.setItem('docconnect:bookingIntent', JSON.stringify({
                slotId,
                returnTo: window.location.pathname + window.location.search,
            }));
            window.location.assign(this.loginUrlValue);
            return;
        }

        this.slotIdInputTarget.value = slotId;
        if (this.hasRecapDateTarget) this.recapDateTarget.textContent = slotLabel;
        this.open();
    }

    open() {
        if (!this.hasModalTarget) return;
        this.modalTarget.dataset.open = 'true';
        this.modalTarget.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        // Focus initial sur le motif pour clavier.
        if (this.hasMotifInputTarget) this.motifInputTarget.focus();
    }

    close(event) {
        if (event) event.preventDefault();
        if (!this.hasModalTarget) return;
        this.modalTarget.dataset.open = 'false';
        this.modalTarget.setAttribute('hidden', 'hidden');
        document.body.style.overflow = '';
    }

    modalIsOpen() {
        return this.hasModalTarget && this.modalTarget.dataset.open === 'true';
    }
}
