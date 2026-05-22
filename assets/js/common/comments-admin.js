class Comment {
    static reply(btn) {
        Comment.clearReply();

        const [root, id] = Comment.#getRootAndIdFromBtn(btn);
        const nextSibling = Comment.#getReplyTextPlace();
        const clone = root.cloneNode(true);
        clone.classList.remove(
            'comment--loading', 'comment--pending', 'comment--rejected',
            'comment--level-1', 'comment--level-2', 'comment--level-3',
            'comment--level-4', 'comment--level-5', 'comment--level-6'
        );
        clone.querySelectorAll('.comment__status-badge, .comment__menu').forEach(
            elem => {
                elem.remove();
            }
        );
        const clearReplyBtn = document.createElement('button');
        clearReplyBtn.classList.add('comment__reply-clear');
        clearReplyBtn.addEventListener('click', () => {
            Comment.clearReply();
        });
        clone.appendChild(clearReplyBtn);

        nextSibling.parentNode.insertBefore(clone, nextSibling);
        Comment.#getParentNodeInput().value = id;
        document.getElementById('comment').focus();
    }

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

    static clearReply() {
        document.querySelectorAll('.comments__form .comment').forEach(
            elem => {
                elem.remove();
            }
        );
        Comment.#getParentNodeInput().value = '';
    }

    static #getReplyTextPlace() {
        return document.getElementById('comment-success');
    }

    static #getParentNodeInput() {
        return document.querySelector('input[name="parentId"]');
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

document.querySelectorAll('.comment__menu-button--reply').forEach(
    btn => {
        btn.addEventListener('click', (e) => {
            Comment.reply(btn);
        });
    }
);

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
