/**
 * Lazy-loaded «ё» dictionaries (eyo-kernel, ~1 MB) — only fetched when the typographer runs.
 *
 * safe: words where «е» → «ё» is unambiguous (еще → ещё);
 * notSafe: words that depend on context (все/всё, узнает/узнаёт).
 */
export interface YoDictionaries {
  restoreSafe: (word: string) => string;
  restoreNotSafe: (word: string) => string;
}

let loading: Promise<YoDictionaries> | null = null;

export function loadYoDictionaries(): Promise<YoDictionaries> {
  loading ??= import("eyo-kernel").then(({Dictionary, safeDictionary, notSafeDictionary}) => {
    const safe = new Dictionary();
    safe.set(safeDictionary);

    const notSafe = new Dictionary();
    notSafe.set(notSafeDictionary);

    return {
      restoreSafe: (word: string) => safe.restoreWord(word),
      restoreNotSafe: (word: string) => notSafe.restoreWord(word)
    };
  }).catch((err) => {
    loading = null;
    throw err;
  });

  return loading;
}
