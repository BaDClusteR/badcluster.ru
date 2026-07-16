/**
 * @typedef {(string) => void} ThemeSwitcherCallback
 */

class ThemeSwitcher {
    /**
     * @type {HTMLButtonElement}
     */
    #switcher;

    #isSwitching = false;

    /**
     * @type Theme
     */
    #theme;

    /**
     * @type EventDispatcher
     */
    #eventDispatcher;

    /**
     * @param {HTMLButtonElement} switcher
     */
    constructor(switcher) {
        this.#theme = Theme.getInstance();
        this.#eventDispatcher = EventDispatcher.getInstance();
        this.#switcher = switcher;

        this.#init();
    }

    #init() {
        this.#detectTheme();
        this.#initListeners();
    }

    #detectTheme() {
        this.#doSetTheme(
            this.#theme.get(),
            true
        );
    }

    #initListeners() {
        this.#switcher.addEventListener('click', () => {
            this.switchTheme();
        });

        this.#eventDispatcher.listen(
            'theme:set',
            /**
             * @param {{theme: string, immediate?: boolean}} payload
             */
            ({theme, immediate}) => {
                this.#doSetTheme(theme, immediate);
            }
        );
    }

    switchTheme() {
        if (this.#isSwitching) {
            return;
        }

        const theme = this.#theme.get();
        this.setTheme(
            theme === 'light'
                ? 'dark'
                : 'light'
        );

    }

    setTheme(theme) {
        if (this.#isSwitching) {
            return;
        }

        this.#eventDispatcher.trigger(
            'theme:set',
            {theme, isPredefined: true}
        );
    }

    #doSetTheme(theme, isImmediately) {
        this.#removeModCssClasses();
        this.#isSwitching = true;

        if (isImmediately) {
            // Мгновенное применение (первая загрузка / возврат из BF-кэша):
            // --initializing убирает transform-transition у иконок, иначе
            // скрытая иконка проедет из центра к границе кнопки.
            this.#switcher.classList.add('header__nav-action-button--initializing');
        }

        this.#switcher.classList.add(`header__nav-action-button--mode-to-${theme}`);

        const handler = () => {
            this.#switcher.classList.remove(`header__nav-action-button--mode-to-${theme}`);
            this.#switcher.classList.add(`header__nav-action-button--mode-${theme}`);
            this.#isSwitching = false;

            if (isImmediately) {
                // Форсим reflow, чтобы финальное положение зафиксировалось без
                // transition, и только потом возвращаем анимацию (снимаем класс).
                void this.#switcher.offsetWidth;
                this.#switcher.classList.remove('header__nav-action-button--initializing');
            }
        };

        if (isImmediately) {
            handler();
        } else {
            setTimeout(handler, 650);
        }
    }

    #removeModCssClasses() {
        this.#switcher.classList.remove(
            'header__nav-action-button--mode-light',
            'header__nav-action-button--mode-to-light',
            'header__nav-action-button--mode-dark',
            'header__nav-action-button--mode-to-dark'
        );
    }

    /**
     * @param {HTMLElement|Document} [root] Default: document
     */
    static autoload(root = document) {
        root.querySelectorAll('.header__nav-action-button--mode').forEach(
            switcher => {
                switcher.themeSwitcher ??= new ThemeSwitcher(switcher);
            }
        );
    }
}
