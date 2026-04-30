import type { API, BlockTool, BlockToolData, ToolboxConfig } from '@editorjs/editorjs';
import type { GalleryBlockData, MediaData } from './types';
import { uploadMedia, type UploadHandle } from './uploadMedia';
import { renderPicture } from './renderPicture';
import classes from './GalleryBlock.module.css';
import TextField from './settings/textfield';
import separator from './settings/separator';
import Toggle from './settings/toggle';
import {notify} from "@/lib/notify.ts";

const ICON_GALLERY = `<svg width="17" height="15" viewBox="0 0 17 15" xmlns="http://www.w3.org/2000/svg"><path d="M3 3h11a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm0 1v9h11V4H3Zm2.5 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm7 4H4l2.5-3.5L8 10l2.5-3.5L13.5 12H10.5Z" fill="currentColor"/><rect x="0" y="1" width="2" height="10" rx=".5" fill="currentColor" opacity=".35"/><rect x="15" y="1" width="2" height="10" rx=".5" fill="currentColor" opacity=".35"/></svg>`;

const ICON_PREV = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>`;
const ICON_NEXT = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>`;
const ICON_ADD = `<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2H9v5a1 1 0 1 1-2 0V9H2a1 1 0 0 1 0-2h5V2a1 1 0 0 1 1-1Z"/></svg>`;
const ICON_DELETE = `<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1 0-2h3a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1h3a1 1 0 0 1 1 1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118Z"/></svg>`;
const ICON_LAZY = `<svg width="17" height="14" viewBox="0 0 17 14" xmlns="http://www.w3.org/2000/svg"><path d="M8.5 1.5C4.5 1.5 1.2 4 .5 7c.7 3 4 5.5 8 5.5s7.3-2.5 8-5.5c-.7-3-4-5.5-8-5.5Zm0 9A3.5 3.5 0 1 1 12 7a3.5 3.5 0 0 1-3.5 3.5Zm0-5.5A2 2 0 1 0 10.5 7a2 2 0 0 0-2-2Z" fill="currentColor"/></svg>`;
const ICON_MOVE_LEFT = `<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M10 3L5 8l5 5V3Z"/></svg>`;
const ICON_MOVE_RIGHT = `<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M6 3l5 5-5 5V3Z"/></svg>`;

/**
 * Editor.js block: image slideshow / gallery.
 *
 * Stores an ordered list of MediaData items and exposes prev/next navigation,
 * add / remove buttons, and per-slide alt text + global lazy-load toggle in
 * the block settings popover.
 */
export class GalleryBlock implements BlockTool {
  static get toolbox(): ToolboxConfig {
    return {
      title: 'Галерея',
      icon: ICON_GALLERY,
    };
  }

  static get isReadOnlySupported(): boolean {
    return false;
  }

  private api: API;
  private data: GalleryBlockData;
  private wrapper!: HTMLDivElement;
  private scroller!: HTMLElement;
  private currentIndex = 0;
  private upload: UploadHandle | null = null;
  private batchTotal = 0;
  private batchDone = 0;

  // Cached control references for lightweight updates
  private prevBtn!: HTMLButtonElement;
  private nextBtn!: HTMLButtonElement;
  private moveLeftBtn!: HTMLButtonElement;
  private moveRightBtn!: HTMLButtonElement;
  private counterEl!: HTMLElement;
  private addBtn!: HTMLButtonElement;
  private batchIndicatorEl!: HTMLElement;

  constructor({ data, api }: { data: BlockToolData<GalleryBlockData>; api: API }) {
    this.api = api;
    this.data = {
      slides: Array.isArray(data?.slides) ? data.slides : [],
      lazy: data?.lazy ?? true,
    };
    if (this.data.slides.length > 0) {
      this.currentIndex = 0;
    }
  }

  render(): HTMLElement {
    this.wrapper = document.createElement('div');
    this.wrapper.className = classes.wrapper;
    this.paint();

    if (this.data.slides.length === 0) {
      queueMicrotask(() => this.openPicker());
    }

    return this.wrapper;
  }

  save(): GalleryBlockData {
    return {
      slides: this.data.slides,
      lazy: this.data.lazy,
    };
  }

  renderSettings(): HTMLElement {
    const wrapper = document.createElement('div');
    wrapper.classList.add(classes.settingsWrapper);

    wrapper.appendChild(separator());

    const currentSlide = this.data.slides[this.currentIndex];

    if (currentSlide) {
      // -- Per-slide settings --
      const slideLabel = document.createElement('div');
      slideLabel.className = classes.settingsLabel;
      slideLabel.textContent = `Слайд ${this.currentIndex + 1}`;
      wrapper.appendChild(slideLabel);

      wrapper.appendChild(
        TextField({
          placeholder: 'Альт текст',
          onChange: (value: string) => {
            if (this.data.slides[this.currentIndex]) {
              this.data.slides[this.currentIndex].alt = value;
              this.updateAlt();
            }
          },
          value: currentSlide.alt ?? '',
        }),
      );

      wrapper.appendChild(separator());
    }

    const globalLabel = document.createElement('div');
    globalLabel.className = classes.settingsLabel;
    globalLabel.textContent = 'Слайдшоу';
    wrapper.appendChild(globalLabel);

    wrapper.appendChild(
      Toggle({
        value: this.data.lazy,
        onChange: (checked: boolean) => {
          this.data.lazy = checked;
          this.updateLazy();
        },
        icon: ICON_LAZY,
        label: 'Lazy load',
      }),
    );

    return wrapper;
  }

  /** Full repaint of the gallery DOM. */
  private paint() {
    this.wrapper.innerHTML = '';

    if (this.data.slides.length === 0) {
      this.wrapper.appendChild(this.buildPlaceholder());
      return;
    }

    this.clampIndex();

    // Viewport with overflow hidden — only the active slide is visible
    const viewport = document.createElement('div');
    viewport.className = classes.viewport;

    // Scroller contains ALL slides
    this.scroller = document.createElement('ul');
    this.scroller.className = classes.scroller;

    for (let i = 0; i < this.data.slides.length; i++) {
      this.scroller.appendChild(this.buildSlide(i));
    }

    viewport.appendChild(this.scroller);

    // Slide toolbar (overlay, shared for all slides)
    viewport.appendChild(this.buildToolbar());

    this.wrapper.appendChild(viewport);
    this.wrapper.appendChild(this.buildControls());
    this.syncControls();
  }

  /** Build a single slide element. */
  private buildSlide(index: number): HTMLElement {
    const slide = document.createElement('li');
    slide.className = classes.slide;
    if (index === this.currentIndex) {
      slide.classList.add(classes.slideActive);
    }

    const pictureEl = renderPicture(this.data.slides[index], {
      lazy: this.data.lazy,
      className: classes.media,
    });
    slide.appendChild(pictureEl);
    return slide;
  }

  private buildToolbar(): HTMLElement {
    const toolbar = document.createElement('div');
    toolbar.className = classes.slideToolbar;

    this.moveLeftBtn = document.createElement('button');
    this.moveLeftBtn.type = 'button';
    this.moveLeftBtn.className = classes.slideToolbarBtn;
    this.moveLeftBtn.innerHTML = ICON_MOVE_LEFT;
    this.moveLeftBtn.title = 'Переместить влево';
    this.moveLeftBtn.addEventListener('click', () => this.moveSlide(-1));

    this.moveRightBtn = document.createElement('button');
    this.moveRightBtn.type = 'button';
    this.moveRightBtn.className = classes.slideToolbarBtn;
    this.moveRightBtn.innerHTML = ICON_MOVE_RIGHT;
    this.moveRightBtn.title = 'Переместить вправо';
    this.moveRightBtn.addEventListener('click', () => this.moveSlide(1));

    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.className = `${classes.slideToolbarBtn} ${classes.slideToolbarBtnDanger}`;
    deleteBtn.innerHTML = ICON_DELETE;
    deleteBtn.title = 'Удалить слайд';
    deleteBtn.addEventListener('click', () => this.removeCurrentSlide());

    toolbar.appendChild(this.moveLeftBtn);
    toolbar.appendChild(this.moveRightBtn);
    toolbar.appendChild(deleteBtn);

    return toolbar;
  }

  private buildControls(): HTMLElement {
    const controls = document.createElement('div');
    controls.className = classes.controls;

    this.prevBtn = document.createElement('button');
    this.prevBtn.type = 'button';
    this.prevBtn.className = classes.navBtn;
    this.prevBtn.innerHTML = ICON_PREV;
    this.prevBtn.addEventListener('click', () => this.goTo(this.currentIndex - 1));

    this.counterEl = document.createElement('div');
    this.counterEl.className = classes.counter;

    this.nextBtn = document.createElement('button');
    this.nextBtn.type = 'button';
    this.nextBtn.className = classes.navBtn;
    this.nextBtn.innerHTML = ICON_NEXT;
    this.nextBtn.addEventListener('click', () => this.goTo(this.currentIndex + 1));

    this.addBtn = document.createElement('button');
    this.addBtn.type = 'button';
    this.addBtn.className = classes.addBtn;
    this.addBtn.innerHTML = ICON_ADD;
    this.addBtn.title = 'Добавить слайд';
    this.addBtn.addEventListener('click', () => this.openPicker());

    this.batchIndicatorEl = document.createElement('div');
    this.batchIndicatorEl.className = classes.batchIndicator;

    controls.appendChild(this.prevBtn);
    controls.appendChild(this.counterEl);
    controls.appendChild(this.nextBtn);
    controls.appendChild(this.addBtn);
    controls.appendChild(this.batchIndicatorEl);

    return controls;
  }

  /** Update counter, disabled states, and active slide class without full repaint. */
  private syncControls() {
    const total = this.data.slides.length;
    const idx = this.currentIndex;

    // Counter
    this.counterEl.textContent = `${idx + 1} / ${total}`;

    // Nav buttons
    this.prevBtn.disabled = idx === 0;
    this.nextBtn.disabled = idx === total - 1;

    // Toolbar buttons
    this.moveLeftBtn.disabled = idx === 0;
    this.moveRightBtn.disabled = idx === total - 1;

    // Active slide class
    const slideEls = this.scroller.children;
    for (let i = 0; i < slideEls.length; i++) {
      slideEls[i].classList.toggle(classes.slideActive, i === idx);
    }
  }

  private buildPlaceholder(): HTMLElement {
    const placeholder = document.createElement('button');
    placeholder.type = 'button';
    placeholder.className = classes.placeholder;
    placeholder.innerHTML = `
      <div class="${classes.placeholderIcon}">${ICON_GALLERY}</div>
      <div class="${classes.placeholderText}">Нажмите, чтобы добавить изображения</div>
    `;
    placeholder.addEventListener('click', () => this.openPicker());
    return placeholder;
  }

  private goTo(index: number) {
    this.currentIndex = index;
    this.clampIndex();
    this.syncControls();
  }

  private moveSlide(direction: -1 | 1) {
    const target = this.currentIndex + direction;
    if (target < 0 || target >= this.data.slides.length) return;

    // Swap data
    const slides = this.data.slides;
    [slides[this.currentIndex], slides[target]] = [slides[target], slides[this.currentIndex]];

    // Swap DOM nodes
    const currentEl = this.scroller.children[this.currentIndex];
    const targetEl = this.scroller.children[target];
    if (direction === -1) {
      this.scroller.insertBefore(currentEl, targetEl);
    } else {
      this.scroller.insertBefore(targetEl, currentEl);
    }

    this.currentIndex = target;
    this.syncControls();
  }

  private removeCurrentSlide() {
    // Remove DOM node
    const slideEl = this.scroller.children[this.currentIndex];
    slideEl.remove();

    // Remove data
    this.data.slides.splice(this.currentIndex, 1);
    this.clampIndex();

    if (this.data.slides.length === 0) {
      this.paint();
    } else {
      this.syncControls();
    }
  }

  private clampIndex() {
    const max = Math.max(0, this.data.slides.length - 1);
    if (this.currentIndex > max) this.currentIndex = max;
    if (this.currentIndex < 0) this.currentIndex = 0;
  }

  /** Update the batch upload counter without full repaint. */
  private updateBatchIndicator() {
    if (!this.batchIndicatorEl) return;
    if (this.batchTotal > 1 && this.batchDone < this.batchTotal) {
      this.batchIndicatorEl.innerHTML = `<span class="${classes.spinner}"></span>${this.batchDone + 1} / ${this.batchTotal}`;
    } else {
      this.batchIndicatorEl.innerHTML = '';
    }
  }

  /** Update alt text on the active slide without full repaint. */
  private updateAlt() {
    const activeSlide = this.scroller.children[this.currentIndex];
    const img = activeSlide?.querySelector('img');
    if (img) {
      img.alt = this.data.slides[this.currentIndex]?.alt ?? '';
    }
  }

  /** Update lazy attribute on all slides without full repaint. */
  private updateLazy() {
    const imgs = this.scroller.querySelectorAll('img');
    for (const img of imgs) {
      if (this.data.lazy) {
        img.loading = 'lazy';
      } else {
        img.removeAttribute('loading');
      }
    }
  }

  private openPicker() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.multiple = true;
    input.addEventListener('change', () => {
      const files = input.files;
      if (files && files.length > 0) {
        this.startUploadBatch(Array.from(files));
      }
    });
    input.click();
  }

  private async startUploadBatch(files: File[]) {
    this.batchTotal = files.length;
    this.batchDone = 0;
    if (this.addBtn) this.addBtn.disabled = true;
    this.updateBatchIndicator();

    for (const file of files) {
      await this.startUpload(file);
      this.batchDone++;
      this.updateBatchIndicator();
    }

    this.batchTotal = 0;
    this.batchDone = 0;
    if (this.addBtn) this.addBtn.disabled = false;
  }

  private startUpload(file: File): Promise<void> {
    // Show upload preview in the active slide area
    const activeSlide = this.scroller?.children[this.currentIndex] as HTMLElement | undefined;
    const target = activeSlide ?? this.wrapper;

    const uploadBox = document.createElement('div');
    uploadBox.className = classes.uploading;

    const blobUrl = URL.createObjectURL(file);
    const previewEl = document.createElement('img');
    previewEl.className = classes.preview;
    previewEl.src = blobUrl;
    uploadBox.appendChild(previewEl);

    const progress = document.createElement('div');
    progress.className = classes.progress;
    const progressBar = document.createElement('div');
    progressBar.className = classes.progressBar;
    progress.appendChild(progressBar);
    uploadBox.appendChild(progress);

    target.innerHTML = '';
    target.appendChild(uploadBox);

    this.upload = uploadMedia(file, ({ fraction }) => {
      progressBar.style.width = `${Math.round(fraction * 100)}%`;
    });

    return this.upload.promise
      .then((media: MediaData) => {
        URL.revokeObjectURL(blobUrl);
        this.upload = null;
        this.data.slides.push(media);

        if (!this.scroller) {
          // First slide added — need full paint to build the gallery structure
          this.currentIndex = 0;
          this.paint();
        } else {
          // Restore the slide that was used as upload preview target
          if (activeSlide && this.data.slides[this.currentIndex]) {
            activeSlide.innerHTML = '';
            activeSlide.appendChild(renderPicture(this.data.slides[this.currentIndex], {
              lazy: this.data.lazy,
              className: classes.media,
            }));
          }

          // Append new slide DOM node
          const newSlideEl = this.buildSlide(this.data.slides.length - 1);
          this.scroller.appendChild(newSlideEl);
          this.currentIndex = this.data.slides.length - 1;
          this.syncControls();
        }

        this.notify('Загружено', 'success');
      })
      .catch((err: Error) => {
        URL.revokeObjectURL(blobUrl);
        this.upload = null;
        this.notify(err.message || 'Ошибка загрузки', 'error');
        // Restore the active slide's content
        if (activeSlide && this.data.slides[this.currentIndex]) {
          activeSlide.innerHTML = '';
          activeSlide.appendChild(renderPicture(this.data.slides[this.currentIndex], {
            lazy: this.data.lazy,
            className: classes.media,
          }));
        } else {
          this.paint();
        }
      });
  }

  private notify(message: string, type: 'success' | 'error') {
      if (type === 'success') {
          notify.success(message);
      } else {
          notify.error(message);
      }
  }

  destroy() {
    if (this.upload) {
      this.upload.abort();
      this.upload = null;
    }
  }
}
