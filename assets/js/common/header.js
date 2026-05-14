(function(document) {
    const burgerBtn = document.querySelector('.header__nav-action-button--menu');

    burgerBtn?.addEventListener(
        'click',
        () => {
            const bodyClassList = document.body.classList;
            const menuColumn = document.querySelector('.header__column--menu');

            if (bodyClassList.contains('body--menu-open')) {
                menuColumn?.classList?.remove('header__column--menu-open');
                burgerBtn.classList.remove('header__nav-action-button--menu-open');

                setTimeout(
                    () => {
                        bodyClassList.remove('body--menu-open');
                    },
                    250
                )
            } else {
                bodyClassList.add('body--menu-open');
                burgerBtn.classList.add('header__nav-action-button--menu-open');

                requestAnimationFrame(
                    () => {

                        if (menuColumn) {
                            menuColumn.classList.toggle(
                                'header__column--menu-open',
                                bodyClassList.contains('body--menu-open')
                            );
                        }
                    }
                );
            }
        }
    );
})(document);
