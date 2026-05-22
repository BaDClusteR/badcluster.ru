// noinspection JSUnusedGlobalSymbols

import type { API, InlineTool } from '@editorjs/editorjs';

/**
 * Inline tool that wraps selected text in <span class="spoiler">...</span>.
 * The spoiler blurs the text; clicking reveals it.
 */
export class SpoilerInlineTool implements InlineTool {
  static get isInline(): boolean {
    return true;
  }

  static get shortcut(): string {
    return 'CMD+SHIFT+S';
  }

  static get sanitize() {
    return {
      span: (el: HTMLElement) => {
        return el.classList.contains('spoiler');
      },
    };
  }

  static get title(): string {
    return 'Spoiler';
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
    this.button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>`;
    return this.button;
  }

  surround(range: Range | null): void {
    if (!range) return;

    const spoilerEl = this.api.selection.findParentTag('SPAN', 'spoiler');

    if (spoilerEl) {
      this.unwrap(spoilerEl);
    } else {
      this.wrap(range);
    }
  }

  checkState(): boolean {
    const spoilerEl = this.api.selection.findParentTag('SPAN', 'spoiler');
    this.active = !!spoilerEl;
    this.button.classList.toggle(this.api.styles.inlineToolButtonActive, this.active);
    return this.active;
  }

  private wrap(range: Range): void {
    const span = document.createElement('span');
    span.classList.add('spoiler');
    span.appendChild(range.extractContents());
    range.insertNode(span);
    this.api.selection.expandToTag(span);
  }

  private unwrap(spoilerEl: HTMLElement): void {
    this.api.selection.expandToTag(spoilerEl);
    const sel = window.getSelection();
    if (!sel) return;
    const range = sel.getRangeAt(0);
    const content = range.extractContents();
    spoilerEl.parentNode?.insertBefore(content, spoilerEl);
    spoilerEl.remove();
  }
}
