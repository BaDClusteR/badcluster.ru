// noinspection JSUnusedGlobalSymbols

import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
import type { HeadingBlockData } from '../heading/HeadingBlock';
import classes from './TocBlock.module.css';

const ICON_TOC = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 12H3"/><path d="M16 6H3"/><path d="M10 18H3"/><path d="M21 6l-3 6 3 6"/></svg>`;
const ICON_REFRESH = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>`;

export interface TocItem {
  text: string;
  anchor: string;
  children?: TocItem[];
}

export interface TocBlockData {
  items: TocItem[];
  label: string;
}

export class TocBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: 'Оглавление',
      icon: ICON_TOC
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

    if (this.data.items.length === 0) {
      queueMicrotask(() => this.collectFromHeadings());
    }

    return this.wrapper;
  }

  save(): TocBlockData {
    this.data.items = this.syncFromDom(this.listEl, this.data.items);
    return {
      items: this.data.items,
      label: this.data.label,
    };
  }

  // --- Private ---

  /** Collect headings and build hierarchical tree. */
  private collectFromHeadings() {
    this.collectHeadingDataAsync().then((flatItems) => {
      this.data.items = this.buildTree(flatItems);
      this.buildList();
    });
  }

  private async collectHeadingDataAsync(): Promise<{ text: string; anchor: string; level: number }[]> {
    const count = this.api.blocks.getBlocksCount();
    const items: { text: string; anchor: string; level: number }[] = [];

    for (let i = 0; i < count; i++) {
      const block = this.api.blocks.getBlockByIndex(i);
      if (!block || block.name !== 'header') continue;

      try {
        const savedData = await block.save() as { data: HeadingBlockData } | undefined;
        if (!savedData?.data) continue;

        const hData = savedData.data;
        const text = hData.tocText?.trim() || hData.text?.replace(/<[^>]*>/g, '').trim() || '';
        if (!text) continue;

        items.push({
          text,
          anchor: hData.anchor ?? '',
          level: hData.level ?? 2,
        });
      } catch {
        // skip
      }
    }

    return items;
  }

  /** Convert flat list with levels into nested TocItem tree. */
  private buildTree(flat: { text: string; anchor: string; level: number }[]): TocItem[] {
    const root: TocItem[] = [];
    const stack: { items: TocItem[]; level: number }[] = [{ items: root, level: 1 }];

    for (const entry of flat) {
      const item: TocItem = { text: entry.text, anchor: entry.anchor };

      // Pop stack until we find a parent at a lower level
      while (stack.length > 1 && stack[stack.length - 1].level >= entry.level) {
        stack.pop();
      }

      const parent = stack[stack.length - 1].items;
      parent.push(item);

      // Push this item's children array as potential parent for deeper levels
      item.children = [];
      stack.push({ items: item.children, level: entry.level });
    }

    // Clean up empty children arrays
    this.pruneEmptyChildren(root);
    return root;
  }

  private pruneEmptyChildren(items: TocItem[]) {
    for (const item of items) {
      if (item.children && item.children.length === 0) {
        delete item.children;
      } else if (item.children) {
        this.pruneEmptyChildren(item.children);
      }
    }
  }

  /** Render the nested list from this.data.items. */
  private buildList() {
    this.listEl.innerHTML = '';

    if (this.data.items.length === 0) {
      const empty = document.createElement('li');
      empty.className = classes.emptyMessage;
      empty.textContent = 'Нажмите ↻ чтобы собрать оглавление из заголовков';
      this.listEl.appendChild(empty);
      return;
    }

    this.renderItems(this.listEl, this.data.items);
  }

  private renderItems(ol: HTMLElement, items: TocItem[]) {
    for (const item of items) {
      const li = document.createElement('li');
      li.className = ol.classList.contains(classes.sublist)
        ? classes.subItem
        : classes.item;

      if (item.anchor) {
        const link = document.createElement('span');
        link.className = classes.link;
        link.contentEditable = 'true';
        link.textContent = item.text;
        link.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') e.preventDefault();
        });
        li.appendChild(link);

        const anchorBadge = document.createElement('span');
        anchorBadge.className = classes.anchorBadge;
        anchorBadge.textContent = `#${item.anchor}`;
        li.appendChild(anchorBadge);
      } else {
        const span = document.createElement('span');
        span.className = classes.linkNoAnchor;
        span.contentEditable = 'true';
        span.textContent = item.text;
        span.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') e.preventDefault();
        });
        li.appendChild(span);
      }

      if (item.children && item.children.length > 0) {
        const subOl = document.createElement('ol');
        subOl.className = classes.sublist;
        this.renderItems(subOl, item.children);
        li.appendChild(subOl);
      }

      ol.appendChild(li);
    }
  }

  /** Read edited texts back from DOM into the hierarchical data. */
  private syncFromDom(ol: HTMLElement, dataItems: TocItem[]): TocItem[] {
    const liEls = ol.querySelectorAll(':scope > li');
    const result: TocItem[] = [];

    liEls.forEach((li, i) => {
      const source = dataItems[i];
      if (!source) return;

      const editable = li.querySelector(`:scope > .${classes.link}, :scope > .${classes.linkNoAnchor}`);
      const text = editable?.textContent?.trim() ?? source.text;

      const item: TocItem = { text, anchor: source.anchor };

      if (source.children && source.children.length > 0) {
        const subOl = li.querySelector(`:scope > .${classes.sublist}`);
        if (subOl) {
          item.children = this.syncFromDom(subOl as HTMLElement, source.children);
        } else {
          item.children = source.children;
        }
      }

      result.push(item);
    });

    return result;
  }
}
