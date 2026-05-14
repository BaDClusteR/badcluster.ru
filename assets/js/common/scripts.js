ThemeSwitcher.autoload();

if (window.safari) {
    document.documentElement.classList.add('safari');
}

(function(root) {
    const settingsSwitcher = root.querySelector('.header__nav-action-button--settings');
    const settingsDrawer = root.querySelector('.header__settings--desktop');

    if (settingsSwitcher && settingsDrawer) {
        const setDrawerState = (isOpen) => {
            settingsDrawer.classList.toggle('header__settings--open', isOpen);
            settingsDrawer.querySelectorAll('button').forEach(
                (button) => {
                    if (isOpen) {
                        button.removeAttribute('tabindex');
                    } else {
                        button.setAttribute('tabindex', '-1');
                    }
                }
            );

            settingsSwitcher.classList.toggle('icon-button--pressed', isOpen);
        }

        const isDrawerOpen = () => settingsDrawer.classList.contains('header__settings--open');

        settingsSwitcher.addEventListener(
            'click',
            () => {
                setDrawerState(
                    !isDrawerOpen()
                );
            }
        );

        document.addEventListener(
            'click',
            (e) => {
                /**
                 * @type {HTMLElement}
                 */
                const element = e.target;

                if (
                    !element.closest('.header__nav-action-button--settings')
                    && !element.closest('.header__settings--desktop')
                ) {
                    setDrawerState(false);
                }
            }
        )
    }
})(document);


(function(root) {
    const buttons = root.querySelectorAll('button[commandd="show-modal"],button[commandd="close"]');
    if (buttons.length > 0 && !('commandForElement' in buttons[0])) {
        buttons.forEach(
            (button) => {
                const target = document.getElementById(
                    button.getAttribute('commandfor')
                );
                const command = button.getAttribute('command');

                if (target instanceof HTMLDialogElement) {
                    if (command === 'show-modal') {
                        button.addEventListener(
                            'click',
                            () => {
                                target.showModal();
                            }
                        );
                    } else if (command === 'close') {
                        button.addEventListener(
                            'click',
                            () => {
                                target.close();
                            }
                        )
                    }
                }
            }
        );
    }
})(document);

(function(root) {
    root.querySelectorAll('.modal').forEach(
        /**
         * @param {HTMLDialogElement} dialog
         */
        (dialog) => {
            dialog.addEventListener(
                'click',
                /**
                 * @param {PointerEvent} e
                 */
                (e) => {
                    if (e.target === dialog) {
                        const rect = dialog.getBoundingClientRect();
                        const x = e.clientX;
                        const y = e.clientY;

                        if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                            dialog.close();
                        }
                    }
                }
            );
        }
    )
})(document);

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const scrollBarSizeHandler = () => {
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.documentElement.style.setProperty('--scrollbar-width', `${scrollbarWidth}px`);
        }

        scrollBarSizeHandler();
        (new ResizeObserver(scrollBarSizeHandler)).observe(document.documentElement);
    }
);
