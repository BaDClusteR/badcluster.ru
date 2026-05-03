// noinspection JSUnusedGlobalSymbols

import type {API, BlockTool, BlockToolData, ToolboxConfig} from "@editorjs/editorjs";
import Toggle from "../mediaBlock/settings/toggle";
import TextField from "../mediaBlock/settings/textfield";
import separator from "../mediaBlock/settings/separator";
import classes from "./TerminalBlock.module.css";

const ICON_TERMINAL = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 7 5 5-5 5"/><path d="M12 19h7"/></svg>`;
const ICON_CIPHER = `<svg xmlns="http://www.w3.org/2000/svg" width="17" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>`;
const ICON_TRANSLATE = `<svg xmlns="http://www.w3.org/2000/svg" width="17" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>`;
const ICON_TITLE = `<svg xmlns="http://www.w3.org/2000/svg" width="17" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16"/><path d="M4 18h7"/><path d="M4 6h16"/></svg>`;

type TabKey = "cipher" | "en" | "ru";

export interface TerminalBlockData {
    /** Cipher (raw encoded) text */
    cipher: string;
    /** English decoded text */
    en: string;
    /** Russian decoded text */
    ru: string;
    /** Which tabs are enabled */
    tabCipher: boolean;
    tabEn: boolean;
    tabRu: boolean;
    /** Tab labels */
    labelCipher: string;
    labelEn: string;
    labelRu: string;
    /** Show title in header */
    showTitle: boolean;
    /** Title text (shown in header left side) */
    title: string;
    /** Anchor ID for navigation */
    anchor: string;
    /** Cipher key text */
    key: string;
    /** Page reference */
    page: string;
}

const TAB_DEFS: {
    key: TabKey;
    dataField: "cipher" | "en" | "ru";
    enabledField: keyof TerminalBlockData;
    labelField: keyof TerminalBlockData
}[] = [
    {key: "cipher", dataField: "cipher", enabledField: "tabCipher", labelField: "labelCipher"},
    {key: "en", dataField: "en", enabledField: "tabEn", labelField: "labelEn"},
    {key: "ru", dataField: "ru", enabledField: "tabRu", labelField: "labelRu"}
];

export class TerminalBlock implements BlockTool {
    static get toolbox(): ToolboxConfig {
        return {
            title: "Терминал",
            icon: ICON_TERMINAL
        };
    }

    static get isReadOnlySupported(): boolean {
        return false;
    }

    static get sanitize() {
        return {
            cipher: {br: true},
            en: {
                p: true,
                div: true,
                br: true,
                b: true,
                strong: true,
                i: true,
                em: true,
                a: {href: true, target: true, rel: true}
            },
            ru: {
                p: true,
                div: true,
                br: true,
                b: true,
                strong: true,
                i: true,
                em: true,
                a: {href: true, target: true, rel: true}
            }
        };
    }

    private api: API;
    private data: TerminalBlockData;
    private wrapper!: HTMLElement;
    private contentEls: Partial<Record<TabKey, HTMLElement>> = {};
    private activeTab: TabKey = "cipher";

    constructor({data, api}: { data: BlockToolData<TerminalBlockData>; api: API }) {
        this.api = api;
        this.data = {
            cipher: data?.cipher ?? "",
            en: data?.en ?? "",
            ru: data?.ru ?? "",
            tabCipher: data?.tabCipher ?? true,
            tabEn: data?.tabEn ?? true,
            tabRu: data?.tabRu ?? true,
            labelCipher: data?.labelCipher ?? "Шифр",
            labelEn: data?.labelEn ?? "EN",
            labelRu: data?.labelRu ?? "RU",
            showTitle: data?.showTitle ?? true,
            title: data?.title ?? "",
            anchor: data?.anchor ?? "",
            key: data?.key ?? "",
            page: data?.page ?? ""
        };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement("div");
        this.wrapper.className = classes.terminal;
        this.wrapper.setAttribute("data-mantine-color-scheme", "dark");
        this.buildContent();
        return this.wrapper;
    }

    save(): TerminalBlockData {
        // Collect latest content from editors
        for (const def of TAB_DEFS) {
            const el = this.contentEls[def.key];
            if (el) {
                this.data[def.dataField] = def.key === "cipher" ? el.innerText : this.normalizeHtml(el);
            }
        }

        return {...this.data};
    }

    validate(data: TerminalBlockData): boolean {
        return data.cipher.trim() !== "" || data.en.trim() !== "" || data.ru.trim() !== "";
    }

    renderSettings(): HTMLElement {
        const wrapper = document.createElement("div");
        wrapper.classList.add(classes.settingsWrapper);

        wrapper.appendChild(separator());

        // Tab toggles
        const tabsLabel = document.createElement("div");
        tabsLabel.className = classes.settingsLabel;
        tabsLabel.textContent = "Вкладки";
        wrapper.appendChild(tabsLabel);

        wrapper.appendChild(
            Toggle({
                value: this.data.tabCipher,
                icon: ICON_CIPHER,
                label: this.data.labelCipher,
                onChange: (checked) => {
                    this.data.tabCipher = checked;
                    this.buildContent();
                }
            })
        );

        wrapper.appendChild(
            Toggle({
                value: this.data.tabEn,
                icon: ICON_TRANSLATE,
                label: this.data.labelEn,
                onChange: (checked) => {
                    this.data.tabEn = checked;
                    this.buildContent();
                }
            })
        );

        wrapper.appendChild(
            Toggle({
                value: this.data.tabRu,
                icon: ICON_TRANSLATE,
                label: this.data.labelRu,
                onChange: (checked) => {
                    this.data.tabRu = checked;
                    this.buildContent();
                }
            })
        );

        wrapper.appendChild(separator());

        // Title toggle
        wrapper.appendChild(
            Toggle({
                value: this.data.showTitle,
                icon: ICON_TITLE,
                label: "Заголовок",
                onChange: (checked) => {
                    this.data.showTitle = checked;
                    this.buildContent();
                }
            })
        );

        wrapper.appendChild(separator());

        // Labels
        const labelsLabel = document.createElement("div");
        labelsLabel.className = classes.settingsLabel;
        labelsLabel.textContent = "Надписи";
        wrapper.appendChild(labelsLabel);

        wrapper.appendChild(
            TextField({
                placeholder: "Шифр (напр. Шифр)",
                value: this.data.labelCipher,
                onChange: (v) => {
                    this.data.labelCipher = v;
                    this.updateTabLabels();
                }
            })
        );

        wrapper.appendChild(
            TextField({
                placeholder: "Язык 1 (напр. EN)",
                value: this.data.labelEn,
                onChange: (v) => {
                    this.data.labelEn = v;
                    this.updateTabLabels();
                }
            })
        );

        wrapper.appendChild(
            TextField({
                placeholder: "Язык 2 (напр. RU)",
                value: this.data.labelRu,
                onChange: (v) => {
                    this.data.labelRu = v;
                    this.updateTabLabels();
                }
            })
        );

        wrapper.appendChild(separator());

        // Anchor
        wrapper.appendChild(
            TextField({
                placeholder: "Анкор (ID)",
                value: this.data.anchor,
                onChange: (v) => {
                    this.data.anchor = v;
                }
            })
        );

        return wrapper;
    }

    // --- Private ---

    private get enabledTabs(): TabKey[] {
        const tabs: TabKey[] = [];
        if (this.data.tabCipher) tabs.push("cipher");
        if (this.data.tabEn) tabs.push("en");
        if (this.data.tabRu) tabs.push("ru");
        return tabs;
    }

    private buildContent() {
        // Save current content before rebuilding
        for (const def of TAB_DEFS) {
            const el = this.contentEls[def.key];
            if (el) {
                this.data[def.dataField] = def.key === "cipher" ? el.innerText : el.innerHTML;
            }
        }

        this.wrapper.innerHTML = "";
        this.contentEls = {};

        const enabled = this.enabledTabs;
        if (enabled.length === 0) return;

        // Pick active tab
        if (!enabled.includes(this.activeTab)) {
            this.activeTab = enabled[0];
        }

        const showHeader = enabled.length > 1 || this.data.showTitle;

        // Header
        if (showHeader) {
            const header = document.createElement("div");
            header.className = classes.header;

            // Title (left side)
            if (this.data.showTitle) {
                const titlePrefix = document.createTextNode('📡 ');
                const titleInput = document.createElement("input");
                titleInput.type = "text";
                titleInput.className = classes.titleInput;
                titleInput.placeholder = "НАЗВАНИЕ";
                titleInput.value = this.data.title;
                titleInput.addEventListener("input", () => {
                    this.data.title = titleInput.value;
                });
                header.appendChild(titlePrefix);
                header.appendChild(titleInput);
            }

            // Tabs (right side) — only when multiple tabs
            if (enabled.length > 1) {
                const tabs = document.createElement("div");
                tabs.className = classes.tabs;

                enabled.forEach((tabKey, i) => {
                    const btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = classes.tabBtn;
                    btn.dataset.tab = tabKey;
                    btn.textContent = this.getTabLabel(tabKey);
                    if (tabKey === this.activeTab) {
                        btn.classList.add(classes.tabBtnActive);
                    }
                    btn.addEventListener("click", () => this.switchTab(tabKey));
                    tabs.appendChild(btn);
                });

                header.appendChild(tabs);
            }

            this.wrapper.appendChild(header);
        }

        // Body
        const body = document.createElement("div");
        body.className = classes.body;

        for (const tabKey of enabled) {
            const content = this.buildTabContent(tabKey);
            if (tabKey === this.activeTab) {
                content.classList.add(classes.tabContentActive);
            }
            body.appendChild(content);
            this.contentEls[tabKey] = content;
        }

        this.wrapper.appendChild(body);

        // Footer — always shown in admin
        const footer = document.createElement("div");
        footer.className = classes.footer;

        const keyRow = this.buildFooterField("KEY", this.data.key, (v) => {
            this.data.key = v;
        });
        const pageRow = this.buildFooterField("PAGE", this.data.page, (v) => {
            this.data.page = v;
        });
        footer.appendChild(pageRow);
        footer.appendChild(keyRow);

        this.wrapper.appendChild(footer);
    }

    private buildTabContent(tabKey: TabKey): HTMLElement {
        const el = document.createElement(tabKey === "cipher" ? "code" : "div");
        el.className = `${classes.content} ${tabKey === "cipher" ? classes.contentCipher : classes.contentText}`;
        el.classList.add(classes.tabContent);
        el.contentEditable = "true";
        el.dataset.tab = tabKey;

        const placeholders: Record<TabKey, string> = {
            cipher: "Зашифрованный текст...",
            en: "English text...",
            ru: "Русский текст..."
        };
        el.dataset.placeholder = placeholders[tabKey];

        if (tabKey === "cipher") {
            el.innerText = this.data.cipher;
        } else {
            el.innerHTML = this.data[tabKey];
        }

        // Prevent Editor.js from intercepting Enter
        el.addEventListener("keydown", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.stopPropagation();
                if (tabKey === "cipher") {
                    // For cipher: just insert a newline
                    e.preventDefault();
                    document.execCommand("insertLineBreak");
                } else {
                    // For text: let browser handle (creates <div>/<p>), then flatten
                    requestAnimationFrame(() => this.flattenNestedDivs(el));
                }
            }
        });

        return el;
    }

    private buildFooterField(label: string, value: string, onChange: (v: string) => void): HTMLElement {
        const row = document.createElement("div");
        row.className = classes.footerRow;

        const labelEl = document.createElement("span");
        labelEl.className = classes.footerLabel;
        labelEl.textContent = `${label}: `;

        const editable = document.createElement("span");
        editable.className = classes.footerInput;
        editable.contentEditable = "true";
        editable.textContent = value;
        editable.dataset.placeholder = "—";
        editable.addEventListener("input", () => {
            onChange(editable.textContent ?? "");
        });
        // Prevent Enter — keep it single-paragraph
        editable.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                e.preventDefault();
            }
        });

        row.appendChild(labelEl);
        row.appendChild(editable);

        return row;
    }

    private getTabLabel(tabKey: TabKey): string {
        const map: Record<TabKey, keyof TerminalBlockData> = {
            cipher: "labelCipher",
            en: "labelEn",
            ru: "labelRu"
        };
        return this.data[map[tabKey]] as string;
    }

    private switchTab(tabKey: TabKey) {
        if (tabKey === this.activeTab) return;
        this.activeTab = tabKey;

        // Update tab buttons
        const buttons = this.wrapper.querySelectorAll(`.${classes.tabBtn}`);
        buttons.forEach((btn) => {
            btn.classList.toggle(classes.tabBtnActive, (btn as HTMLElement).dataset.tab === tabKey);
        });

        // Update content visibility
        const contents = this.wrapper.querySelectorAll(`.${classes.tabContent}`);
        contents.forEach((el) => {
            el.classList.toggle(classes.tabContentActive, (el as HTMLElement).dataset.tab === tabKey);
        });
    }

    private updateTabLabels() {
        const buttons = this.wrapper.querySelectorAll(`.${classes.tabBtn}`);
        buttons.forEach((btn) => {
            const el = btn as HTMLElement;
            const tabKey = el.dataset.tab as TabKey;
            if (tabKey) {
                el.textContent = this.getTabLabel(tabKey);
            }
        });
    }

    private flattenNestedDivs(root: HTMLElement) {
        const sel = window.getSelection();
        const savedRange = sel?.rangeCount ? sel.getRangeAt(0).cloneRange() : null;

        for (const child of Array.from(root.children)) {
            if (child.tagName === "DIV") {
                const nested = child.querySelector("div");
                if (nested) {
                    const fragment = document.createDocumentFragment();
                    while (child.firstChild) {
                        fragment.appendChild(child.firstChild);
                    }
                    root.replaceChild(fragment, child);
                }
            }
        }

        if (savedRange && sel) {
            try {
                sel.removeAllRanges();
                sel.addRange(savedRange);
            } catch { /* range may be invalid */
            }
        }
    }

    private normalizeHtml(el: HTMLElement): string {
        const clone = el.cloneNode(true) as HTMLElement;
        for (const div of Array.from(clone.querySelectorAll("div"))) {
            const p = document.createElement("p");
            p.innerHTML = div.innerHTML;
            div.replaceWith(p);
        }
        return clone.innerHTML;
    }
}
