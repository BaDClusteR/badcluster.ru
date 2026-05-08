import type { API, BlockTool, BlockToolData, ToolboxConfig } from "@editorjs/editorjs";
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
export declare class TerminalBlock implements BlockTool {
    static get toolbox(): ToolboxConfig;
    static get isReadOnlySupported(): boolean;
    static get sanitize(): {
        cipher: {
            br: boolean;
        };
        en: {
            p: boolean;
            div: boolean;
            br: boolean;
            b: boolean;
            strong: boolean;
            i: boolean;
            em: boolean;
            a: {
                href: boolean;
                target: boolean;
                rel: boolean;
            };
            kbd: boolean;
        };
        ru: {
            p: boolean;
            div: boolean;
            br: boolean;
            b: boolean;
            strong: boolean;
            i: boolean;
            em: boolean;
            a: {
                href: boolean;
                target: boolean;
                rel: boolean;
            };
        };
    };
    private api;
    private data;
    private wrapper;
    private contentEls;
    private activeTab;
    constructor({ data, api }: {
        data: BlockToolData<TerminalBlockData>;
        api: API;
    });
    render(): HTMLElement;
    save(): TerminalBlockData;
    validate(data: TerminalBlockData): boolean;
    renderSettings(): HTMLElement;
    private get enabledTabs();
    private buildContent;
    private buildTabContent;
    private buildFooterField;
    private getTabLabel;
    private switchTab;
    private updateTabLabels;
    private flattenNestedDivs;
    private normalizeHtml;
}
