import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sursa', 'iconita'];

    async copiaza(event) {
        const control = event.currentTarget;

        try {
            await navigator.clipboard.writeText(this.sursaTarget.textContent.trim());
            this.iconitaTarget.classList.replace('ti-copy', 'ti-check');
            control.setAttribute('aria-label', 'Parolă copiată');
            control.setAttribute('title', 'Parolă copiată');

            window.clearTimeout(this.temporizatorResetare);
            this.temporizatorResetare = window.setTimeout(() => {
                this.iconitaTarget.classList.replace('ti-check', 'ti-copy');
                control.setAttribute('aria-label', 'Copiază parola');
                control.setAttribute('title', 'Copiază parola');
            }, 2000);
        } catch {
            control.setAttribute('aria-label', 'Parola nu a putut fi copiată');
            control.setAttribute('title', 'Parola nu a putut fi copiată');
        }
    }

    disconnect() {
        window.clearTimeout(this.temporizatorResetare);
    }
}
