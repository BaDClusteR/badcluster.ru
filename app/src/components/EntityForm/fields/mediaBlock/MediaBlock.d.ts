import type { API, BlockTool, BlockToolData, ToolboxConfig } from "@editorjs/editorjs";
import type { MediaBlockData } from "./types";
export declare class MediaBlock implements BlockTool {
    static get toolbox(): ToolboxConfig;
    /** Block is stateful (uploads, settings changes) → tell Editor.js. */
    static get isReadOnlySupported(): boolean;
    private api;
    private readonly data;
    private wrapper;
    private upload;
    private imageId;
    constructor({ data, api }: {
        data: BlockToolData<MediaBlockData>;
        api: API;
    });
    render(): HTMLElement;
    save(): MediaBlockData;
    renderSettings(): HTMLDivElement;
    private paint;
    private fullRepaint;
    private quickUpdate;
    private buildCaptionInput;
    /** Opens a native file picker, then kicks off the upload if a file was chosen. */
    private openPicker;
    /** Shows a local preview + progress bar and uploads the file. */
    private startUpload;
    private notify;
    destroy(): void;
}
