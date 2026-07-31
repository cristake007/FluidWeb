import { Controller } from '@hotwired/stimulus';

const CHEIE_PREFERINTA = 'fluidweb.baraLateralaRestransa';

export default class extends Controller {
    static targets = ['control', 'text'];
    static values = {
        clasaRestransa: { type: String, default: 'shell-aplicatie--restransa' },
    };

    connect() {
        this.interogareDesktop = window.matchMedia('(min-width: 768px)');
        this.laSchimbareEcran = this.actualizeazaPentruEcran.bind(this);
        this.interogareDesktop.addEventListener('change', this.laSchimbareEcran);

        this.element.classList.add('shell-aplicatie--fara-tranzitie');
        this.actualizeazaPentruEcran();
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => this.element.classList.remove('shell-aplicatie--fara-tranzitie'));
        });
    }

    disconnect() {
        this.interogareDesktop?.removeEventListener('change', this.laSchimbareEcran);
    }

    comuta() {
        if (!this.interogareDesktop.matches) {
            return;
        }

        const esteRestransa = this.element.classList.toggle(this.clasaRestransaValue);
        window.localStorage.setItem(CHEIE_PREFERINTA, esteRestransa ? 'true' : 'false');
        this.actualizeazaControl(esteRestransa);
    }

    actualizeazaPentruEcran() {
        const esteRestransa = this.interogareDesktop.matches
            && 'true' === window.localStorage.getItem(CHEIE_PREFERINTA);

        this.element.classList.toggle(this.clasaRestransaValue, esteRestransa);
        this.actualizeazaControl(esteRestransa);
    }

    actualizeazaControl(esteRestransa) {
        const eticheta = esteRestransa ? 'Extinde' : 'Restrânge';

        this.controlTarget.setAttribute('aria-label', eticheta);
        this.controlTarget.setAttribute('title', eticheta);
        this.textTarget.textContent = eticheta;
    }
}
