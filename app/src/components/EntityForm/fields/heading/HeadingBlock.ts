// noinspection JSUnusedGlobalSymbols

import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
import TextField from '../mediaBlock/settings/textfield';
import separator from '../mediaBlock/settings/separator';
import classes from './HeadingBlock.module.css';
import {iconH2, iconH3, iconH4, iconHeader} from "./icons.ts";

interface HeadingLevel {
  number: number;
  tag: string;
  svg: string;
}

export interface HeadingBlockData {
  text: string;
  level: number;
  anchor: string;
  tocText: string;
}

const LEVELS: HeadingLevel[] = [
  {
    number: 2,
    tag: 'H2',
    svg: iconH2,
  },
  {
    number: 3,
    tag: 'H3',
    svg: iconH3,
  },
  {
    number: 4,
    tag: 'H4',
    svg: iconH4,
  },
];

/**
 * Custom heading block for Editor.js with anchor and ToC text support.
 *
 * Saves: { text, level, anchor, tocText }
 */
export class HeadingBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: 'Заголовок',
      icon: iconHeader,
    };
  }

  static get isReadOnlySupported(): boolean {
    return false;
  }

  /** Allow conversion from/to other blocks. */
  static get conversionConfig() {
    return {
      export: 'text',
      import: 'text',
    };
  }

  /** Sanitizer rules for pasted content. */
  static get sanitize() {
    return {
      level: false,
      text: {},
    };
  }

  static get pasteConfig() {
    return {
      tags: ['H2', 'H3', 'H4'],
    };
  }

  private api: API;
  private data: HeadingBlockData;
  private element!: HTMLHeadingElement;
  private config: { levels: number[]; defaultLevel: number; placeholder: string };

  constructor({ data, api, config }: { data: BlockToolData<HeadingBlockData>; api: API; config?: Record<string, unknown> }) {
    this.api = api;
    this.config = {
      levels: (config?.levels as number[]) ?? [2, 3, 4],
      defaultLevel: (config?.defaultLevel as number) ?? 2,
      placeholder: (config?.placeholder as string) ?? '',
    };
    this.data = {
      text: data?.text ?? '',
      level: this.config.levels.includes(data?.level) ? data.level : this.config.defaultLevel,
      anchor: data?.anchor ?? '',
      tocText: data?.tocText ?? '',
    };
  }

  render(): HTMLElement {
    this.element = this.buildTag();
    return this.element;
  }

  save(): HeadingBlockData {
    return {
      text: this.element.innerHTML,
      level: this.data.level,
      anchor: this.data.anchor,
      tocText: this.data.tocText,
    };
  }

  validate(data: HeadingBlockData): boolean {
    return data.text.trim() !== '';
  }

  renderSettings() {
    const wrapper = document.createElement('div');
    wrapper.className = classes.settingsWrapper;

    // Level buttons
    const levelsRow = document.createElement('div');
    levelsRow.className = classes.levelsRow;

    for (const level of this.availableLevels) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = classes.levelBtn;
      btn.innerHTML = level.svg;
      btn.classList.toggle(classes.levelBtnActive, level.number === this.data.level);
      btn.addEventListener('click', () => {
        this.setLevel(level.number);
        // Update active state
        levelsRow.querySelectorAll(`.${classes.levelBtn}`).forEach((el, i) => {
          el.classList.toggle(classes.levelBtnActive, this.availableLevels[i].number === level.number);
        });
      });
      levelsRow.appendChild(btn);
    }

    wrapper.appendChild(levelsRow);
    wrapper.appendChild(separator());

    wrapper.appendChild(
      TextField({
        placeholder: 'Анкор (ID)',
        value: this.data.anchor,
        onChange: (value: string) => {
          this.data.anchor = value;
        },
      }),
    );

    wrapper.appendChild(
      TextField({
        placeholder: 'Текст для оглавления',
        value: this.data.tocText,
        onChange: (value: string) => {
          this.data.tocText = value;
        },
      }),
    );

    return wrapper;
  }

  /** Handle paste of heading elements. */
  onPaste(event: { detail: { data: HTMLHeadingElement } }) {
    const tag = event.detail.data.tagName;
    const level = parseInt(tag.replace('H', ''), 10);

    this.data = {
      ...this.data,
      text: event.detail.data.innerHTML,
      level: this.config.levels.includes(level) ? level : this.config.defaultLevel,
    };

    // Re-render
    if (this.element) {
      const newEl = this.buildTag();
      this.element.replaceWith(newEl);
      this.element = newEl;
    }
  }

  private get availableLevels(): HeadingLevel[] {
    return LEVELS.filter((l) => this.config.levels.includes(l.number));
  }

  private setLevel(level: number) {
    this.data.level = level;
    this.data.text = this.element.innerHTML;

    const newEl = this.buildTag();
    this.element.replaceWith(newEl);
    this.element = newEl;
  }

  private buildTag(): HTMLHeadingElement {
    const tag = `h${this.data.level}` as keyof HTMLElementTagNameMap;
    const el = document.createElement(tag) as HTMLHeadingElement;
    el.innerHTML = this.data.text || '';
    el.classList.add(classes.heading);
    el.contentEditable = 'true';
    el.dataset.placeholder = this.api.i18n.t(this.config.placeholder);
    return el;
  }
}
