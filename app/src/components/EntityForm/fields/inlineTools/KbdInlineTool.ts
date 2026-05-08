// noinspection JSUnusedGlobalSymbols

import type { API, InlineTool } from '@editorjs/editorjs';

/**
 * Inline tool that wraps selected text in <kbd>...</kbd>.
 */
export class KbdInlineTool implements InlineTool {
  static get isInline(): boolean {
    return true;
  }

  static get shortcut(): string {
    return 'CMD+SHIFT+K';
  }

  static get sanitize() {
    return {
      kbd: true,
    };
  }

  static get title(): string {
    return 'Kbd';
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
    this.button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.01"/><path d="M10 8h.01"/><path d="M14 8h.01"/><path d="M18 8h.01"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/><path d="M7 16h10"/></svg>`;
    return this.button;
  }

  surround(range: Range | null): void {
    if (!range) return;

    const kbdEl = this.api.selection.findParentTag('KBD');

    if (kbdEl) {
      // Unwrap
      this.unwrap(kbdEl);
    } else {
      // Wrap
      this.wrap(range);
    }
  }

  checkState(): boolean {
    const kbdEl = this.api.selection.findParentTag('KBD');
    this.active = !!kbdEl;
    this.button.classList.toggle(this.api.styles.inlineToolButtonActive, this.active);
    return this.active;
  }

  private wrap(range: Range): void {
    const kbd = document.createElement('kbd');
    kbd.appendChild(range.extractContents());
    range.insertNode(kbd);
    this.api.selection.expandToTag(kbd);
  }

  private unwrap(kbdEl: HTMLElement): void {
    this.api.selection.expandToTag(kbdEl);
    const sel = window.getSelection();
    if (!sel) return;
    const range = sel.getRangeAt(0);
    const content = range.extractContents();
    kbdEl.parentNode?.insertBefore(content, kbdEl);
    kbdEl.remove();
  }
}