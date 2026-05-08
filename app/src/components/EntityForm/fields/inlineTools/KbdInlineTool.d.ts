import type { API, InlineTool } from '@editorjs/editorjs';
/**
 * Inline tool that wraps selected text in <kbd>...</kbd>.
 */
export declare class KbdInlineTool implements InlineTool {
    static get isInline(): boolean;
    static get shortcut(): string;
    static get sanitize(): {
        kbd: boolean;
    };
    static get title(): string;
    private api;
    private button;
    private active;
    constructor({ api }: {
        api: API;
    });
    render(): HTMLButtonElement;
    surround(range: Range | null): void;
    checkState(): boolean;
    private wrap;
    private unwrap;
}
