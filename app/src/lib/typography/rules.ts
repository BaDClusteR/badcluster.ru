import {isMorseLike, isSpacer, mapTextSegments, textSegments} from "./html";
import type {YoDictionaries} from "./eyo";

export type FixKind = "yo" | "dash" | "hyphen" | "ellipsis" | "quotes" | "spaces";
export type FixStats = Partial<Record<FixKind, number>>;

export const FIX_KIND_LABELS: Record<FixKind, string> = {
  yo: "ё",
  dash: "тире",
  hyphen: "дефисы",
  ellipsis: "многоточия",
  quotes: "кавычки",
  spaces: "пробелы"
};

/** A context-dependent «ё» the author has to decide on. */
export interface WordSuggestion {
  /** Offset of the word in the HTML string. */
  offset: number;
  before: string;
  after: string;
}

const LETTER = /[A-Za-zА-Яа-яЁё0-9]/;

/** чём / нём / всём — always with «ё» right after these prepositions, never without them. */
const PREPOSITION_WORDS = new Set(["чем", "нем", "всем"]);
const PREPOSITION_BEFORE = /(?:^|[^А-Яа-яЁё])(?:[Оо]|[Оо]б|[Оо]бо|[Вв]|[Вв]о|[Нн]а|[Пп]ри)(?:\s|&nbsp;)+$/;

/**
 * Ambiguous by the dictionary, but standard spelling has «ё» in every meaning.
 */
const ALWAYS_YO: Record<string, string> = Object.fromEntries(`
  полет:полёт Полет:Полёт проем:проём Проем:Проём подъем:подъём Подъем:Подъём объем:объём
  маневр:манёвр Маневр:Манёвр маневры:манёвры маневре:манёвре маневров:манёвров
  темным:тёмным черным:чёрным твердо:твёрдо боев:боёв козел:козёл живете:живёте
  белесых:белёсых Белесые:Белёсые
  Хрусталев:Хрусталёв Хрусталева:Хрусталёва Хрусталеву:Хрусталёву Хрусталевым:Хрусталёвым
  Хрусталеве:Хрусталёве хрусталевский:хрусталёвский
`.trim().split(/\s+/).map((pair) => pair.split(":")));

/**
 * Ambiguous by the dictionary, but in practice (checked on the whole site) nearly always
 * written with «е» — suggesting them would only add noise.
 */
const RARELY_YO = new Set(`
  перед Перед лет лета лету летом далеко Далеко недалеко Недалеко издалека Издалека далеки
  самое Самое вселенной Вселенной вселенную Вселенную вселенных вселенная Вселенная
  темно Темно черно мертво легонько кубарем умел примет примете примета отеле
  метки метку меток меткой маркер маркера маркеры маркером священный священного священной
  священному священном Священные почтенный почтенной приближенных сложенные сложенных
  раздробленная раздробленное раздвоенный заторможенный посаженных перекошенное перекошенный
  перекошенных перекошенные Перекошенные пройденным пройденной пройденный заряжен заряженному
  заряженной склонен точен пристыженной наряженный Боевой Огневой Пылевых подземку планер сбоев
  шлепок шлепками желчное Желчное желчного желчным желчь Чертовский счетах щекам щеках
  загруженности железок железки жернова Чем
`.trim().split(/\s+/));

const isNumericRange = (s: string, i: number) =>
  /\d(?:\s|&nbsp;)*$/.test(s.slice(0, i)) && /^(?:\s|&nbsp;)*\d/.test(s.slice(i + 1));

function bump(stats: FixStats, kind: FixKind, n = 1) {
  stats[kind] = (stats[kind] ?? 0) + n;
}

/**
 * Unambiguous fixes, safe to apply automatically on save.
 */
export function autoFixHtml(html: string, yo: YoDictionaries): { html: string, stats: FixStats } {
  const stats: FixStats = {};

  // A double-encoded "empty line" shows up as literal "&nbsp;" text on the site.
  if (/^\s*&amp;nbsp;\s*$/.test(html)) {
    bump(stats, "spaces");

    return {html: "&nbsp;", stats};
  }

  if (isSpacer(html) || isMorseLike(html)) {
    return {html, stats};
  }

  let out = html;

  // Spaces: the editor leaves &nbsp; around inline tags and at paragraph edges.
  // Non-breaking spaces are added at render time anyway, so stored ones are noise
  // (except after numbers: «10&nbsp;км», «№&nbsp;5»).
  out = out.replace(/^((?:<[^>]+>)*)(?:\s|&nbsp;)+/, (_m, tags: string) => {
    bump(stats, "spaces");

    return tags;
  });
  out = out.replace(/(?:\s|&nbsp;)+((?:<\/[^>]+>)*)$/, (_m, tags: string) => {
    bump(stats, "spaces");

    return tags;
  });
  out = mapTextSegments(out, (text) => text
    .replace(/&nbsp;/g, (m, i: number, s: string) => {
      if (/[\d№]$/.test(s.slice(0, i))) {
        return m;
      }

      bump(stats, "spaces");

      return " ";
    })
    .replace(/ {2,}/g, () => {
      bump(stats, "spaces");

      return " ";
    })
  );

  // Dashes, ellipses, quotes.
  out = mapTextSegments(out, (text, segment) => {
    let t = text;

    if (segment.lineStart) {
      t = t.replace(/^((?:\s|&nbsp;)*)[–-](?=\s|&nbsp;)/, (_m, lead: string) => {
        bump(stats, "dash");

        return lead + "—";
      });
    }

    // A tag boundary counts as a space: «,</i> – подумал», «бы, –</i> подумал».
    t = t.replace(/(?<=\s|&nbsp;|^)[–-](?=\s|&nbsp;|$)/g, (m, i: number, s: string) => {
      const spaceBefore = /(?:\s|&nbsp;)$/.test(s.slice(0, i));
      const spaceAfter = /^(?:\s|&nbsp;)/.test(s.slice(i + 1));

      // «2008 - 2011» stays as the author wrote it.
      if (!(spaceBefore || spaceAfter) || (m === "-" && !(spaceBefore && spaceAfter)) || isNumericRange(s, i)) {
        return m;
      }

      bump(stats, "dash");

      return "—";
    });

    // «повторил Сергей. –А на самом деле…» — a dash glued to the next word.
    t = t.replace(/(?<=\s|&nbsp;)–(?=[А-ЯЁ])/g, () => {
      bump(stats, "dash");

      return "—";
    });

    // «кого—то», «приятель–мормон» → hyphen.
    t = t.replace(/(?<=[A-Za-zА-Яа-яЁё])[–—](?=[A-Za-zА-Яа-яЁё])/g, () => {
      bump(stats, "hyphen");

      return "-";
    });

    t = t.replace(/(?<![.?!…])\.\.\.(?!\.)/g, () => {
      bump(stats, "ellipsis");

      return "…";
    });

    t = t.replace(/“([^“”]*)”/g, (_m, inner: string) => {
      bump(stats, "quotes");

      return `«${inner}»`;
    });

    if ((t.match(/"/g) ?? []).length % 2 === 0) {
      t = t.replace(/"([^"]+)"/g, (_m, inner: string, i: number, s: string) => {
        bump(stats, "quotes");
        const before = s.slice(0, i);
        const nested = (before.match(/«/g) ?? []).length > (before.match(/»/g) ?? []).length;

        return nested ? `„${inner}“` : `«${inner}»`;
      });
    }

    return t;
  });

  // «ё»: dictionary words plus «о чём», «в нём», «во всём».
  out = mapTextSegments(out, (text) => text.replace(/[А-ЯЁа-яё]+/g, (word, i: number, s: string) => {
    if (!isYoCandidate(word, i, s)) {
      return word;
    }

    // «тёмно-синий», «чёрно-белый».
    if (/^[ТтЧч][е](?:мно|рно)$/.test(word) && /^-[А-Яа-яЁё]/.test(s.slice(i + word.length))) {
      bump(stats, "yo");

      return word.slice(0, 1) + "ё" + word.slice(2);
    }

    const safe = ALWAYS_YO[word] ?? yo.restoreSafe(word);

    if (safe !== word) {
      bump(stats, "yo");

      return safe;
    }

    if (PREPOSITION_WORDS.has(word) && PREPOSITION_BEFORE.test(s.slice(0, i))) {
      const fixed = yo.restoreNotSafe(word);

      if (fixed !== word) {
        bump(stats, "yo");

        return fixed;
      }
    }

    return word;
  }));

  return {html: out, stats};
}

/**
 * Context-dependent «ё» (все/всё, узнает/узнаёт...) for manual review.
 * Run it on already auto-fixed HTML.
 */
export function findWordSuggestions(html: string, yo: YoDictionaries): WordSuggestion[] {
  if (isSpacer(html) || isMorseLike(html)) {
    return [];
  }

  const result: WordSuggestion[] = [];

  for (const segment of textSegments(html)) {
    const re = /[А-ЯЁа-яё]+/g;
    let m: RegExpExecArray | null;

    while ((m = re.exec(segment.text))) {
      const word = m[0];

      if (
        !isYoCandidate(word, m.index, segment.text)
        || RARELY_YO.has(word)
        || PREPOSITION_WORDS.has(word)
        || word in ALWAYS_YO
        || yo.restoreSafe(word) !== word
      ) {
        continue;
      }

      const after = yo.restoreNotSafe(word);

      if (after !== word) {
        result.push({offset: segment.start + m.index, before: word, after});
      }
    }
  }

  return result;
}

function isYoCandidate(word: string, i: number, s: string): boolean {
  if (!/[Ее]/.test(word)) {
    return false;
  }

  // Glued to Latin letters or digits — a typo or an identifier, leave it alone.
  if (LETTER.test(s[i - 1] ?? "") || LETTER.test(s[i + word.length] ?? "")) {
    return false;
  }

  // Abbreviation: «мед. училище».
  return !/^\.(?:\s|&nbsp;)+[а-яё]/.test(s.slice(i + word.length));
}
