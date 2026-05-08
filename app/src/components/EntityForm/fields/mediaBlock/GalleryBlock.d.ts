import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
import type { GalleryBlockData } from './types';
export declare class GalleryBlock implements BlockTool {
    static get toolbox(): ToolboxConfig;
    static get isReadOnlySupported(): boolean;
    private data;
    private wrapper;
    private scroller;
    private currentIndex;
    private upload;
    private batchTotal;
    private batchDone;
    private prevBtn;
    private nextBtn;
    private moveLeftBtn;
    private moveRightBtn;
    private counterEl;
    private addBtn;
    private batchIndicatorEl;
    constructor({ data, api }: {
        data: BlockToolData<GalleryBlockData>;
        api: API;
    });
    render(): HTMLElement;
    save(): GalleryBlockData;
    renderSettings(): HTMLElement;
    /** Full repaint of the gallery DOM. */
    private paint;
    /** Build a single slide element. */
    private buildSlide;
    private buildToolbar;
    private buildControls;
    /** Update counter, disabled states, and active slide class without full repaint. */
    private syncControls;
    private buildPlaceholder;
    private goTo;
    private moveSlide;
    private removeCurrentSlide;
    private clampIndex;
    /** Update the batch upload counter without full repaint. */
    private updateBatchIndicator;
    /** Update alt text on the active slide without full repaint. */
    private updateAlt;
    /** Update lazy attribute on all slides without full repaint. */
    private updateLazy;
    private buildCaptionInput;
    private openPicker;
    private startUploadBatch;
    private startUpload;
    private notify;
    destroy(): void;
}
