// noinspection JSUnusedGlobalSymbols

import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
import type { HeadingBlockData } from '../heading/HeadingBlock';
import classes from './TocBlock.module.css';

const ICON_TOC = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 12H3"/><path d="M16 6H3"/><path d="M10 18H3"/><path d="M21 6l-3 6 3 6"/></svg>`;
const ICON_REFRESH = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>`;

export interface TocItem {
  text: string;
  anchor: string;
  level: number;
}

export interface TocBlockData {
  items: TocItem[];
  label: string;
}

export class TocBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: 'Оглавление',
      icon: ICON_TOC,
    };
  }

  static get isReadOnlySupported(): boolean {
    return false;
  }

  private api: API;
  private data: TocBlockData;
  private wrapper!: HTMLElement;
  private listEl!: HTMLElement;

  constructor({ data, api }: { data: BlockToolData<TocBlockData>; api: API }) {
    this.api = api;
    this.data = {
      items: Array.isArray(data?.items) ? data.items : [],
      label: data?.label ?? 'Содержание',
    };
  }

  render(): HTMLElement {
    this.wrapper = document.createElement('details');
    this.wrapper.className = classes.toc;
    this.wrapper.setAttribute('open', '');

    // Summary
    const summary = document.createElement('summary');
    summary.className = classes.summary;

    const labelInput = document.createElement('input');
    labelInput.type = 'text';
    labelInput.className = classes.labelInput;
    labelInput.value = this.data.label;
    labelInput.placeholder = 'Содержание';
    labelInput.addEventListener('input', () => {
      this.data.label = labelInput.value;
    });
    // Prevent details toggle when clicking the input
    labelInput.addEventListener('click', (e) => e.preventDefault());

    const refreshBtn = document.createElement('button');
    refreshBtn.type = 'button';
    refreshBtn.className = classes.refreshBtn;
    refreshBtn.innerHTML = ICON_REFRESH;
    refreshBtn.title = 'Собрать из заголовков';
    refreshBtn.addEventListener('click', (e) => {
      e.preventDefault();
      this.collectFromHeadings();
    });

    summary.appendChild(labelInput);
    summary.appendChild(refreshBtn);
    this.wrapper.appendChild(summary);

    // Nav / list
    const nav = document.createElement('nav');
    nav.className = classes.nav;

    this.listEl = document.createElement('ol');
    this.listEl.className = classes.list;

    this.buildList();

    nav.appendChild(this.listEl);
    this.wrapper.appendChild(nav);

    // If empty, auto-collect on first render
    if (this.data.items.length === 0) {
      queueMicrotask(() => this.collectFromHeadings());
    }

    return this.wrapper;
  }

  save(): TocBlockData {
    // Collect edited texts from DOM
    this.syncFromDom();
    return {
      items: this.data.items,
      label: this.data.label,
    };
  }

  // --- Private ---

  /** Collect headings from the editor's blocks. */
  private collectFromHeadings() {
    const count = this.api.blocks.getBlocksCount();
    const items: TocItem[] = [];
    const minLevel = 2; // H2 is top-level in ToC

    for (let i = 0; i < count; i++) {
      const block = this.api.blocks.getBlockByIndex(i);
      if (!block || block.name !== 'header') continue;

      // Access saved data from the block
      // Editor.js doesn't expose block data directly — we need to call save
      const holder = block.holder;
      const headingEl = holder?.querySelector('h2, h3, h4') as HTMLElement | null;
      if (!headingEl) continue;

      const text = headingEl.textContent?.trim() ?? '';
      if (!text) continue;

      // Try to get anchor and tocText from the block's internal state
      // We access the block's saved data through the holder's data attributes
      // or by reading the settings fields. For now, parse from the heading element.
      const tag = headingEl.tagName; // H2, H3, H4
      const level = parseInt(tag.replace('H', ''), 10);

      // HeadingBlock stores anchor and tocText — we need to get them.
      // The cleanest way is to call block.save() but it's async.
      // Instead, let's do it async:
      items.push({ text, anchor: '', level: level - minLevel });
    }

    // Now do an async pass to get anchors and tocText
    this.collectHeadingDataAsync().then((enrichedItems) => {
      this.data.items = enrichedItems.length > 0 ? enrichedItems : items;
      this.buildList();
    });
  }

  private async collectHeadingDataAsync(): Promise<TocItem[]> {
    const count = this.api.blocks.getBlocksCount();
    const items: TocItem[] = [];
    const minLevel = 2;

    for (let i = 0; i < count; i++) {
      const block = this.api.blocks.getBlockByIndex(i);
      if (!block || block.name !== 'header') continue;

      try {
        const savedData = await block.save() as { data: HeadingBlockData } | undefined;
        if (!savedData?.data) continue;

        const hData = savedData.data;
        const text = hData.tocText?.trim() || hData.text?.replace(/<[^>]*>/g, '').trim() || '';
        if (!text) continue;

        const level = (hData.level ?? 2) - minLevel;
        items.push({
          text,
          anchor: hData.anchor ?? '',
          level: Math.max(0, level),
        });
      } catch {
        // Block may not support save — skip
      }
    }

    return items;
  }

  private buildList() {
    this.listEl.innerHTML = '';

    if (this.data.items.length === 0) {
      const empty = document.createElement('li');
      empty.className = classes.emptyMessage;
      empty.textContent = 'Нажмите ↻ чтобы собрать оглавление из заголовков';
      this.listEl.appendChild(empty);
      return;
    }

    // Build nested structure: group items by level
    // Level 0 = top, level 1 = sub, level 2 = sub-sub
    let currentOl: HTMLElement = this.listEl;
    let currentLevel = 0;
    const olStack: HTMLElement[] = [this.listEl];

    for (let i = 0; i < this.data.items.length; i++) {
      const item = this.data.items[i];
      const targetLevel = item.level;

      // Go deeper
      while (currentLevel < targetLevel) {
        const subOl = document.createElement('ol');
        subOl.className = classes.sublist;
        // Attach to last li, or to current ol if no li yet
        const lastLi = currentOl.querySelector(':scope > li:last-child');
        if (lastLi) {
          lastLi.appendChild(subOl);
        } else {
          currentOl.appendChild(subOl);
        }
        olStack.push(subOl);
        currentOl = subOl;
        currentLevel++;
      }

      // Go shallower
      while (currentLevel > targetLevel && olStack.length > 1) {
        olStack.pop();
        currentOl = olStack[olStack.length - 1];
        currentLevel--;
      }

      const li = document.createElement('li');
      li.className = classes.item;
      li.dataset.index = String(i);

      if (item.anchor) {
        const link = document.createElement('span');
        link.className = classes.link;
        link.contentEditable = 'true';
        link.textContent = item.text;
        link.dataset.anchor = item.anchor;
        // Prevent Enter
        link.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') e.preventDefault();
        });
        li.appendChild(link);

        const anchorBadge = document.createElement('span');
        anchorBadge.className = classes.anchorBadge;
        anchorBadge.textContent = `#${item.anchor}`;
        li.appendChild(anchorBadge);
      } else {
        // No anchor — plain text, still editable
        const span = document.createElement('span');
        span.className = classes.linkNoAnchor;
        span.contentEditable = 'true';
        span.textContent = item.text;
        span.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') e.preventDefault();
        });
        li.appendChild(span);
      }

      currentOl.appendChild(li);
    }
  }

  /** Read edited texts back from the DOM into data. */
  private syncFromDom() {
    const items = this.listEl.querySelectorAll(`.${classes.item}`);
    items.forEach((li) => {
      const idx = parseInt((li as HTMLElement).dataset.index ?? '', 10);
      if (isNaN(idx) || !this.data.items[idx]) return;

      const editable = li.querySelector(`.${classes.link}, .${classes.linkNoAnchor}`);
      if (editable) {
        this.data.items[idx].text = editable.textContent?.trim() ?? '';
      }
    });
  }
}