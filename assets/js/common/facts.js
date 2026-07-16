document.querySelectorAll('.fact__next').forEach(
    (btn) => {
        const contentContainer = btn.parentNode.querySelector('.fact__content');
        const contentWrapper = contentContainer?.firstElementChild;
        const root = contentContainer.parentNode;

        const setLoadingState = (isLoading) => {
            contentContainer.classList.toggle('fact__content--loading', isLoading);
            btn.toggleAttribute('disabled', isLoading);
            root.querySelector('.fact__next-text').setAttribute('aria-hidden', isLoading ? 'true' : 'false');
            root.querySelector('.fact__next-loader').setAttribute('aria-hidden', isLoading ? 'false' : 'true');
        }

        const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

        btn.addEventListener('click', async () => {
            if (contentContainer) {
                setLoadingState(true);
                try {
                    await delay(600);
                    contentContainer.scrollTop = 0;

                    contentWrapper.innerHTML = await fetch('/fact').then(
                        response => response.text()
                    );
                } catch (Error) {
                    Toast.getInstance().error(Error.text);
                } finally {
                    setLoadingState(false);
                }
            }
        });
    }
);
