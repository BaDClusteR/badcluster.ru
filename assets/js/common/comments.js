class CommentController {
    /**
     * @type HTMLFormElement
     */
    #form;

    /**
     * @type HTMLInputElement
     */
    #nickname;

    /**
     * @type HTMLTextAreaElement
     */
    #comment;

    /**
     * @type HTMLSpanElement
     */
    #commentErrorContainer;

    /**
     * @type HTMLButtonElement
     */
    #sendBtn;

    constructor(root) {
        this.#initDOM(root);
        this.#initListeners();
    }

    #initDOM(root) {
        this.#form = root.classList.contains("comments__form")
            ? root
            : root.querySelector(".comments__form");

        this.#nickname = this.#form.querySelector("input[name='nickname']");
        this.#comment = this.#form.querySelector("[name='comment']");
        this.#commentErrorContainer = this.#form.querySelector("[name='comment'] ~ .comment__field-error");
        this.#sendBtn = this.#form.querySelector("button[type='submit']");

        this.#comment.removeAttribute("required");
    }

    #initListeners() {
        this.#comment.addEventListener(
            "input",
            () => {
                this.#removeInvalidMarkers();
            }
        );

        this.#comment.addEventListener(
            "keydown",
            (e) => {
                if (
                    e.key === "Enter"
                    && !e.shiftKey
                    && (
                        e.ctrlKey
                        || e.metaKey
                    )
                ) {
                    this.#sendBtn.click();
                }
            }
        );

        this.#form.addEventListener(
            "submit",
            (e) => {
                e.preventDefault();

                this.#removeInvalidMarkers();

                if (this.#validate()) {
                    this.#setLoadingState(true);

                    const data = new FormData(this.#form);
                    data.set('nickname', this.#nickname.value);
                    data.set('comment', this.#comment.value);
                    if (this.#form.querySelector('input[name="parentId"]')) {
                        data.set('parentId', this.#form.querySelector('input[name="parentId"]').value);
                    }

                    fetch(
                        this.#form.action,
                        {
                            method: "POST",
                            body: data
                        }
                    ).then(response => response.json())
                    .then(data => {
                        if (data?.status !== 'success') {
                            Toast.getInstance().error(
                                "Ошибка отправки коммента",
                                data?.message ?? ""
                            );
                        } else {
                            Toast.getInstance().success(
                                "Коммент отправлен",
                                data?.message ?? "Спасибо за мысли!"
                            );
                            this.#form.reset();
                        }
                    }).catch(error => {
                        Toast.getInstance().error(
                            "Ошибка отправки коммента",
                            error.message
                        );
                    }).finally(() => {
                        this.#setLoadingState(false);
                    });
                }
            }
        );
    }

    #removeInvalidMarkers() {
        this.#comment.classList.remove("comment__field--invalid");
        this.#commentErrorContainer.innerHTML = "";
    }

    /**
     * @param {boolean} isLoading
     */
    #setLoadingState(isLoading) {
        this.#nickname.disabled = isLoading;
        this.#comment.disabled = isLoading;
        this.#sendBtn.disabled = isLoading;
        this.#sendBtn.classList.toggle('btn--loading', isLoading);
    }

    #validate() {
        if (!this.#comment.value.trim()) {
            this.#comment.classList.add("comment__field--invalid");
            this.#commentErrorContainer.innerHTML = this.#getRandomErrorText();
            return false;
        }

        return true;
    }

    #getRandomErrorText() {
        const texts = [
            "Не будьте молчаливым как Гордон Фримен. Напишите хоть слово!",
            "Вы пытаетесь отправить пустоту. Сервер в недоумении.",
            "Комментарий пуст. Кот пробежал по Enter'у? :)",
            "Я могу попытаться прочитать между строк, но тут вообще ничего нет :)",
            "Вы забыли самое главное — написать комментарий.",
            "Краткость — сестра таланта, но не до такой же степени!",
            "Эй, здесь пусто! Напишите, что думаете.",
            "Error: content is null. Напишите хоть что-нибудь."
        ];

        return texts[
            Math.floor(Math.random() * texts.length)
        ];
    }

    static autoload() {
        document.querySelectorAll(".comments__form").forEach(
            /**
             * @param {HTMLFormElement & {controller?: CommentController}} form
             */
            (form) => {
                if (!form.controller) {
                    form.controller = new CommentController(form);
                }
            }
        );
    }
}

CommentController.autoload();
