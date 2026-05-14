class TabController {
    /**
     * @type HTMLElement
     */
    #root;

    /**
     * @type HTMLElement[]
     */
    #switchers;

    /**
     * @type EventDispatcher
     */
    #eventDispatcher;

    /**
     * @param {HTMLElement} root Default: document.documentElement
     */
    constructor(root = document.documentElement) {
        this.#root = root;
        this.#eventDispatcher = EventDispatcher.getInstance();

        this.#initSwitchers();
    }

    #initSwitchers() {
        this.#switchers = Array.from(
            this.#root.querySelectorAll('[role="tab"]')
        );

        this.#switchers.forEach(
            switcher => {
                switcher.addEventListener('click', () => {
                    this.#switch(
                        this.#getSwitcherTabId(switcher)
                    );
                });
            }
        );
    }

    #switch(tabId) {
        this.#switchers.forEach(
            (switcher) => {
                const activeStateToSet = switcher.hasAttribute('data-closable')
                    ? !(switcher.getAttribute('aria-selected') === 'true')
                    : true;

                this.#setState(
                    switcher,
                    (this.#getSwitcherTabId(switcher) === tabId)
                        ? activeStateToSet
                        : false
                );
            }
        );
    }

    #setState(switcher, isActive) {
        switcher.setAttribute('aria-selected', isActive ? 'true' : 'false');
        const tab = document.getElementById(
            this.#getSwitcherTabId(switcher)
        );

        if (tab) {
            if (isActive && tab.hasAttribute('hidden')) {
                tab.removeAttribute('hidden');
                this.#eventDispatcher.trigger('tab:shown', {tab, switcher});
            } else if (!isActive && !tab.hasAttribute('hidden')) {
                tab.setAttribute('hidden', '');
                this.#eventDispatcher.trigger('tab:hidden', {tab, switcher});
            }
        }
    }

    /**
     * @param {HTMLElement} switcher
     */
    #getSwitcherTabId(switcher) {
        return String(switcher.getAttribute('aria-controls') || '');
    }

    static autoload() {
        document.querySelectorAll('[data-tabs]').forEach(
            tabContainer => {
                tabContainer.tabController = new TabController(tabContainer);
            }
        );
    }
}

TabController.autoload();
