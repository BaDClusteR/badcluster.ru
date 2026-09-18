// noinspection JSUnusedGlobalSymbols

import type {API, BlockTool, BlockToolData, ToolboxConfig} from "@editorjs/editorjs";
import Toggle from "../mediaBlock/settings/Toggle/Toggle";
import classes from "./HtmlBlock.module.css";

const ICON_HTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 8-4 4 4 4"/><path d="m17 8 4 4-4 4"/><path d="m14 4-4 16"/></svg>`;
const ICON_PREVIEW = `<svg xmlns="http://www.w3.org/2000/svg" width="17" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;

export interface HtmlBlockData {
  html: string;
}

/**
 * Raw HTML block — whatever is typed here ends up on the page verbatim.
 *
 * Saves: { html }
 *
 * The `html: true` sanitize rule is load-bearing: Editor.js leaves a string
 * field untouched only for `true`. An object rule would filter it against a tag
 * whitelist and `false` would strip every tag, both of which defeat the point.
 */
export class HtmlBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: "HTML",
      icon: ICON_HTML
    };
  }

  static get isReadOnlySupported(): boolean {
    return false;
  }

  static get sanitize() {
    return {
      html: true
    };
  }

  private readonly data: HtmlBlockData;
  private wrapper!: HTMLElement;
  private textarea!: HTMLTextAreaElement;
  private preview!: HTMLElement;
  private showPreview = false;

  constructor({data}: { data: BlockToolData<HtmlBlockData>; api: API }) {
    this.data = {
      html: data?.html ?? ""
    };
  }

  render(): HTMLElement {
    this.wrapper = document.createElement("div");
    this.wrapper.className = classes.html;

    const label = document.createElement("div");
    label.className = classes.label;
    label.textContent = "HTML";
    this.wrapper.appendChild(label);

    this.textarea = document.createElement("textarea");
    this.textarea.className = classes.code;
    this.textarea.placeholder = "<div>…</div>";
    this.textarea.spellcheck = false;
    this.textarea.value = this.data.html;
    // Editor.js splits/merges blocks on these keys; inside a code field they
    // must stay ordinary text editing.
    this.textarea.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === "Backspace") {
        e.stopPropagation();
      }
    });
    this.textarea.addEventListener("input", () => {
      this.data.html = this.textarea.value;
      this.autoGrow();
      this.refreshPreview();
    });
    this.wrapper.appendChild(this.textarea);

    this.preview = document.createElement("div");
    this.preview.className = classes.preview;
    this.preview.hidden = true;
    // The preview is derived from the textarea, so its mutations are not
    // content changes — without this Editor.js would fire onChange (and mark
    // the form dirty) every time the preview is toggled or retyped.
    this.preview.dataset.mutationFree = "true";
    this.wrapper.appendChild(this.preview);

    // scrollHeight is only meaningful once the node is laid out.
    requestAnimationFrame(() => this.autoGrow());

    return this.wrapper;
  }

  save(): HtmlBlockData {
    return {
      html: this.textarea.value
    };
  }

  validate(data: HtmlBlockData): boolean {
    return data.html.trim() !== "";
  }

  renderSettings(): HTMLElement {
    return Toggle({
      value: this.showPreview,
      icon: ICON_PREVIEW,
      label: "Предпросмотр",
      onChange: (checked) => {
        this.showPreview = checked;
        this.applyMode();
      }
    });
  }

  /**
   * The preview is shown below the code, never instead of it. Hiding the
   * textarea would leave the block with a `display: none` first input, and
   * Editor.js positions the block toolbar off that input's bounding rect — an
   * all-zero rect pushes the hover menu off-screen, so the block becomes
   * impossible to tune (including switching the preview back off).
   */
  private applyMode() {
    this.refreshPreview();
    this.preview.hidden = !this.showPreview;
  }

  private refreshPreview() {
    if (!this.showPreview) {
      return;
    }

    this.preview.innerHTML = this.textarea.value;
  }

  private autoGrow() {
    this.textarea.style.height = "auto";
    this.textarea.style.height = `${this.textarea.scrollHeight}px`;
  }
}
