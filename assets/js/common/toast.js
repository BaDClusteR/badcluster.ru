class Toast extends Singleton {
    /**
     * @type HTMLDivElement
     */
    #container;

    #options;

    static DEFAULTS = {
        limit: 3,
        lifetime: 10000
    };

    /**
     * @param {{limit?: number}} options
     */
    constructor(options) {
        super();

        this.#options = Object.assign(
            {},
            Toast.DEFAULTS,
            options
        );
    }

    info(title, message) {
        this.#push(title, message, 'info');
    }

    success(title, message) {
        this.#push(title, message, 'success');
    }

    error(title, message) {
        this.#push(title, message, 'error');
    }

    #push(title, message, type) {
        const container = this.#getContainer();

        if (container.childElementCount >= this.#options.limit) {
            const oldest = container.lastElementChild;
            this.#doRemove(oldest, true);
        }

        const toast = document.createElement('div');
        toast.classList.add('toast', `toast--${type}`);
        const toastHeader = document.createElement('div');
        toastHeader.classList.add('toast__header');
        toastHeader.innerHTML = title;

        const toastBody = document.createElement('div');
        toastBody.classList.add('toast__body');
        toastBody.innerHTML = message;

        const closeBtn = document.createElement('button');
        closeBtn.classList.add('toast__close');
        closeBtn.innerHTML = '×';
        closeBtn.addEventListener(
            'click',
            () => {
                this.#doRemove(toast);
            }
        );

        toast.appendChild(toastHeader);
        toast.appendChild(closeBtn);
        toast.appendChild(toastBody);

        const addHandler = () => {
            if (container.firstElementChild) {
                container.insertBefore(toast, container.firstElementChild);
            } else {
                container.appendChild(toast);
            }
        }

        if (this.#canDoViewTransition()) {
            container.startViewTransition(
                () => {
                    addHandler();
                }
            );
        } else {
            addHandler();
        }

        setTimeout(
            () => {
                this.#remove(toast);
            },
            this.#options.lifetime
        );
    }

    #canDoViewTransition() {
        return 'startViewTransition' in document
            && !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    }

    #getContainer() {
        if (!this.#container) {
            this.#container = document.createElement('div');
            this.#container.classList.add('toast__container');
            document.body.appendChild(this.#container);
        }

        return this.#container;
    }

    /**
     * @param {HTMLDivElement} toast
     */
    #remove(toast) {
        if (toast.matches(':hover')) {
            setTimeout(
                () => {
                    this.#remove(toast);
                },
                500
            );
        } else {
            this.#doRemove(toast);
        }
    }

    #doRemove(toast, isLatest) {
        if (toast && !toast.classList.contains('toast--removing')) {
            if (
                this.#canDoViewTransition()
                && !isLatest
                && this.#getContainer().childElementCount > 1
            ) {
                if (toast.parentNode) {
                    toast.parentNode.startViewTransition(
                        () => {
                            toast.remove();
                        }
                    );
                }
            } else {
                toast.classList.add('toast--removing');
                setTimeout(
                    () => {
                        if (toast.parentNode) {
                            toast.remove();
                        }
                    },
                    500
                );
            }
        }
    }
}
