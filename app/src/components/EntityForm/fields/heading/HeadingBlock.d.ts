import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
export interface HeadingBlockData {
    text: string;
    level: number;
    anchor: string;
    tocText: string;
}
/**
 * Custom heading block for Editor.js with anchor and ToC text support.
 *
 * Saves: { text, level, anchor, tocText }
 */
export declare class HeadingBlock implements BlockTool {
    static get toolbox(): ToolboxConfig;
    static get isReadOnlySupported(): boolean;
    /** Allow conversion from/to other blocks. */
    static get conversionConfig(): {
        export: string;
        import: string;
    };
    /** Sanitizer rules for pasted content. */
    static get sanitize(): {
        level: boolean;
        text: {};
    };
    static get pasteConfig(): {
        tags: string[];
    };
    private api;
    private data;
    private element;
    private config;
    constructor({ data, api, config }: {
        data: BlockToolData<HeadingBlockData>;
        api: API;
        config?: Record<string, unknown>;
    });
    render(): HTMLElement;
    save(): HeadingBlockData;
    validate(data: HeadingBlockData): boolean;
    renderSettings(): HTMLDivElement;
    /** Handle paste of heading elements. */
    onPaste(event: Parameters<NonNullable<BlockTool['onPaste']>>[0]): void;
    private get availableLevels();
    private setLevel;
    private buildTag;
}
