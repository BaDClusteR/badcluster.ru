import type {OutputData} from "@editorjs/editorjs";
import {loadYoDictionaries} from "./eyo";
import {toPlainText} from "./html";
import {autoFixHtml, findWordSuggestions, type FixKind, type FixStats} from "./rules";

type Path = (string | number)[];

/**
 * Block fields with human-readable HTML. Terminal (code/ciphers) and raw HTML blocks
 * are skipped on purpose.
 */
const TEXT_FIELDS: Record<string, string[]> = {
  paragraph: ["text"],
  header: ["text", "tocText"],
  quote: ["text", "translatedText"],
  list: ["items"],
  media: ["caption"],
  gallery: ["captions"],
  toc: ["items"]
};

interface TextRef {
  blockIndex: number,
  blockId?: string,
  path: Path,
  html: string
}

export interface Suggestion {
  /** Stable key, also used to remember dismissed suggestions. */
  key: string,
  field: string,
  blockId?: string,
  path: Path,
  offset: number,
  before: string,
  after: string,
  contextBefore: string,
  contextAfter: string,
  /** The context is cut off (there is more text before / after it). */
  truncatedBefore: boolean,
  truncatedAfter: boolean
}

const CONTEXT_LENGTH = 70;

export function isBlocksDocument(value: unknown): value is OutputData {
  return !!value && typeof value === "object" && Array.isArray((value as OutputData).blocks);
}

function collectTexts(doc: OutputData): TextRef[] {
  const refs: TextRef[] = [];

  const walk = (value: unknown, path: Path, blockIndex: number, blockId?: string) => {
    if (typeof value === "string") {
      refs.push({blockIndex, blockId, path, html: value});
    } else if (Array.isArray(value)) {
      // Lists: strings or {content, items}; TOC: {text, children}; gallery: strings.
      value.forEach((item, i) => {
        if (typeof item === "string") {
          walk(item, [...path, i], blockIndex, blockId);
        } else if (item && typeof item === "object") {
          for (const key of ["content", "text"]) {
            if (typeof item[key] === "string") {
              walk(item[key], [...path, i, key], blockIndex, blockId);
            }
          }

          for (const key of ["items", "children"]) {
            if (Array.isArray(item[key])) {
              walk(item[key], [...path, i, key], blockIndex, blockId);
            }
          }
        }
      });
    }
  };

  doc.blocks.forEach((block, blockIndex) => {
    for (const field of TEXT_FIELDS[block.type] ?? []) {
      walk(
        (block.data as Record<string, unknown> | undefined)?.[field],
        ["blocks", blockIndex, "data", field],
        blockIndex,
        block.id
      );
    }
  });

  return refs;
}

function groupBy<V>(items: V[], keyFn: (item: V) => string): Map<string, V[]> {
  const map = new Map<string, V[]>();

  for (const item of items) {
    const key = keyFn(item);
    map.set(key, [...(map.get(key) ?? []), item]);
  }

  return map;
}

function setAtPath(root: unknown, path: Path, value: string) {
  let node = root as Record<string | number, unknown>;

  for (const key of path.slice(0, -1)) {
    node = node[key] as Record<string | number, unknown>;
  }

  node[path[path.length - 1]] = value;
}

function mergeStats(target: FixStats, source: FixStats) {
  for (const [kind, n] of Object.entries(source) as [FixKind, number][]) {
    target[kind] = (target[kind] ?? 0) + n;
  }
}

export function countFixes(stats: FixStats): number {
  return Object.values(stats).reduce((sum, n) => sum + (n ?? 0), 0);
}

/**
 * Applies unambiguous fixes to every blocks document among the form values.
 * Returns the same object when nothing changed.
 */
export async function autoFixValues<T extends Record<string, unknown>>(
  values: T
): Promise<{ values: T, stats: FixStats, changedFields: string[] }> {
  const stats: FixStats = {};
  const changedFields: string[] = [];
  const fields = Object.keys(values).filter((key) => isBlocksDocument(values[key]));

  if (fields.length === 0) {
    return {values, stats, changedFields};
  }

  const yo = await loadYoDictionaries();
  let result = values;

  for (const field of fields) {
    const doc = structuredClone(values[field]) as OutputData;
    let changed = false;

    for (const ref of collectTexts(doc)) {
      const fixed = autoFixHtml(ref.html, yo);

      if (fixed.html !== ref.html) {
        setAtPath(doc, ref.path, fixed.html);
        mergeStats(stats, fixed.stats);
        changed = true;
      }
    }

    if (changed) {
      result = {...result, [field]: doc};
      changedFields.push(field);
    }
  }

  return {values: result, stats, changedFields};
}

/**
 * Context-dependent «ё» across all blocks documents among the form values.
 */
export async function findSuggestions(values: Record<string, unknown>): Promise<Suggestion[]> {
  const yo = await loadYoDictionaries();
  const result: Suggestion[] = [];

  for (const field of Object.keys(values)) {
    const doc = values[field];

    if (!isBlocksDocument(doc)) {
      continue;
    }

    for (const ref of collectTexts(doc)) {
      for (const word of findWordSuggestions(ref.html, yo)) {
        const fullBefore = toPlainText(ref.html.slice(0, word.offset));
        const fullAfter = toPlainText(ref.html.slice(word.offset + word.before.length));
        const contextBefore = fullBefore.slice(-CONTEXT_LENGTH);
        const contextAfter = fullAfter.slice(0, CONTEXT_LENGTH);

        result.push({
          key: `${word.before}|${contextBefore.slice(-30)}|${contextAfter.slice(0, 30)}`,
          field,
          blockId: ref.blockId,
          path: ref.path,
          offset: word.offset,
          before: word.before,
          after: word.after,
          contextBefore,
          contextAfter,
          truncatedBefore: fullBefore.length > CONTEXT_LENGTH,
          truncatedAfter: fullAfter.length > CONTEXT_LENGTH
        });
      }
    }
  }

  return result;
}

/**
 * Applies accepted suggestions. Skips any whose word is no longer at its offset
 * (the text was edited after the check).
 */
export function applySuggestions<T extends Record<string, unknown>>(
  values: T,
  suggestions: Suggestion[]
): { values: T, applied: number } {
  const byField = groupBy(suggestions, (s) => s.field);
  let result = values;
  let applied = 0;

  for (const [field, list] of byField) {
    const doc = structuredClone(values[field]) as OutputData;
    const byPath = groupBy(list, (s) => JSON.stringify(s.path));

    for (const group of byPath.values()) {
      const path = group[0].path;
      let node: unknown = doc;

      for (const key of path) {
        node = (node as Record<string | number, unknown>)?.[key];
      }

      if (typeof node !== "string") {
        continue;
      }

      let html = node;

      for (const s of [...group].sort((a, b) => b.offset - a.offset)) {
        if (html.slice(s.offset, s.offset + s.before.length) === s.before) {
          html = html.slice(0, s.offset) + s.after + html.slice(s.offset + s.before.length);
          applied++;
        }
      }

      setAtPath(doc, path, html);
    }

    result = {...result, [field]: doc};
  }

  return {values: result, applied};
}
