/**
 * Helpers for walking the HTML that Editor.js stores in text fields:
 * plain text interleaved with inline tags and entities (&nbsp;, &amp;...).
 */

/** Text inside these tags is code or Morse — never touched. */
const RAW_TAGS = new Set(["kbd", "code", "pre"]);

/** Tags that start a new visual line (a dash right after them opens a line of dialogue). */
const LINE_TAGS = new Set(["br", "p", "li", "div", "blockquote"]);

export interface TextSegment {
  /** Offset of the segment in the whole HTML string. */
  start: number;
  text: string;
  /** The segment starts a paragraph or a line (after <br>, <p>...). */
  lineStart: boolean;
}

/**
 * Splits HTML into text segments between tags, skipping the contents of RAW_TAGS.
 */
export function textSegments(html: string): TextSegment[] {
  const segments: TextSegment[] = [];
  const re = /<(\/?)([a-z0-9]+)[^>]*>/gi;
  let rawDepth = 0;
  let lineStart = true;
  let pos = 0;
  let m: RegExpExecArray | null;

  const pushText = (end: number) => {
    if (end > pos && rawDepth === 0) {
      const text = html.slice(pos, end);
      segments.push({start: pos, text, lineStart});
      if (stripEntities(text).trim() !== "") {
        lineStart = false;
      }
    }
  };

  while ((m = re.exec(html))) {
    pushText(m.index);
    const closing = m[1] === "/";
    const tag = m[2].toLowerCase();

    if (RAW_TAGS.has(tag)) {
      rawDepth = Math.max(0, rawDepth + (closing ? -1 : 1));
    } else if (LINE_TAGS.has(tag) && (!closing || tag === "br")) {
      lineStart = true;
    }

    pos = m.index + m[0].length;
  }

  pushText(html.length);

  return segments;
}

/**
 * Applies `fn` to every text segment (outside tags and RAW_TAGS) and stitches the HTML back.
 */
export function mapTextSegments(
  html: string,
  fn: (text: string, segment: TextSegment, index: number, all: TextSegment[]) => string
): string {
  const segments = textSegments(html);
  let result = "";
  let pos = 0;

  segments.forEach((segment, i) => {
    result += html.slice(pos, segment.start) + fn(segment.text, segment, i, segments);
    pos = segment.start + segment.text.length;
  });

  return result + html.slice(pos);
}

export function stripEntities(text: string): string {
  return text
    .replace(/&nbsp;/g, " ")
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&quot;/g, "\"");
}

/** Visible text: no tags, entities decoded. */
export function toPlainText(html: string): string {
  return stripEntities(html.replace(/<[^>]*>/g, ""));
}

/** An "empty line" paragraph made of &nbsp; / <br> only — kept as is on purpose. */
export function isSpacer(html: string): boolean {
  return /^(?:\s|&nbsp;|<br\s*\/?>)*$/.test(html);
}

/** Morse code and similar dot/dash sequences. */
export function isMorseLike(html: string): boolean {
  const text = toPlainText(html);

  return /[.–—-](?:\s*[.–—-]){5,}/.test(text) && (text.match(/[–—-]/g) ?? []).length >= 4;
}
