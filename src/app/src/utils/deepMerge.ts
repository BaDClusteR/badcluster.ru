import {StringKeyObject} from "@/types";

function isObject(item: any): boolean {
    // Нам важно, чтобы это был именно объект-контейнер { ... }, а не null или массив
    return (item && typeof item === 'object' && !Array.isArray(item));
}

export default function deepMerge(target: StringKeyObject, source: StringKeyObject): StringKeyObject {
    // Создаем копию target, чтобы не мутировать исходный объект
    let output = { ...target };

    if (isObject(target) && isObject(source)) {
        Object.keys(source).forEach(key => {
            const sourceValue = source[key];
            const targetValue = target[key];

            if (isObject(sourceValue)) {
                if (key in target && isObject(targetValue)) {
                    // Если оба — объекты, идем глубже
                    output[key] = deepMerge(targetValue, sourceValue);
                } else {
                    // Если в цели нет такого ключа или там не объект — просто копируем объект из источника
                    output[key] = { ...sourceValue };
                }
            } else {
                // Если пришел null или любой примитив (string, number, boolean) —
                // он просто перезаписывает то, что было в target
                output[key] = sourceValue;
            }
        });
    }
    return output;
}
