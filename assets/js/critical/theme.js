class Theme extends Singleton {
    static COOKIE_NAME = 'theme';
    static COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

    static DARK = 'dark';
    static LIGHT = 'light';

    /**
     * @type EventDispatcher
     */
    #eventDispatcher;

    constructor() {
        super();

        this.#eventDispatcher = EventDispatcher.getInstance();

        this.#initListeners();
    }

    /**
     * Тема, в которой сейчас отрисована страница: выбранная пользователем либо
     * системная.
     *
     * @return {string}
     */
    get() {
        return this.getPreferred() || this.#detectSystemTheme();
    }

    /**
     * Явно выбранная пользователем тема; пустая строка — тема системная.
     * По этой же куке бэкенд проставляет data-theme на <html>, поэтому тема
     * применяется ещё до того, как отработает JS.
     *
     * @return {string}
     */
    getPreferred() {
        const value = document.cookie
            .split('; ')
            .find(item => item.startsWith(`${Theme.COOKIE_NAME}=`))
            ?.slice(Theme.COOKIE_NAME.length + 1) ?? '';

        return (value === Theme.DARK || value === Theme.LIGHT)
            ? value
            : '';
    }

    /**
     * @param {string} theme
     * @param {boolean} immediate
     */
    set(theme, immediate = false) {
        this.#doSet(theme, !!theme, immediate);
    }

    #doSet(theme, isPreferred, immediate = false) {
        this.#eventDispatcher.trigger(
            'theme:set',
            {
                theme: theme || this.#detectSystemTheme(),
                isPreferred,
                immediate
            }
        );
    }

    #initListeners() {
        this.#eventDispatcher.listen(
            'theme:set',
            /**
             * @param {string} theme
             * @param {boolean} isPreferred
             */
            ({theme, isPreferred = true}) => {
                if (isPreferred) {
                    this.#saveToCookie(theme);
                }
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
             * @param {boolean} isPreferred
             */
            ({theme, isPreferred = true}) => {
                // Без data-theme страница живёт на light-dark() и системной
                // color-scheme, так что атрибут нужен только для явного выбора.
                if (isPreferred) {
                    document.documentElement.setAttribute('data-theme', theme);
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            }
        );

        // При возврате на страницу из BF cache DOM восстанавливается с тем
        // data-theme, который был на момент ухода — переприменяем актуальную
        // тему из куки (её могли поменять в другой вкладке). immediate=true:
        // переключатель применяет её без анимации, иначе скрытая иконка
        // проезжает из центра к границе кнопки.
        window.addEventListener(
            'pageshow',
            (event) => {
                if (event.persisted) {
                    this.set(
                        this.getPreferred(),
                        true
                    );
                }
            }
        );
    }

    #saveToCookie(theme) {
        document.cookie = [
            `${Theme.COOKIE_NAME}=${theme}`,
            'path=/',
            `max-age=${Theme.COOKIE_MAX_AGE}`,
            'SameSite=Lax',
            ...(window.location.protocol === 'https:' ? ['Secure'] : [])
        ].join('; ');
    }

    #detectSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? Theme.DARK
            : Theme.LIGHT;
    }
}

Theme.getInstance();
