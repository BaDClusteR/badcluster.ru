#!/usr/bin/env bash
#
# Пересобирает админку: основную аппу (app/) и SPA-подмодули модулей
# (modules/<Имя>/app, module federation). Зависимости ставятся через npm ci —
# ровно по package-lock.json, lock-файлы не меняются (иначе следующий git pull
# на сервере споткнется о них).
#
#   bin/build-admin.sh                 # всё
#   bin/build-admin.sh app Books       # только основная аппа и модуль Books
#   bin/build-admin.sh --no-install    # без npm ci, только сборка
#
# Перед остальными ставятся зависимости packages/admin-types: модули и аппа
# импортируют его исходники, и без его node_modules tsc не найдет react.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
INSTALL=1
TARGETS=()

for arg in "$@"; do
    case "$arg" in
        --no-install) INSTALL=0 ;;
        -h|--help) sed -n '3,13p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *) TARGETS+=("$arg") ;;
    esac
done

# Без аргументов — основная аппа и все модули, у которых есть своя аппа
if [ ${#TARGETS[@]} -eq 0 ]; then
    TARGETS=(app)

    for dir in "$ROOT"/modules/*/app; do
        [ -f "$dir/package.json" ] && TARGETS+=("$(basename "$(dirname "$dir")")")
    done
fi

target_dir() {
    if [ "$1" = "app" ]; then
        echo "$ROOT/app"
    else
        echo "$ROOT/modules/$1/app"
    fi
}

for target in "${TARGETS[@]}"; do
    if [ ! -f "$(target_dir "$target")/package.json" ]; then
        echo "Не знаю, что собирать: \"$target\" (нет $(target_dir "$target")/package.json)" >&2
        exit 1
    fi
done

if [ "$INSTALL" -eq 1 ]; then
    echo "==> packages/admin-types: npm ci"
    (cd "$ROOT/packages/admin-types" && npm ci --no-audit --no-fund --loglevel=error > /dev/null)
fi

for target in "${TARGETS[@]}"; do
    dir="$(target_dir "$target")"
    started=$SECONDS
    echo "==> $target"

    if [ "$INSTALL" -eq 1 ]; then
        (cd "$dir" && npm ci --no-audit --no-fund --loglevel=error > /dev/null)
    fi

    # Вывод vite длинный и с предупреждениями о таймингах: показываем его, только если сборка упала
    if ! output="$(cd "$dir" && npm run build 2>&1)"; then
        echo "$output" >&2
        echo "Сборка $target упала" >&2
        exit 1
    fi

    # Не роняет сборку, но значит, что у модуля не хватает типов (например, @types/react)
    if grep -q "TYPE-001" <<< "$output"; then
        echo "    внимание: не сгенерировались типы module federation (TYPE-001), см. npm run build в $dir"
    fi

    echo "    собрано за $((SECONDS - started)) с"
done

echo "Готово: ${TARGETS[*]}"
