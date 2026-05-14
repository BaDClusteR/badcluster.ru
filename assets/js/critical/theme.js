class Theme extends Singleton {
    static LS_NAME = 'theme';
    static LS_IS_PREDEFINED = 'theme-defined';

    static DARK = 'dark';
    static LIGHT = 'light';

    /**
     * @type MediaQueryList
     */
    #mediaMatcher;

    /**
     * @type EventDispatcher
     */
    #eventDispatcher;

    constructor() {
        super();

        this.#mediaMatcher = window.matchMedia('(prefers-color-scheme: dark)');
        this.#eventDispatcher = EventDispatcher.getInstance();

        this.#initListeners();

        this.set(
            this.get(true)
        );
    }

    isSystem() {
        return !localStorage.getItem(Theme.LS_IS_PREDEFINED);
    }

    get(returnEmptyOnSystem = false) {
        const theme = {theme: '', returnEmptyOnSystem: !!returnEmptyOnSystem};
        this.#eventDispatcher.trigger(
            'theme:get',
            theme
        );

        return theme.theme;
    }

    /**
     * @param {string} theme
     */
    set(theme) {
        this.#doSet(theme, !!theme);
    }

    #doSet(theme, isPredefined) {
        if (!theme) {
            theme = this.#detectCurrentTheme();
        }

        this.#eventDispatcher.trigger(
            'theme:set',
            {theme, isPredefined}
        );
    }

    #initListeners() {
        this.#eventDispatcher.listen(
            'theme:set',
            /**
             * @param {string} theme
             * @param {boolean} isPredefined
             */
            ({theme, isPredefined}) => {
                localStorage.setItem(
                    Theme.LS_IS_PREDEFINED,
                    isPredefined
                        ? theme
                        : ''
                );

                localStorage.setItem(Theme.LS_NAME, theme);
            }
        );

        this.#eventDispatcher.listen(
            'theme:set',
            () => {
                setTimeout(
                    () => {
                        document.documentElement.classList.remove('theme-changing');
                    },
                    250
                );

                document.documentElement.classList.add('theme-changing');
            }
        );

        this.#eventDispatcher.listen(
            'theme:set',
            /**
             * @param {string} theme
             */
            ({theme}) => {
                if (theme) {
                    document.documentElement.setAttribute('data-theme', theme);
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            }
        );

        this.#eventDispatcher.listen(
            'theme:set',
            /**
             * @param {string} theme
             */
            ({theme}) => {
                document.documentElement.style.colorScheme = (theme === Theme.DARK)
                    ? 'dark'
                    : null;
            }
        );

        this.#eventDispatcher.listen(
            'theme:get',
            /**
             * @param {{returnEmptyOnSystem?: boolean, theme: string}} obj
             */
            (obj) => {
                obj.theme = (this.isSystem() && obj.returnEmptyOnSystem)
                    ? ''
                    : localStorage.getItem(Theme.LS_NAME);
            }
        );

        const autoSetTheme = (isDark) => {
            if (this.isSystem()) {
                this.#doSet(
                    this.#getDarkOrLight(
                        isDark
                    ),
                    false
                );
            }
        }

        this.#mediaMatcher.addEventListener(
            'change',
            (event) => {
                autoSetTheme(event.matches);
            }
        );

        autoSetTheme(this.#mediaMatcher.matches);
    }

    #detectCurrentTheme() {
        return this.#getDarkOrLight(
            this.#mediaMatcher.matches
        );
    }

    #getDarkOrLight(isDark) {
        return isDark
            ? Theme.DARK
            : Theme.LIGHT;
    }
}

Theme.getInstance();
