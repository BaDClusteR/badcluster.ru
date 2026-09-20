// noinspection JSUnusedGlobalSymbols

import type {BlockTool, ToolboxConfig} from "@editorjs/editorjs";
import classes from "./DelimiterBlock.module.css";

const ICON_DELIMITER = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>`;

/**
 * Разделитель — блок без данных и без настроек: он либо есть, либо нет.
 * Рендерится как «***» и на фронте (`<hr class="block__delimiter">`).
 */
export class DelimiterBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: "Разделитель",
      icon: ICON_DELIMITER
    };
  }

  static get isReadOnlySupported(): boolean {
    return true;
  }

  static get sanitize() {
    return {};
  }

  render(): HTMLElement {
    const wrapper = document.createElement("div");
    wrapper.className = classes.delimiter;
    wrapper.textContent = "***";

    return wrapper;
  }

  save(): Record<string, never> {
    return {};
  }
}
