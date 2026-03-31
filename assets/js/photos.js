import Lightbox from './lightbox.js';

class PhotoStorage extends Singleton {
    /**
     * @type {HTMLLIElement[]}
     */
    #storage = [];

    /**
     * @param {HTMLLIElement} element
     */
    store(element) {
        this.#storage.push(element);
        element.parentNode.removeChild(element);
    }

    /**
     * @param {string} category
     */
    setCategory(category) {
        Array.from(this.#getRoot().children).forEach(
            (item) => {
                if (!this.#isAppropriateCategory(item, category)) {
                    this.store(item);
                }
            }
        );

        const newStorage = [];

        this.#storage.forEach(
            item => {
                if (this.#isAppropriateCategory(item, category)) {
                    this.#restore(item)
                } else {
                    newStorage.push(item);
                }
            }
        );

        this.#storage = newStorage;
    }

    /**
     * @param {HTMLLIElement} item
     * @param {string} category
     */
    #isAppropriateCategory(item, category) {
        return category === 'all' || this.#getCategories(item).indexOf(category) !== -1;
    }

    /**
     * @param {HTMLLIElement} element
     *
     * @return string[]
     */
    #getCategories(element) {
        const rawValue = String(element.getAttribute('data-category') || '').trim();

        return rawValue
            ? rawValue.split(',')
            : [];
    }

    /**
     * @param {HTMLLIElement} element
     */
    #restore(element) {
        let comment = this.#findComment(element.id);

        if (comment) {
            comment.parentNode.insertBefore(element, comment);
        }
    }

    #findComment(id) {
        const treeWalker = document.createTreeWalker(
            this.#getRoot(),
            NodeFilter.SHOW_COMMENT
        );

        let node;
        while (node = treeWalker.nextNode()) {
            if (node.textContent === ` /${id} `) {
                return node;
            }
        }

        return null;
    }

    #getRoot() {
        return document.querySelector(".photos");
    }
}

class PhotoFilter {
    /**
     * @type {HTMLButtonElement[]}
     */
    #filters = [];

    /**
     * @type {HTMLElement}
     */
    #root;

    constructor(root) {
        this.#root = root;

        this.#initDOM();
        this.#initListeners();
    }

    #initDOM() {
        this.#filters = Array.from(
            this.#root.querySelectorAll('.photo__filter')
        );
    }

    #initListeners() {
        this.#filters.forEach(
            (filter) => {
                filter.addEventListener(
                    'click',
                    () => {
                        this.#setFilter(
                            this.#getFilter(
                                filter
                            )
                        );
                    }
                );
            }
        );
    }

    #setFilter(filter) {
        this.#filters.forEach(
            (filterBtn) => {
                filterBtn.classList.toggle(
                    'active',
                    this.#getFilter(filterBtn) === filter
                );
            }
        );

        PhotoStorage.getInstance().setCategory(filter);
    }

    /**
     * @param {HTMLButtonElement} btn
     */
    #getFilter(btn) {
        return String(btn.getAttribute('data-filter') || 'all');
    }
}

window.lightbox = new Lightbox({
    gallery: ".photo__figure",
    paddingFn: (viewportSize, data) => {
        let bottom = viewportSize.y < 200 ? 0 : 20;
        if (data.element?.parentNode.querySelector('.photo__title')) {
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
        currSlideElement.parentNode.querySelector('.photo__title')?.innerHTML || ''
    )
});

document.querySelectorAll('.photo__filters').forEach(
    /**
     * @param {HTMLElement & {photoFilter?: PhotoFilter}} filters
     */
    (filters) => {
        filters.photoFilter ??= new PhotoFilter(filters);
    }
);
