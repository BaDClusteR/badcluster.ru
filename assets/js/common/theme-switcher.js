/**
 * @typedef {(string) => void} ThemeSwitcherCallback
 */

class ThemeSwitcher {
    static SWITCH_DURATION = 650;
    static SETTLE_DURATION = 200;

    static CSS_CLASS_FROZEN = 'header__nav-action-button--initializing';

    /**
     * @type {HTMLButtonElement}
     */
    #switcher;

    #isSwitching = false;

    /**
     * Страница только что восстановлена из BF-кэша: всё, что прилетит в этом
     * окне, применяем мгновенно.
     */
    #isSettling = false;

    #settleTimeout = null;

    /**
     * Тема, под которую сейчас выставлены иконки.
     *
     * @type {string}
     */
    #appliedTheme;

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
        this.#appliedTheme = this.#theme.get();

        this.#initListeners();
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
                this.#syncTheme(theme, immediate);
            }
        );

        // Системную тему страница подхватывает сама, но иконкам нужна
        // промежуточная фаза, иначе они поедут в новое положение покоя напрямую
        // через центр кнопки. Клик сюда не попадает: prefers-color-scheme — это
        // настройка ОС, на data-theme она не реагирует.
        //
        // event.matches не смотрим: при восстановлении из BF-кэша change
        // прилетает и тогда, когда системная тема не менялась, — спрашиваем
        // актуальную тему сами, а лишние события отсекает #syncTheme().
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener(
            'change',
            () => {
                this.#syncTheme(
                    this.#theme.get(),
                    false
                );
            }
        );
    }

    switchTheme() {
        if (this.#isSwitching) {
            return;
        }

        const theme = this.#theme.get();
        this.setTheme(
            theme === Theme.LIGHT
                ? Theme.DARK
                : Theme.LIGHT
        );
    }

    setTheme(theme) {
        if (this.#isSwitching) {
            return;
        }

        this.#theme.set(theme);
    }

    #syncTheme(theme, isImmediately) {
        // Анимируем только реальную смену темы: положение покоя иконок задаёт
        // CSS, и если тема та же, двигать нечего.
        if (this.#isSwitching || (theme === this.#appliedTheme && !isImmediately)) {
            return;
        }

        this.#appliedTheme = theme;
        this.#doSetTheme(theme, isImmediately || this.#isSettling);
    }

    #doSetTheme(theme, isImmediately) {
        if (isImmediately) {
            this.#applyImmediately();

            return;
        }

        this.#isSwitching = true;
        this.#switcher.classList.add(`header__nav-action-button--mode-to-${theme}`);

        setTimeout(
            () => {
                // В покое солнце и луна меняются сторонами кнопки, поэтому
                // снятие промежуточного класса гасим — иначе уехавшая иконка
                // проедет весь путь обратно через центр.
                this.#freezeIcons();
                this.#removeModCssClasses();
                this.#unfreezeIcons();

                this.#isSwitching = false;
            },
            ThemeSwitcher.SWITCH_DURATION
        );
    }

    /**
     * Возврат из BF-кэша: положение покоя иконок уже задано CSS, нам остаётся
     * погасить анимацию, чтобы всё нужное применилось мгновенно. Гасим с
     * запасом по времени — события восстановления приходят не одним куском.
     */
    #applyImmediately() {
        this.#isSettling = true;
        this.#freezeIcons();
        this.#removeModCssClasses();

        clearTimeout(this.#settleTimeout);
        this.#settleTimeout = setTimeout(
            () => {
                this.#isSettling = false;
                this.#unfreezeIcons();
            },
            ThemeSwitcher.SETTLE_DURATION
        );
    }

    #freezeIcons() {
        this.#switcher.classList.add(ThemeSwitcher.CSS_CLASS_FROZEN);
    }

    #unfreezeIcons() {
        // Форсим reflow, чтобы положение зафиксировалось без transition,
        // и только потом возвращаем анимацию.
        void this.#switcher.offsetWidth;
        this.#switcher.classList.remove(ThemeSwitcher.CSS_CLASS_FROZEN);
    }

    #removeModCssClasses() {
        this.#switcher.classList.remove(
            'header__nav-action-button--mode-to-light',
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
