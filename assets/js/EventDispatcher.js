class EventDispatcher extends Singleton {
    /**
     * @type {Object.<string, function[]>}
     */
    #listeners = {};

    listen(eventName, callback) {
        if (!this.#listeners[eventName]) {
            this.#listeners[eventName] = [];

            document.addEventListener(
                eventName,
                (e) => {
                    this.#triggerListeners(
                        eventName,
                        e?.detail
                    );
                }
            )
        }

        this.#listeners[eventName].push(callback);
    }

    trigger(eventName, data) {
        document.dispatchEvent(
            new CustomEvent(
                eventName,
                {detail: data}
            )
        );
    }

    #triggerListeners(eventName, data) {
        if (this.#listeners[eventName]) {
            this.#listeners[eventName].forEach(
                (listener) => {
                    listener(data);
                }
            );
        }
    }
}

EventDispatcher.getInstance()
