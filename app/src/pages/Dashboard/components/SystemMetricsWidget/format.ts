export function formatBytes(bytes: number): string {
    if (bytes >= 2 ** 30) {
        return `${(bytes / 2 ** 30).toFixed(1).replace(/\.0$/, "")} GB`;
    }

    if (bytes >= 2 ** 20) {
        return `${(bytes / 2 ** 20).toFixed(1).replace(/\.0$/, "")} MB`;
    }

    if (bytes >= 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${Math.round(bytes)} B`;
}

/** Ближайшее сверху «красивое» значение: 1/2/5 × 10^n */
export function niceCeil(value: number): number {
    if (value <= 0) {
        return 1;
    }

    const power = 10 ** Math.floor(Math.log10(value));
    const mantissa = value / power;

    if (mantissa <= 1) {
        return power;
    }

    if (mantissa <= 2) {
        return 2 * power;
    }

    if (mantissa <= 5) {
        return 5 * power;
    }

    return 10 * power;
}

/**
 * «Красивый» потолок для байтовых величин: округляем в единицах КБ/МБ/ГБ,
 * чтобы деления оси попадали на круглые значения, а не «488 KB»
 */
export function niceCeilBytes(bytes: number): number {
    let unit = 1;

    while (bytes / unit >= 1024) {
        unit *= 1024;
    }

    return niceCeil(bytes / unit) * unit;
}
