class Gallery {
    /**
     * @type {HTMLElement}
     */
    #root;

    /**
     * @type {HTMLUListElement}
     */
    #scroller;

    /**
     * @type {HTMLLIElement[]}
     */
    #slides;

    /**
     * @type {HTMLButtonElement}
     */
    #btnPrev;

    /**
     * @type {HTMLButtonElement}
     */
    #btnNext;

    /**
     * @type {HTMLElement}
     */
    #currentCounter;

    /**
     * @param {HTMLElement} root
     */
    constructor(root) {
        this.#root = root;

        this.#initDOM();
        this.#initListeners();
        this.#initObserver();
    }

    #initDOM() {
        this.#scroller = this.#root.querySelector('.post__gallery-scroller');
        this.#slides = Array.from(this.#root.querySelectorAll('.post__gallery-slide'));
        this.#btnPrev = this.#root.querySelector('.post__gallery-btn--prev');
        this.#btnNext = this.#root.querySelector('.post__gallery-btn--next');
        this.#currentCounter = this.#root.querySelector('.post__gallery-counter-current');
    }

    #initListeners() {
        this.#btnPrev.addEventListener('click', this.previous.bind(this));
        this.#btnNext.addEventListener('click', this.next.bind(this));
    }

    #initObserver() {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(
                    (entry, i) => {
                        if (entry.isIntersecting) {
                            this.#setSlide(
                                this.#slides.indexOf(entry.target)
                            );
                        }
                    }
                );
            },
            {
                root: this.#scroller,
                threshold: 0.6
            }
        );

        this.#slides.forEach(
            (slide, i) => {
                observer.observe(slide);

                slide.querySelectorAll('a').forEach(
                    (a) => {
                        a.addEventListener(
                            'focus',
                            () => {
                                this.#setSlide(i);
                                this.#scrollToSlide(i);
                            }
                        );
                    }
                )
            }
        );
    }

    #setSlide(index) {
        index = Math.min(
            this.#slides.length,
            Math.max(
                0,
                index
            )
        );

        this.#currentCounter.textContent = `${index + 1}`;
        this.#setActiveSlide(index);

        this.#btnPrev.disabled = index === 0;
        this.#btnNext.disabled = index === this.#slides.length - 1;
    }

    #setActiveSlide(slideIndex) {
        this.#slides.forEach(
            (slide, i) => {
                slide.classList.toggle(
                    'post__gallery-slide--active',
                    slideIndex === i
                );
            }
        );
    }

    previous() {
        if (this.#getCurrentSlideIndex() > 0) {
            this.#setSlide(
                this.#getCurrentSlideIndex() - 1,
                true
            );

            this.#scrollToSlide(
                this.#getCurrentSlideIndex()
            );
        }
    }

    next() {
        if (!this.#isLastSlide()) {
            this.#setSlide(
                this.#getCurrentSlideIndex() + 1
            );
            this.#scrollToSlide(
                this.#getCurrentSlideIndex()
            );
        }
    }

    #scrollToSlide(index) {
        this.#scroller.scrollTo({
            left: this.#getScrollDistance() * index,
            behavior: 'smooth'
        });
    }

    #getScrollDistance() {
        if (!this.#slides[0]) {
            return 0;
        }

        let result = this.#slides[0].clientWidth + this.#calculateGap();

        if (String(document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl') {
            result = -result;
        }

        return result;
    }

    #calculateGap() {
        return parseInt(
            getComputedStyle(
                this.#scroller
            ).gap
        ) || 0;
    }

    #getCurrentSlideIndex() {
        let index = -1;

        this.#slides.forEach(
            (slide, i) => {
                if (slide.classList.contains('post__gallery-slide--active')) {
                    index = i;
                }
            }
        );

        return index;
    }

    #isLastSlide() {
        return this.#getCurrentSlideIndex() >= this.#slides.length - 1;
    }

    #isFirstSlide() {
        return this.#getCurrentSlideIndex() <= 0;
    }

    /**
     * @param {Element|Document} [root]
     */
    static autoload(root) {
        root ??= document;

        root.querySelectorAll('.post__gallery').forEach(
            /**
             * @param {Element & {galleryController?: Gallery}} gallery
             */
            (gallery) => {
                if (!gallery.galleryController) {
                    gallery.galleryController = new this(gallery);
                }
            }
        );
    }
}
