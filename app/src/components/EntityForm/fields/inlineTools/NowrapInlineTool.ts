// noinspection JSUnusedGlobalSymbols

import type { API, InlineTool } from "@editorjs/editorjs";

/**
 * Inline tool that wraps selected text in <span class="nowrap">...</span>.
 */
export class NowrapInlineTool implements InlineTool {
  static get isInline(): boolean {
    return true;
  }

  static get sanitize() {
    return {
      span: (el: HTMLElement) => {
        return el.classList.contains("nowrap");
      },
    };
  }

  static get title(): string {
    return "Nowrap";
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
    this.button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M3 12h15"/><path d="M18 12l3 3-3 3"/><path d="M3 18h7"/></svg>`;
    return this.button;
  }

  surround(range: Range | null): void {
    if (!range) return;

    const el = this.api.selection.findParentTag("SPAN", "nowrap");

    if (el) {
      this.unwrap(el);
    } else {
      this.wrap(range);
    }
  }

  checkState(): boolean {
    const el = this.api.selection.findParentTag("SPAN", "nowrap");
    this.active = !!el;
    this.button.classList.toggle(this.api.styles.inlineToolButtonActive, this.active);
    return this.active;
  }

  private wrap(range: Range): void {
    const span = document.createElement("span");
    span.classList.add("nowrap");
    span.appendChild(range.extractContents());
    range.insertNode(span);
    this.api.selection.expandToTag(span);
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