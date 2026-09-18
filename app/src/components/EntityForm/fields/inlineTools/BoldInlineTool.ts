// noinspection JSUnusedGlobalSymbols

import type {API, InlineTool} from "@editorjs/editorjs";

/**
 * Inline tool that wraps selected text in <b>...</b>, replacing Editor.js's
 * built-in bold.
 *
 * The built-in one is `document.execCommand('bold')` plus
 * `document.queryCommandState('bold')`, and both ask the browser whether the
 * selection is *already* bold by looking at the computed font-weight. Gecko
 * counts anything above 400 as bold, and the editor's body text sits at 440
 * (variable font), so in Firefox every paragraph looks already-bold: the button
 * lights up and the command un-bolds instead of bolding. Chrome and Safari draw
 * the line higher and happen to work.
 *
 * Deciding from the DOM instead of from computed styles makes it behave the
 * same everywhere, at any font weight.
 *
 * <strong> is recognised too, since pasted content brings it in.
 */
export class BoldInlineTool implements InlineTool {
  static get isInline(): boolean {
    return true;
  }

  static get shortcut(): string {
    return "CMD+B";
  }

  static get sanitize() {
    return {
      b: {},
      strong: {}
    };
  }

  static get title(): string {
    return "Bold";
  }

  private api: API;
  private button!: HTMLButtonElement;
  private active = false;

  constructor({api}: { api: API }) {
    this.api = api;
  }

  render(): HTMLButtonElement {
    this.button = document.createElement("button");
    this.button.type = "button";
    this.button.classList.add(this.api.styles.inlineToolButton);
    this.button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 5h6a3.5 3.5 0 0 1 0 7h-6z"/><path d="M13 12h1a3.5 3.5 0 0 1 0 7h-7v-7"/></svg>`;
    return this.button;
  }

  surround(range: Range | null): void {
    if (!range) return;

    const el = this.findBold();

    if (el) {
      this.unwrap(el);
    } else {
      this.wrap(range);
    }
  }

  checkState(): boolean {
    this.active = !!this.findBold();
    this.button.classList.toggle(this.api.styles.inlineToolButtonActive, this.active);
    return this.active;
  }

  private findBold(): HTMLElement | null {
    return this.api.selection.findParentTag("B") ?? this.api.selection.findParentTag("STRONG");
  }

  private wrap(range: Range): void {
    const bold = document.createElement("b");
    bold.appendChild(range.extractContents());
    range.insertNode(bold);
    this.api.selection.expandToTag(bold);
  }

  private unwrap(el: HTMLElement): void {
    this.api.selection.expandToTag(el);
    const sel = window.getSelection();
    if (!sel) return;
    const range = sel.getRangeAt(0);
    const content = range.extractContents();
    el.parentNode?.insertBefore(content, el);
    el.remove();
  }
}
