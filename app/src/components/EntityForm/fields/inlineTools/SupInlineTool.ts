// noinspection JSUnusedGlobalSymbols

import type { API, InlineTool } from "@editorjs/editorjs";

/**
 * Inline tool that wraps selected text in <sup>...</sup>.
 */
export class SupInlineTool implements InlineTool {
  static get isInline(): boolean {
    return true;
  }

  static get sanitize() {
    return {
      sup: true,
    };
  }

  static get title(): string {
    return "Sup";
  }

  private api: API;
  private button!: HTMLButtonElement;
  private active = false;

  constructor({ api }: { api: API }) {
    this.api = api;
  }

  render(): HTMLButtonElement {
    this.button = document.createElement("button");
    this.button.type = "button";
    this.button.classList.add(this.api.styles.inlineToolButton);
    this.button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><text x="2" y="18" font-size="14" font-family="sans-serif" fill="currentColor" stroke="none">x</text><text x="14" y="10" font-size="9" font-family="sans-serif" fill="currentColor" stroke="none">2</text></svg>`;
    return this.button;
  }

  surround(range: Range | null): void {
    if (!range) return;

    const el = this.api.selection.findParentTag("SUP");

    if (el) {
      this.unwrap(el);
    } else {
      this.wrap(range);
    }
  }

  checkState(): boolean {
    const el = this.api.selection.findParentTag("SUP");
    this.active = !!el;
    this.button.classList.toggle(this.api.styles.inlineToolButtonActive, this.active);
    return this.active;
  }

  private wrap(range: Range): void {
    const sup = document.createElement("sup");
    sup.appendChild(range.extractContents());
    range.insertNode(sup);
    this.api.selection.expandToTag(sup);
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