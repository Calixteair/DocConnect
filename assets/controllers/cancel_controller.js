import { Controller } from '@hotwired/stimulus';

/**
 * Modal de confirmation d'annulation de RDV.
 * Au clic sur un bouton "Annuler", on ouvre la modal commune et on cible le bon form (via formId).
 * Le submit final est délégué au form HTML normal (POST CSRF).
 */
export default class extends Controller {
    static targets = ['modal', 'recap', 'confirmButton'];

    connect() {
        this.boundEscape = (e) => { if (e.key === 'Escape' && this.isOpen()) this.close(); };
        document.addEventListener('keydown', this.boundEscape);
    }

    disconnect() {
        document.removeEventListener('keydown', this.boundEscape);
    }

    open(event) {
        event.preventDefault();
        const trigger = event.currentTarget;
        const formId = trigger.dataset.formId;
        const recap = trigger.dataset.recap || '';

        this.openFormId = formId;
        if (this.hasRecapTarget) this.recapTarget.textContent = recap;
        this.modalTarget.dataset.open = 'true';
        this.modalTarget.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        if (this.hasConfirmButtonTarget) this.confirmButtonTarget.focus();
    }

    confirm(event) {
        event.preventDefault();
        if (!this.openFormId) return;
        const form = document.getElementById(this.openFormId);
        if (form) form.submit();
    }

    close(event) {
        if (event) event.preventDefault();
        if (!this.hasModalTarget) return;
        this.modalTarget.dataset.open = 'false';
        this.modalTarget.setAttribute('hidden', 'hidden');
        document.body.style.overflow = '';
        this.openFormId = null;
    }

    isOpen() {
        return this.hasModalTarget && this.modalTarget.dataset.open === 'true';
    }
}
