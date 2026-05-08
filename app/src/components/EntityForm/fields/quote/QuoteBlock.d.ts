import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
export interface QuoteBlockData {
    text: string;
    translated: boolean;
    translatedText: string;
    labelOriginal: string;
    labelTranslation: string;
}
export declare class QuoteBlock implements BlockTool {
    static get toolbox(): ToolboxConfig;
    static get isReadOnlySupported(): boolean;
    static get sanitize(): {
        text: {
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
        translatedText: {
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
    };
    static get pasteConfig(): {
        tags: string[];
    };
    private api;
    private data;
    private wrapper;
    private originalEl;
    private translationEl;
    private activeTab;
    constructor({ data, api }: {
        data: BlockToolData<QuoteBlockData>;
        api: API;
    });
    render(): HTMLElement;
    save(): QuoteBlockData;
    /** Replace all <div> with clean <p>, strip attributes. */
    private normalizeHtml;
    validate(data: QuoteBlockData): boolean;
    renderSettings(): HTMLElement;
    onPaste(event: {
        detail: {
            data: HTMLElement;
        };
    }): void;
    private buildContent;
    private buildEditable;
    /** Unwrap any nested divs so the structure stays flat (direct children of the contenteditable). */
    private flattenNestedDivs;
    private switchTab;
    private updateSwitcherLabels;
}
