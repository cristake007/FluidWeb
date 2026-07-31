import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['camp'];

    valideaza(event) {
        if (this.element.checkValidity()) {
            this.campTargets.forEach((camp) => this.actualizeazaCamp(camp));

            return;
        }

        event.preventDefault();
        this.campTargets.forEach((camp) => this.actualizeazaCamp(camp));

        const primulCampInvalid = this.campTargets.find((camp) => !camp.validity.valid);

        if (primulCampInvalid) {
            primulCampInvalid.focus();
        }
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
        const identificatorFeedback = camp.getAttribute('aria-describedby');
        const feedback = identificatorFeedback ? document.getElementById(identificatorFeedback) : null;

        if (!esteValid && feedback) {
            feedback.textContent = camp.validity.valueMissing
                ? camp.dataset.mesajLipsa
                : camp.dataset.mesajInvalid;
        }

        camp.classList.toggle('is-invalid', !esteValid);
        camp.setAttribute('aria-invalid', esteValid ? 'false' : 'true');

        return esteValid;
    }
}
