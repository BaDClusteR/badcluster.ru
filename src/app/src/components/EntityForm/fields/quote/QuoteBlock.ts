// noinspection JSUnusedGlobalSymbols

import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
import Toggle from '../mediaBlock/settings/toggle';
import TextField from '../mediaBlock/settings/textfield';
import separator from '../mediaBlock/settings/separator';
import classes from './QuoteBlock.module.css';

const ICON_QUOTE = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M10 8v5H6.83C7.44 11.15 8.6 9.7 10 8M4 14V8a8.1 8.1 0 0 1 6-7.76V3a5.07 5.07 0 0 0-4.47 5H8a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2m16-6v5h-3.17c.61-1.85 1.77-3.3 3.17-5M14 14V8a8.1 8.1 0 0 1 6-7.76V3a5.07 5.07 0 0 0-4.47 5H18a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2"/></svg>`;
const ICON_TRANSLATE = `<svg xmlns="http://www.w3.org/2000/svg" width="17" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>`;

export interface QuoteBlockData {
  text: string;
  translated: boolean;
  translatedText: string;
  labelOriginal: string;
  labelTranslation: string;
}

export class QuoteBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: 'Цитата',
      icon: ICON_QUOTE,
    };
  }

  static get isReadOnlySupported(): boolean {
    return false;
  }

  static get sanitize() {
    return {
      text: {
        p: true,
        div: true,
        br: true,
        b: true,
        strong: true,
        i: true,
        em: true,
        a: { href: true, target: true, rel: true },
      },
      translatedText: {
        p: true,
        div: true,
        br: true,
        b: true,
        strong: true,
        i: true,
        em: true,
        a: { href: true, target: true, rel: true },
      },
    };
  }

  static get pasteConfig() {
    return {
      tags: ['BLOCKQUOTE'],
    };
  }

  private api: API;
  private data: QuoteBlockData;
  private wrapper!: HTMLElement;
  private originalEl!: HTMLDivElement;
  private translationEl!: HTMLDivElement;
  private activeTab: 'original' | 'translation' = 'original';

  constructor({ data, api }: { data: BlockToolData<QuoteBlockData>; api: API }) {
    this.api = api;
    this.data = {
      text: data?.text ?? '',
      translated: data?.translated ?? false,
      translatedText: data?.translatedText ?? '',
      labelOriginal: data?.labelOriginal ?? 'EN',
      labelTranslation: data?.labelTranslation ?? 'RU',
    };
  }

  render(): HTMLElement {
    this.wrapper = document.createElement('blockquote');
    this.wrapper.className = classes.quote;
    this.buildContent();
    return this.wrapper;
  }

  save(): QuoteBlockData {
    return {
      text: this.normalizeHtml(this.originalEl),
      translated: this.data.translated,
      translatedText: this.data.translated ? this.normalizeHtml(this.translationEl) : '',
      labelOriginal: this.data.labelOriginal,
      labelTranslation: this.data.labelTranslation,
    };
  }

  /** Replace all <div> with clean <p>, strip attributes. */
  private normalizeHtml(el: HTMLElement): string {
    const clone = el.cloneNode(true) as HTMLElement;
    for (const div of Array.from(clone.querySelectorAll('div'))) {
      const p = document.createElement('p');
      p.innerHTML = div.innerHTML;
      div.replaceWith(p);
    }
    return clone.innerHTML;
  }

  validate(data: QuoteBlockData): boolean {
    return data.text.trim() !== '';
  }

  renderSettings(): HTMLElement {
    const wrapper = document.createElement('div');
    wrapper.classList.add(classes.settingsWrapper);

    wrapper.appendChild(separator());

    wrapper.appendChild(
      Toggle({
        value: this.data.translated,
        onChange: (checked: boolean) => {
          this.data.translated = checked;
          this.buildContent();
        },
        icon: ICON_TRANSLATE,
        label: 'Перевод',
      }),
    );

    if (this.data.translated) {
      wrapper.appendChild(separator());

      const labelsLabel = document.createElement('div');
      labelsLabel.className = classes.settingsLabel;
      labelsLabel.textContent = 'Надписи на кнопках';
      wrapper.appendChild(labelsLabel);

      wrapper.appendChild(
        TextField({
          placeholder: 'Оригинал (напр. EN)',
          value: this.data.labelOriginal,
          onChange: (value: string) => {
            this.data.labelOriginal = value;
            this.updateSwitcherLabels();
          },
        }),
      );

      wrapper.appendChild(
        TextField({
          placeholder: 'Перевод (напр. RU)',
          value: this.data.labelTranslation,
          onChange: (value: string) => {
            this.data.labelTranslation = value;
            this.updateSwitcherLabels();
          },
        }),
      );
    }

    return wrapper;
  }

  // @ts-expect-error PasteEvent union type doesn't match tag paste detail shape
  onPaste(event: { detail: { data: HTMLElement } }) {
    this.data.text = event.detail.data.innerHTML;
    if (this.originalEl) {
      this.originalEl.innerHTML = this.data.text;
    }
  }

  // --- Private ---

  private buildContent() {
    // Save current content before rebuilding
    if (this.originalEl) {
      this.data.text = this.originalEl.innerHTML;
    }
    if (this.translationEl) {
      this.data.translatedText = this.translationEl.innerHTML;
    }

    this.wrapper.innerHTML = '';

    // Original text
    this.originalEl = this.buildEditable(this.data.text, 'Текст цитаты');

    // Translation
    this.translationEl = this.buildEditable(this.data.translatedText, 'Перевод цитаты');

    if (this.data.translated) {
      this.wrapper.classList.add(classes.quoteSwitchable);

      // Switcher
      const controls = document.createElement('div');
      controls.className = classes.controls;

      const btnOriginal = document.createElement('button');
      btnOriginal.type = 'button';
      btnOriginal.className = `${classes.controlBtn} ${classes.controlBtnActive}`;
      btnOriginal.textContent = this.data.labelOriginal;
      btnOriginal.dataset.tab = 'original';

      const divider = document.createElement('span');
      divider.className = classes.divider;
      divider.textContent = '/';

      const btnTranslation = document.createElement('button');
      btnTranslation.type = 'button';
      btnTranslation.className = classes.controlBtn;
      btnTranslation.textContent = this.data.labelTranslation;
      btnTranslation.dataset.tab = 'translation';

      btnOriginal.addEventListener('click', () => this.switchTab('original'));
      btnTranslation.addEventListener('click', () => this.switchTab('translation'));

      controls.appendChild(btnOriginal);
      controls.appendChild(divider);
      controls.appendChild(btnTranslation);
      this.wrapper.appendChild(controls);

      // Both content divs
      this.originalEl.classList.add(classes.tabContent, classes.tabContentActive);
      this.translationEl.classList.add(classes.tabContent);

      this.wrapper.appendChild(this.originalEl);
      this.wrapper.appendChild(this.translationEl);

      this.activeTab = 'original';
    } else {
      this.wrapper.classList.remove(classes.quoteSwitchable);
      this.wrapper.appendChild(this.originalEl);
    }
  }

  private buildEditable(html: string, placeholder: string): HTMLDivElement {
    const el = document.createElement('div');
    el.className = classes.content;
    el.contentEditable = 'true';
    el.innerHTML = html;
    el.dataset.placeholder = placeholder;

    // Intercept Enter before Editor.js can create a new block
    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.stopPropagation();

        // After the browser inserts a new div, flatten nested divs
        requestAnimationFrame(() => this.flattenNestedDivs(el));
      }
    });

    return el;
  }

  /** Unwrap any nested divs so the structure stays flat (direct children of the contenteditable). */
  private flattenNestedDivs(root: HTMLElement) {
    // Save cursor position
    const sel = window.getSelection();
    const savedRange = sel?.rangeCount ? sel.getRangeAt(0).cloneRange() : null;

    for (const child of Array.from(root.children)) {
      if (child.tagName === 'DIV') {
        const nested = child.querySelector('div');
        if (nested) {
          // Lift nested divs up to root level
          const fragment = document.createDocumentFragment();
          while (child.firstChild) {
            fragment.appendChild(child.firstChild);
          }
          root.replaceChild(fragment, child);
        }
      }
    }

    // Restore cursor
    if (savedRange && sel) {
      try {
        sel.removeAllRanges();
        sel.addRange(savedRange);
      } catch {
        // Range may be invalid after DOM mutation
      }
    }
  }

  private switchTab(tab: 'original' | 'translation') {
    if (tab === this.activeTab) return;
    this.activeTab = tab;

    const buttons = this.wrapper.querySelectorAll(`.${classes.controlBtn}`);
    buttons.forEach((btn) => {
      btn.classList.toggle(classes.controlBtnActive, (btn as HTMLElement).dataset.tab === tab);
    });

    this.originalEl.classList.toggle(classes.tabContentActive, tab === 'original');
    this.translationEl.classList.toggle(classes.tabContentActive, tab === 'translation');
  }

  private updateSwitcherLabels() {
    const buttons = this.wrapper.querySelectorAll(`.${classes.controlBtn}`);
    buttons.forEach((btn) => {
      const el = btn as HTMLElement;
      if (el.dataset.tab === 'original') {
        el.textContent = this.data.labelOriginal;
      } else {
        el.textContent = this.data.labelTranslation;
      }
    });
  }
}