import type {API, BlockTool, BlockToolData, ToolboxConfig} from "@editorjs/editorjs";
import type {MediaBlockData, MediaData} from "./types";
import {uploadMedia, type UploadHandle} from "./uploadMedia";
import {renderPicture} from "./renderPicture";
import classes from "./MediaBlock.module.css";
import TextField from "./settings/textfield.ts";
import separator from "./settings/separator.ts";
import Toggle from "./settings/toggle.ts";
import {Nullable} from "@/types.ts";
import {iconLazyLoad, iconMedia} from "./icons.ts";

export class MediaBlock implements BlockTool {
    // noinspection JSUnusedGlobalSymbols
    static get toolbox(): ToolboxConfig {
        return {
            title: "Медиа",
            icon: iconMedia
        };
    }

    // noinspection JSUnusedGlobalSymbols
    /** Block is stateful (uploads, settings changes) → tell Editor.js. */
    static get isReadOnlySupported(): boolean {
        return false;
    }

    private api: API;
    private readonly data: MediaBlockData;
    private wrapper!: HTMLDivElement;
    private upload: UploadHandle | null = null;
    private imageId: Nullable<number> = null;

    constructor({data, api}: { data: BlockToolData<MediaBlockData>; api: API }) {
        this.api = api;
        this.data = {
            media: data?.media,
            lazy: data?.lazy ?? true,
            caption: data?.caption ?? '',
        };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement("div");
        this.wrapper.className = classes.wrapper;
        this.paint();

        // If this is a brand-new empty block, open the picker right away.
        if (!this.data.media) {
            // Defer so Editor.js has finished inserting the block into DOM.
            queueMicrotask(() => this.openPicker());
        }

        return this.wrapper;
    }

    save(): MediaBlockData {
        return {
            media: this.data.media,
            lazy: this.data.lazy,
            caption: this.data.caption,
        };
    }

    renderSettings() {
        const wrapper = document.createElement("div");
        wrapper.classList.add(classes.mediaSettingsWrapper);

        wrapper.appendChild(
            separator()
        );

        wrapper.appendChild(
            TextField({
                placeholder: "Альт текст",
                onChange: (value: string) => {
                    if (this.data.media) {
                        this.data.media.alt = value;
                        this.quickUpdate();
                    }
                },
                value: String(this.data.media?.alt || "")
            })
        );

        wrapper.appendChild(
            Toggle({
                value: this.data.lazy,
                onChange: (checked: boolean): void => {
                    this.data.lazy = checked;

                    this.quickUpdate();
                },
                icon: iconLazyLoad,
                label: "Lazy load"
            })
        );

        return wrapper;
    }

    private paint() {
        if (this.data.media?.id === this.imageId) {
            this.quickUpdate();
        } else {
            this.fullRepaint();
        }
    }

    private fullRepaint() {
        this.wrapper.innerHTML = "";

        // Uploaded state → native <picture> / <video>
        if (this.data.media) {
            const el = renderPicture(this.data.media, {
                lazy: this.data.lazy,
                className: classes.media
            });
            this.wrapper.appendChild(el);
            this.wrapper.appendChild(this.buildCaptionInput());

            this.imageId = this.data.media.id;
            return;
        }

        // Empty state → clickable placeholder
        const placeholder = document.createElement("button");
        placeholder.type = "button";
        placeholder.className = classes.placeholder;
        placeholder.innerHTML = `
      <div class="${classes.placeholderIcon}">${iconMedia}</div>
      <div class="${classes.placeholderText}">Нажмите, чтобы загрузить изображение или видео</div>
    `;
        placeholder.addEventListener("click", () => this.openPicker());
        this.wrapper.appendChild(placeholder);

        this.imageId = null;
    }

    private quickUpdate() {
        const img = this.wrapper.querySelector("img");
        if (img) {
            img.alt = this.data.media?.alt ?? "";
            if (this.data.lazy) {
                img.loading = "lazy";
            } else {
                img.removeAttribute("loading")
            }
        }

    }

    private buildCaptionInput(): HTMLElement {
        const figcaption = document.createElement('figcaption');
        figcaption.className = classes.caption;

        const textarea = document.createElement('textarea');
        textarea.className = classes.captionInput;
        textarea.placeholder = 'Подпись';
        textarea.value = this.data.caption ?? '';
        textarea.rows = 1;
        const autoResize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = `${textarea.scrollHeight}px`;
        };
        textarea.addEventListener('input', () => {
            this.data.caption = textarea.value;
            autoResize();
        });
        figcaption.appendChild(textarea);
        requestAnimationFrame(autoResize);

        return figcaption;
    }

    /** Opens a native file picker, then kicks off the upload if a file was chosen. */
    private openPicker() {
        const input = document.createElement("input");
        input.type = "file";
        input.accept = "image/*,video/*";
        input.addEventListener("change", () => {
            const file = input.files?.[0];
            if (file) this.startUpload(file);
        });
        input.click();
    }

    /** Shows a local preview + progress bar and uploads the file. */
    private startUpload(file: File) {
        this.wrapper.innerHTML = "";

        const previewBox = document.createElement("div");
        previewBox.className = classes.uploading;

        // Local preview via blob URL (no server round trip)
        const blobUrl = URL.createObjectURL(file);
        const isVideo = file.type.startsWith("video/");
        const previewEl = document.createElement(
            isVideo
                ? "video"
                : "img"
        );
        previewEl.className = classes.preview;
        (previewEl as HTMLImageElement | HTMLVideoElement).src = blobUrl;
        if (isVideo) {
            (previewEl as HTMLVideoElement).muted = true;
            (previewEl as HTMLVideoElement).autoplay = false;
        }
        previewBox.appendChild(previewEl);

        // Progress bar
        const progress = document.createElement("div");
        progress.className = classes.progress;
        const progressBar = document.createElement("div");
        progressBar.className = classes.progressBar;
        progress.appendChild(progressBar);
        previewBox.appendChild(progress);

        this.wrapper.appendChild(previewBox);

        this.upload = uploadMedia(file, ({fraction}) => {
            progressBar.style.width = `${Math.round(fraction * 100)}%`;
        });

        this.upload.promise
            .then((media: MediaData) => {
                this.data.media = media;
                URL.revokeObjectURL(blobUrl);
                this.upload = null;
                this.paint();
            })
            .catch((err: Error) => {
                URL.revokeObjectURL(blobUrl);
                this.upload = null;
                this.notify(err.message || `Не получилось загрузить ${file.name}.`, "error");
                this.paint();
            });
    }

    private notify(message: string, type: "success" | "error") {
        this.api.notifier.show({
            message,
            style: type === "success" ? "confirmation" : "alert"
        });
    }

    destroy() {
        if (this.upload) {
            this.upload.abort();
            this.upload = null;
        }
    }
}
