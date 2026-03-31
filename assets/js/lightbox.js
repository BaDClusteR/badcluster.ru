export default class Lightbox {
    #lightbox;

    #getDefaultSettings() {
        const arrowSVG = this.#getArrowSVG();

        return {
            gallery: ".post__image-figure, .post__gallery",
            children: "a.lightbox",

            mainClass: "gallery",
            bgOpacity: 1,
            showHideAnimationType: 'fade',
            paddingFn: (viewportSize, data) => {
                let bottom = viewportSize.y < 200 ? 0 : 20;
                if (data.element?.nextElementSibling?.classList.contains('post__image-text')) {
                    bottom += 40;
                }
                return {
                    top: viewportSize.y < 200 ? 0 : 60,
                    bottom,
                    left: viewportSize.x < 420 ? 0 : 60,
                    right: viewportSize.x < 420 ? 0 : 60
                };
            },
            customCaptionFn: (currSlideElement) => (
                currSlideElement.parentNode.querySelector('.post__image-text')?.innerHTML || ''
            ),

            arrowPrevSVG: arrowSVG,
            arrowNextSVG: arrowSVG,
            closeSVG: '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="pswp__icn"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
            zoomSVG: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round"></circle><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M8 11H14" class="pswp__zoom-icn-bar-h" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><path d="M11 8V14M8 11H14" stroke="currentColor" class="pswp__zoom-icn-bar-v" stroke-width="2" stroke-linecap="round"></path></svg>',

            indexIndicatorSep: " из ",
            errorMsg: "Не могу загрузить картинку :(",
            closeTitle: "Закрыть",
            zoomTitle: "Зум",
            arrowPrevTitle: "Предыдущий",
            arrowNextTitle: "Следующий",

            pswpModule: () => import("./photoswipe.esm.min.js"),
        }
    }

    #getArrowSVG() {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    #hasGalleryImages() {
        return !!document.querySelector("a.lightbox, a.photo__link");
    }

    #initLightbox(settings) {
        if (this.#hasGalleryImages()) {
            const defaults = this.#getDefaultSettings();
            if (!this.#lightbox) {
                import('./photoswipe-lightbox.esm.min.js').then(
                    (module) => {
                        const lightboxSettings = Object.assign(
                            {},
                            defaults,
                            settings || {}
                        );

                        this.#lightbox = new module.default(lightboxSettings);

                        this.#lightbox.on('uiRegister', () => {
                            this.#lightbox.pswp.ui.registerElement({
                                name: 'download-button',
                                order: 8,
                                isButton: true,
                                tagName: 'a',
                                html: '<svg width="24" height="24" aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 13V19C18 19.5304 17.7893 20.0391 17.4142 20.4142C17.0391 20.7893 16.5304 21 16 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V8C3 7.46957 3.21071 6.96086 3.58579 6.58579C3.96086 6.21071 4.46957 6 5 6H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /><path d="M15 3H21V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /><path d="M10 14L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>',

                                onInit: (el, pswp) => {
                                    el.setAttribute('target', '_blank');
                                    el.setAttribute('rel', 'noopener');
                                    pswp.on('change', () => {
                                        el.href = pswp.currSlide.data.src;
                                    });
                                }
                            });

                            this.#lightbox.pswp.ui.registerElement({
                                name: 'custom-caption',
                                order: 9,
                                isButton: false,
                                appendTo: 'root',
                                html: 'Caption text',
                                onInit: (el) => {
                                    this.#lightbox.pswp.on('change', () => {
                                        const currSlideElement = this.#lightbox.pswp.currSlide.data.element;
                                        let captionHTML = '';
                                        if (currSlideElement) {
                                            captionHTML = lightboxSettings.customCaptionFn(currSlideElement);
                                        }
                                        el.innerHTML = captionHTML || '';
                                    });
                                }
                            });
                        });

                        this.#lightbox.init();
                        this.#lightbox.addFilter('uiElement', (element, data) => {
                            if (data.name === 'preloader') {
                                element.innerHTML = '<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="pswp__icn" aria-hidden="true"><circle cx="50" cy="50" fill="none" stroke-linecap="round" r="40" stroke-width="4" stroke="#FFF" stroke-dasharray="62.832 62.832" transform="rotate(162 50 50)"><animateTransform attributeName="transform" type="rotate" calcMode="linear" values="0 50 50;360 50 50" keyTimes="0;1" dur="1s" begin="0s" repeatCount="indefinite"/></circle></svg>';
                            }

                            return element;
                        });
                    }
                );
            }
        }
    }

    /**
     * @param [settings]
     */
    constructor(settings) {
        this.#initLightbox(settings);
    }
}
