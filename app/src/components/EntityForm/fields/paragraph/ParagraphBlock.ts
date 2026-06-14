// noinspection JSUnusedGlobalSymbols

import type {API, BlockTool, BlockToolData, ToolboxConfig} from "@editorjs/editorjs";
import classes from "./ParagraphBlock.module.css";
import {ParagraphClassRule} from "@admin/types";

const ICON_ALIGN_LEFT = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 10H3"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M17 18H3"/></svg>`;
const ICON_ALIGN_CENTER = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 10H7"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M17 18H7"/></svg>`;
const ICON_ALIGN_RIGHT = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10H7"/><path d="M21 6H3"/><path d="M21 14H3"/><path d="M21 18H7"/></svg>`;

type Alignment = "left" | "center" | "right";

const ALIGNMENTS: { key: Alignment; icon: string }[] = [
  {key: "left", icon: ICON_ALIGN_LEFT},
  {key: "center", icon: ICON_ALIGN_CENTER},
  {key: "right", icon: ICON_ALIGN_RIGHT}
];

export interface ParagraphBlockData {
  text: string;
  alignment?: Alignment;
}

export class ParagraphBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: "Параграф",
      icon: `<svg xmlns="http://www.w3.org/2000/svg" width="17" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 4v16"/><path d="M17 4v16"/><path d="M19 4H9.5a4.5 4.5 0 0 0 0 9H13"/></svg>`
    };
  }

  static get isReadOnlySupported(): boolean {
    return false;
  }

  static get conversionConfig() {
    return {
      export: "text",
      import: "text"
    };
  }

  static get sanitize() {
    return {
      text: {
        br: true,
        b: true,
        strong: true,
        i: true,
        em: true,
        a: {href: true, target: true, rel: true},
        kbd: true,
        code: true,
        sup: true,
        span: true
      }
    };
  }

  static get pasteConfig() {
    return {
      tags: ["P"]
    };
  }

  private data: ParagraphBlockData;
  private element!: HTMLParagraphElement;
  private config: { placeholder?: string; classRules?: ParagraphClassRule[] };

  constructor({data, config}: {
    data: BlockToolData<ParagraphBlockData>;
    api: API;
    config?: Record<string, unknown>
  }) {
    this.config = {
      placeholder: config?.placeholder as string | undefined,
      classRules: config?.classRules as ParagraphClassRule[] | undefined
    };
    this.data = {
      text: data?.text ?? "",
      alignment: data?.alignment ?? "left"
    };
  }

  render(): HTMLElement {
    this.element = document.createElement("p");
    this.element.classList.add(classes.paragraph);
    this.element.classList.add("ce-paragraph");
    this.element.contentEditable = "true";
    this.element.innerHTML = this.data.text;
    if (this.config.placeholder) {
      this.element.dataset.placeholder = this.config.placeholder;
    }
    this.applyAlignment();
    this.applyClassRules();
    this.element.addEventListener("input", () => this.applyClassRules());
    return this.element;
  }

  save(): ParagraphBlockData {
    return {
      text: this.element.innerHTML,
      alignment: this.data.alignment === "left" ? undefined : this.data.alignment
    };
  }

  validate(): boolean {
    // Allow empty paragraphs
    return true;
  }

  renderSettings() {
    const wrapper = document.createElement("div");
    wrapper.className = classes.alignRow;

    for (const align of ALIGNMENTS) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = classes.alignBtn;
      btn.innerHTML = align.icon;
      btn.classList.toggle(classes.alignBtnActive, this.data.alignment === align.key);
      btn.addEventListener("click", () => {
        this.data.alignment = align.key;
        this.applyAlignment();
        wrapper.querySelectorAll(`.${classes.alignBtn}`).forEach((el, i) => {
          el.classList.toggle(classes.alignBtnActive, ALIGNMENTS[i].key === align.key);
        });
      });
      wrapper.appendChild(btn);
    }

    return wrapper;
  }

  // @ts-expect-error PasteEvent type mismatch
  onPaste(event: { detail: { data: HTMLElement } }) {
    const el = event.detail.data;
    this.data.text = el.innerHTML;

    // Detect alignment from pasted element
    if (el.classList.contains("align-center")) {
      this.data.alignment = "center";
    } else if (el.classList.contains("align-right")) {
      this.data.alignment = "right";
    } else {
      this.data.alignment = "left";
    }

    if (this.element) {
      this.element.innerHTML = this.data.text;
      this.applyAlignment();
      this.applyClassRules();
    }
  }

  private applyClassRules() {
    if (!this.config.classRules?.length) return;
    const text = this.element.textContent ?? "";
    for (const rule of this.config.classRules) {
      this.element.classList.toggle(rule.className, rule.pattern.test(text));
    }
  }

  private applyAlignment() {
    this.element.classList.remove(classes.alignCenter, classes.alignRight);
    if (this.data.alignment === "center") {
      this.element.classList.add(classes.alignCenter);
    } else if (this.data.alignment === "right") {
      this.element.classList.add(classes.alignRight);
    }
  }
}
