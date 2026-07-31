class Comment {
    static #getRootAndIdFromBtn(btn) {
        return [btn.closest('.comment'), Comment.getCommentId(btn)];
    }

    static #getLevel(comment) {
        for (let i = 1; i <= 6; i++) {
            if (comment.classList.contains(`comment--level-${i}`)) {
                return i;
            }
        }

        return 0;
    }

    /**
     * @param {HTMLElement} elem
     */
    static getCommentId(elem) {
        return elem.closest('[data-comment-id]')?.getAttribute('data-comment-id') || "";
    }

    static approve(btn) {
        const [root, id] = Comment.#getRootAndIdFromBtn(btn);

        root.classList.add('comment--loading');

        fetch(`/admin/api/comment_approve/${id}`)
            .then(() => {
                Toast.getInstance().success('Успешно', 'Модерация подтверждена');
                root.classList.remove('comment--rejected', 'comment--pending', 'comment--loading');
                root.querySelector('.comment__status-badge')?.remove();
            })
            .catch((reason) => {
                Toast.getInstance().error('Ошибка', 'Не могу подтвердить модерацию комментария. Подробности в консоли.');
                console.warn(`Cannot approve comment #${id}.`, reason);
            });
    }

    static reject(btn) {
        const [root, id] = Comment.#getRootAndIdFromBtn(btn);

        root.classList.add('comment--loading');

        fetch(`/admin/api/comment_reject/${id}`)
        .then(() => {
            Toast.getInstance().success('Успешно', 'Модерация отклонена');
            root.classList.remove('comment--pending', 'comment--loading');
            root.classList.add('comment--rejected');
            const badge = root.querySelector('.comment__status-badge');
            if (badge) {
                badge.innerHTML = 'Отклонен';
            }
        })
        .catch((reason) => {
            Toast.getInstance().error('Ошибка', 'Не могу отклонить модерацию комментария. Подробности в консоли.');
            console.warn(`Cannot approve comment #${id}.`, reason);
        });
    }

    static delete(btn) {
        const [root, id] = Comment.#getRootAndIdFromBtn(btn);
        const level = Comment.#getLevel(root);

        root.classList.add('comment--loading');

        fetch(`/admin/api/comment_delete/${id}`)
        .then(() => {
            let deleted = 0;
            let next = root.nextElementSibling;
            while (next && Comment.#getLevel(next) > level) {
                next.remove();
                deleted++;
                next = root.nextElementSibling;
            }
            root.remove();
            Toast.getInstance().success(
                'Успешно',
                deleted === 0
                    ? 'Комментарий удален'
                    : 'Ветка комментариев удалена'
            );
        })
        .catch((reason) => {
            Toast.getInstance().error('Ошибка', 'Не могу удалить комментарий. Подробности в консоли.');
            console.warn(`Cannot approve comment #${id}.`, reason);
        });
    }
}

document.querySelectorAll('.comment__menu-button--approve').forEach(
    btn => {
        btn.addEventListener('click', () => {
            Comment.approve(btn);
        });
    }
);

document.querySelectorAll('.comment__menu-button--reject').forEach(
    btn => {
        btn.addEventListener('click', () => {
            Comment.reject(btn);
        });
    }
);

document.querySelectorAll('.comment__menu-button--delete').forEach(
    btn => {
        btn.addEventListener('click', () => {
            if (confirm('Действительно удалить комментарий?')) {
                Comment.delete(btn);
            }
        });
    }
);
