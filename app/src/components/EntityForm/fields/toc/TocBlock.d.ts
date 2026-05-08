import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
export interface TocItem {
    text: string;
    anchor: string;
    level: number;
}
export interface TocBlockData {
    items: TocItem[];
    label: string;
}
export declare class TocBlock implements BlockTool {
    static get toolbox(): ToolboxConfig;
    static get isReadOnlySupported(): boolean;
    private api;
    private data;
    private wrapper;
    private listEl;
    constructor({ data, api }: {
        data: BlockToolData<TocBlockData>;
        api: API;
    });
    render(): HTMLElement;
    save(): TocBlockData;
    /** Collect headings from the editor's blocks. */
    private collectFromHeadings;
    private collectHeadingDataAsync;
    private buildList;
    /** Read edited texts back from the DOM into data. */
    private syncFromDom;
}
