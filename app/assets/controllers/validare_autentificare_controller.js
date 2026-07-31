import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['camp'];

    valideaza(event) {
        this.campTargets.forEach((camp) => this.actualizeazaCamp(camp));

        const primulCampInvalid = this.campTargets.find((camp) => !camp.validity.valid);

        if (!primulCampInvalid) {
            return;
        }

        event.preventDefault();
        primulCampInvalid.focus();
    }

    valideazaCamp(event) {
        this.actualizeazaCamp(event.currentTarget);
    }

    actualizeazaDupaEditare(event) {
        if ('true' === event.currentTarget.getAttribute('aria-invalid')) {
            this.actualizeazaCamp(event.currentTarget);
        }
    }

    actualizeazaCamp(camp) {
        const esteValid = camp.validity.valid;

        camp.classList.toggle('is-invalid', !esteValid);
        camp.setAttribute('aria-invalid', esteValid ? 'false' : 'true');

        return esteValid;
    }
}
