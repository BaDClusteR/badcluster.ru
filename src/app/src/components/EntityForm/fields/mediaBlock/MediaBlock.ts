import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
import type { MediaBlockData, MediaData } from './types';
import { uploadMedia, type UploadHandle } from './uploadMedia';
import { renderPicture } from './renderPicture';
import classes from './MediaBlock.module.css';
import TextField from "@/components/EntityForm/fields/mediaBlock/settings/textfield.ts";
import separator from "@/components/EntityForm/fields/mediaBlock/settings/separator.ts";
import Toggle from "@/components/EntityForm/fields/mediaBlock/settings/toggle.ts";
import {Nullable, Optional, StringKeyObject} from "@/types.ts";

const ICON_MEDIA = `<svg width="17" height="15" viewBox="0 0 17 15" xmlns="http://www.w3.org/2000/svg"><path d="M2 1h13a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1Zm0 1v11h13V2H2Zm3 4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm8 5H4l3-4 2 2 3-4 4 6h-3Z" fill="currentColor"/></svg>`;

/**
 * Custom Editor.js block for images and videos.
 *
 * Flow:
 *   1. On creation via the toolbox, file picker opens immediately.
 *   2. Chosen file gets a local blob preview + upload progress overlay.
 *   3. On successful upload, the server returns MediaData and the block
 *      swaps the preview for a native <picture> / <video>.
 *   4. Clicking an empty placeholder (e.g. after canceling the picker)
 *      reopens the file picker.
 *
 * Settings: `lazy` toggle, default ON — adds loading="lazy" to the rendered img.
 */
export class MediaBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: 'Медиа',
      icon: ICON_MEDIA,
    };
  }

  /** Block is stateful (uploads, settings changes) → tell Editor.js. */
  static get isReadOnlySupported(): boolean {
    return false;
  }

  private api: API;
  private readonly data: MediaBlockData;
  private wrapper!: HTMLDivElement;
  private upload: UploadHandle | null = null;
  private imageId: Nullable<number> = null;

  constructor({ data, api }: { data: BlockToolData<MediaBlockData>; api: API }) {
    this.api = api;
    this.data = {
      media: data?.media,
      lazy: data?.lazy ?? true,
    };
  }

  render(): HTMLElement {
    this.wrapper = document.createElement('div');
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
    };
  }

  /**
   * Block Tunes: renders custom items in the block settings popover.
   * The "Lazy load" item is a toggle.
   */
  renderSettings() {
      const wrapper = document.createElement('div');
      wrapper.classList.add(classes.mediaSettingsWrapper);

      wrapper.appendChild(
          separator()
      );

      wrapper.appendChild(
          TextField({
              placeholder: 'Альт текст',
              onChange: (value: string) => {
                  if (this.data.media) {
                      this.data.media.alt = value;
                      this.quickUpdate();
                  }
              },
              value: String(this.data.media?.alt || '')
          })
      );

      wrapper.appendChild(
          Toggle({
              value: this.data.lazy,
              onChange: (checked: boolean): void => {
                  this.data.lazy = checked;

                  this.quickUpdate();
              },
              icon: `<svg width="17" height="14" viewBox="0 0 17 14" xmlns="http://www.w3.org/2000/svg"><path d="M8.5 1.5C4.5 1.5 1.2 4 .5 7c.7 3 4 5.5 8 5.5s7.3-2.5 8-5.5c-.7-3-4-5.5-8-5.5Zm0 9A3.5 3.5 0 1 1 12 7a3.5 3.5 0 0 1-3.5 3.5Zm0-5.5A2 2 0 1 0 10.5 7a2 2 0 0 0-2-2Z" fill="currentColor"/></svg>`,
              label: "Lazy load"
          })
      );

      return wrapper;
  }

  /** Rebuilds the block's inner DOM based on current state. */
  private paint() {
      console.log({
          old: this.data.media?.id,
          new: this.imageId
      });
      if (this.data.media?.id === this.imageId) {
          this.quickUpdate();
      } else {
          this.fullRepaint();
      }
  }

  private fullRepaint() {
      this.wrapper.innerHTML = '';

      // Uploaded state → native <picture> / <video>
      if (this.data.media) {
          const el = renderPicture(this.data.media, {
              lazy: this.data.lazy,
              className: classes.media,
          });
          this.wrapper.appendChild(el);

          this.imageId = this.data.media.id;
          console.log(this.imageId);
          return;
      }

      // Empty state → clickable placeholder
      const placeholder = document.createElement('button');
      placeholder.type = 'button';
      placeholder.className = classes.placeholder;
      placeholder.innerHTML = `
      <div class="${classes.placeholderIcon}">${ICON_MEDIA}</div>
      <div class="${classes.placeholderText}">Click to upload an image or video</div>
    `;
      placeholder.addEventListener('click', () => this.openPicker());
      this.wrapper.appendChild(placeholder);

      this.imageId = null;
  }

  private quickUpdate() {
      const img = this.wrapper.querySelector('img');
      if (img) {
          img.alt = this.data.media?.alt ?? '';
          if (this.data.lazy) {
              img.loading = "lazy";
          } else {
              img.removeAttribute("loading")
          }
      }
  }

  /** Opens a native file picker, then kicks off the upload if a file was chosen. */
  private openPicker() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,video/*';
    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (file) this.startUpload(file);
    });
    input.click();
  }

  /** Shows a local preview + progress bar and uploads the file. */
  private startUpload(file: File) {
    this.wrapper.innerHTML = '';

    const previewBox = document.createElement('div');
    previewBox.className = classes.uploading;

    // Local preview via blob URL (no server round trip)
    const blobUrl = URL.createObjectURL(file);
    const isVideo = file.type.startsWith('video/');
    const previewEl = isVideo ? document.createElement('video') : document.createElement('img');
    previewEl.className = classes.preview;
    (previewEl as HTMLImageElement | HTMLVideoElement).src = blobUrl;
    if (isVideo) {
      (previewEl as HTMLVideoElement).muted = true;
      (previewEl as HTMLVideoElement).autoplay = false;
    }
    previewBox.appendChild(previewEl);

    // Progress bar
    const progress = document.createElement('div');
    progress.className = classes.progress;
    const progressBar = document.createElement('div');
    progressBar.className = classes.progressBar;
    progress.appendChild(progressBar);
    previewBox.appendChild(progress);

    this.wrapper.appendChild(previewBox);

    this.upload = uploadMedia(file, ({ fraction }) => {
      progressBar.style.width = `${Math.round(fraction * 100)}%`;
    });

    this.upload.promise
      .then((media: MediaData) => {
        this.data.media = media;
        URL.revokeObjectURL(blobUrl);
        this.upload = null;
        this.paint();
        this.notify('Uploaded', 'success');
      })
      .catch((err: Error) => {
        URL.revokeObjectURL(blobUrl);
        this.upload = null;
        this.notify(err.message || 'Upload failed', 'error');
        this.paint();
      });
  }

  private notify(message: string, type: 'success' | 'error') {
    this.api.notifier.show({
      message,
      style: type === 'success' ? 'confirmation' : 'alert',
    });
  }

  destroy() {
    if (this.upload) {
      this.upload.abort();
      this.upload = null;
    }
  }
}
