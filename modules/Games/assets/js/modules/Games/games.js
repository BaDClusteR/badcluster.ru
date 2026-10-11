class GamesFilter {
    static #ROMAN = ['', 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix'];

    /**
     * Отдельно стоящая цифра 1–9: не часть числа и не прилипла к слову
     */
    static #DIGIT = /(?<![\p{L}\p{N}])[1-9](?![\p{L}\p{N}])/gu;

    /**
     * @type HTMLInputElement
     */
    #input;

    /**
     * @type {{element: HTMLLIElement, title: string, romanTitle: string}[]}
     */
    #games;

    /**
     * @type HTMLElement
     */
    #emptyState;

    /**
     * @param {HTMLInputElement} input
     * @param {HTMLElement} root
     */
    constructor(input, root) {
        this.#input = input;
        this.#emptyState = root.querySelector('.games__empty');
        this.#games = Array.from(
            document.getElementById(input.getAttribute('aria-controls'))?.querySelectorAll(':scope > .game') || []
        ).map(
            element => {
                const title = GamesFilter.#normalize(element.querySelector('.game__name')?.textContent || '');

                return {
                    element,
                    title,
                    romanTitle: title.replace(GamesFilter.#DIGIT, digit => GamesFilter.#ROMAN[digit]),
                };
            }
        );

        this.#initListeners(root);

        // Браузер может восстановить значение поля при возврате на страницу
        this.#filter();
    }

    #initListeners(root) {
        this.#input.addEventListener('input', () => this.#filter());

        this.#input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.#input.value) {
                e.preventDefault();
                this.#clear();
            }
        });

        root.querySelectorAll('.games__search-clear, .games__empty-reset').forEach(
            button => button.addEventListener('click', () => this.#clear())
        );
    }

    #clear() {
        this.#input.value = '';
        this.#filter();
        this.#input.focus();
    }

    #filter() {
        const query = GamesFilter.#normalize(this.#input.value);
        const romanQuery = GamesFilter.#buildRomanQuery(query);
        let visibleCount = 0;

        this.#games.forEach(
            ({element, title, romanTitle}) => {
                const isVisible = title.includes(query) || romanQuery.test(romanTitle);

                element.hidden = !isVisible;
                visibleCount += isVisible ? 1 : 0;
            }
        );

        if (this.#emptyState) {
            this.#emptyState.hidden = visibleCount > 0 || !this.#games.length;
        }
    }

    /**
     * Регистр не учитываем, «ё» приравниваем к «е»
     *
     * @param {string} value
     *
     * @return string
     */
    static #normalize(value) {
        return value.trim().toLocaleLowerCase('ru').replaceAll('ё', 'е');
    }

    /**
     * Арабские цифры 1–9 считаем синонимами римских: в названиях они заранее переведены
     * в римские (romanTitle), а цифры из запроса превращаются в римские с границами слова —
     * иначе «2» нашлось бы и в «iii». Римские цифры из запроса остаются как есть, поэтому
     * недопечатанное «Black Mirror I» по-прежнему находит и II, и III.
     *
     * @param {string} query
     *
     * @return RegExp
     */
    static #buildRomanQuery(query) {
        const pattern = query
            .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            .replace(
                GamesFilter.#DIGIT,
                digit => `(?<![\\p{L}\\p{N}])${GamesFilter.#ROMAN[digit]}(?![\\p{L}\\p{N}])`
            );

        return new RegExp(pattern, 'u');
    }

    static autoload() {
        const input = document.getElementById('games-search');

        if (input) {
            new GamesFilter(input, input.closest('.content-area') || document.documentElement);
        }
    }
}

GamesFilter.autoload();
