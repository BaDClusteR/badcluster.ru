// noinspection JSUnusedGlobalSymbols

import type { API, InlineTool } from '@editorjs/editorjs';
import classes from './LinkInlineTool.module.css';

/**
 * Custom link inline tool with "open in new window" support.
 * Replaces the built-in link tool.
 */
export class LinkInlineTool implements InlineTool {
  static get isInline(): boolean {
    return true;
  }

  static get shortcut(): string {
    return 'CMD+K';
  }

  static get sanitize() {
    return {
      a: {
        href: true,
        target: true,
        rel: true,
      },
    };
  }

  static get title(): string {
    return 'Ссылка';
  }

  private api: API;
  private button!: HTMLButtonElement;
  private actionsEl!: HTMLElement;
  private urlInput!: HTMLInputElement;
  private newWindowCheckbox!: HTMLInputElement;
  private active = false;
  private existingLink: HTMLAnchorElement | null = null;
  /** Prevents checkState from closing actions that surround() just opened. */
  private justOpened = false;

  constructor({ api }: { api: API }) {
    this.api = api;
  }

  render(): HTMLButtonElement {
    this.button = document.createElement('button');
    this.button.type = 'button';
    this.button.classList.add(this.api.styles.inlineToolButton);
    this.button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>`;
    return this.button;
  }

  renderActions(): HTMLElement {
    this.actionsEl = document.createElement('div');
    this.actionsEl.className = classes.actions;
    this.actionsEl.hidden = true;


    // URL input
    this.urlInput = document.createElement('input');
    this.urlInput.type = 'url';
    this.urlInput.className = classes.urlInput;
    this.urlInput.placeholder = 'https://...';
    this.urlInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        this.applyLink();
      }
    });

    // New window checkbox row
    const checkboxRow = document.createElement('label');
    checkboxRow.className = classes.checkboxRow;

    this.newWindowCheckbox = document.createElement('input');
    this.newWindowCheckbox.type = 'checkbox';
    this.newWindowCheckbox.className = classes.checkbox;
    this.newWindowCheckbox.addEventListener('change', () => {
      // If editing an existing link, apply target immediately
      if (this.existingLink) {
        if (this.newWindowCheckbox.checked) {
          this.existingLink.target = '_blank';
          this.existingLink.rel = 'noopener noreferrer';
        } else {
          this.existingLink.removeAttribute('target');
          this.existingLink.removeAttribute('rel');
        }
      }
    });

    const checkboxLabel = document.createTextNode('Открывать в новом окне');

    // Prevent mousedown from moving focus out of contenteditable (which closes the toolbar)
    checkboxRow.addEventListener('mousedown', (e) => e.preventDefault());

    checkboxRow.appendChild(this.newWindowCheckbox);
    checkboxRow.appendChild(checkboxLabel);

    this.actionsEl.appendChild(this.urlInput);
    this.actionsEl.appendChild(checkboxRow);

    return this.actionsEl;
  }

  surround(range: Range | null): void {
    if (!range) return;

    if (this.active) {
      // Already a link — unwrap
      this.unwrap();
      this.closeActions();
    } else {
      // Save selection before focus moves to the input
      this.api.selection.save();
      this.showActions();
      this.justOpened = true;
    }
  }

  checkState(): boolean {
    const anchorEl = this.api.selection.findParentTag('A') as HTMLAnchorElement | null;
    this.active = !!anchorEl;
    this.existingLink = anchorEl;
    this.button.classList.toggle(this.api.styles.inlineToolButtonActive, this.active);

    if (this.justOpened) {
      // surround() just opened the panel — don't close it
      this.justOpened = false;
      return this.active;
    }

    if (anchorEl) {
      this.showActions();
      this.urlInput.value = anchorEl.href;
      this.newWindowCheckbox.checked = anchorEl.target === '_blank';
    } else {
      this.closeActions();
    }

    return this.active;
  }

  clear(): void {
    this.closeActions();
  }

  private showActions() {
    this.actionsEl.hidden = false;
    requestAnimationFrame(() => this.urlInput.focus());
  }

  private closeActions() {
    this.actionsEl.hidden = true;
    this.urlInput.value = '';
    this.newWindowCheckbox.checked = false;
  }

  private applyLink() {
    const url = this.urlInput.value.trim();
    if (!url) return;

    const newWindow = this.newWindowCheckbox.checked;

    if (this.existingLink) {
      // Update existing link
      this.existingLink.href = url;
      if (newWindow) {
        this.existingLink.target = '_blank';
        this.existingLink.rel = 'noopener noreferrer';
      } else {
        this.existingLink.removeAttribute('target');
        this.existingLink.removeAttribute('rel');
      }
    } else {
      // Create new link around saved selection
      this.api.selection.restore();
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return;
      const range = sel.getRangeAt(0);

      const a = document.createElement('a');
      a.href = url;
      if (newWindow) {
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
      }
      a.appendChild(range.extractContents());
      range.insertNode(a);
      this.api.selection.expandToTag(a);
    }

    this.api.inlineToolbar.close();
  }

  private unwrap() {
    if (!this.existingLink) return;

    this.api.selection.expandToTag(this.existingLink);
    const sel = window.getSelection();
    if (!sel) return;
    const range = sel.getRangeAt(0);
    const content = range.extractContents();
    this.existingLink.parentNode?.insertBefore(content, this.existingLink);
    this.existingLink.remove();
    this.existingLink = null;
  }
}