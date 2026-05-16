// noinspection JSUnusedGlobalSymbols

import type { API, InlineTool } from '@editorjs/editorjs';

/**
 * Inline tool that wraps selected text in <code>...</code>.
 */
export class CodeInlineTool implements InlineTool {
  static get isInline(): boolean {
    return true;
  }

  static get shortcut(): string {
    return 'CMD+E';
  }

  static get sanitize() {
    return {
      code: true,
    };
  }

  static get title(): string {
    return 'Code';
  }

  private api: API;
  private button!: HTMLButtonElement;
  private active = false;

  constructor({ api }: { api: API }) {
    this.api = api;
  }

  render(): HTMLButtonElement {
    this.button = document.createElement('button');
    this.button.type = 'button';
    this.button.classList.add(this.api.styles.inlineToolButton);
    this.button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>`;
    return this.button;
  }

  surround(range: Range | null): void {
    if (!range) return;

    const codeEl = this.api.selection.findParentTag('CODE');

    if (codeEl) {
      this.unwrap(codeEl);
    } else {
      this.wrap(range);
    }
  }

  checkState(): boolean {
    const codeEl = this.api.selection.findParentTag('CODE');
    this.active = !!codeEl;
    this.button.classList.toggle(this.api.styles.inlineToolButtonActive, this.active);
    return this.active;
  }

  private wrap(range: Range): void {
    const code = document.createElement('code');
    code.appendChild(range.extractContents());
    range.insertNode(code);
    this.api.selection.expandToTag(code);
  }

  private unwrap(codeEl: HTMLElement): void {
    this.api.selection.expandToTag(codeEl);
    const sel = window.getSelection();
    if (!sel) return;
    const range = sel.getRangeAt(0);
    const content = range.extractContents();
    codeEl.parentNode?.insertBefore(content, codeEl);
    codeEl.remove();
  }
}