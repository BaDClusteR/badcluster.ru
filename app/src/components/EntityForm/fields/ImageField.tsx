import {useState, useRef} from "react";
import {Text, Stack} from "@mantine/core";
import {uploadMedia, type UploadHandle} from "./mediaBlock/uploadMedia";
import type {MediaData} from "./mediaBlock/types";
import {notify} from "@/lib/notify";
import classes from "./ImageField.module.css";
import {iconBin, iconExternal, iconUpload} from "@/components/EntityForm/fields/mediaBlock/icons.ts";
import clsx from "clsx";

interface ImageFieldProps {
  label?: string;
  description?: React.ReactNode;
  withAsterisk?: boolean;
  error?: React.ReactNode;
  value?: MediaData | null;
  onChange: (media: MediaData | null) => void;
  previewWidth?: number | string;
  thumbnailWidth?: number;
  thumbnailHeight?: number;
  uploadPurpose?: string;
  showAlt?: boolean;
}

export function ImageField({
                             label,
                             description,
                             withAsterisk,
                             error,
                             value,
                             onChange,
                             thumbnailWidth,
                             thumbnailHeight,
                             showAlt = false,
                             uploadPurpose
                           }: ImageFieldProps) {
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [blobUrl, setBlobUrl] = useState<string | null>(null);
  const [alt, setAlt] = useState(value?.alt ?? "");
  const uploadRef = useRef<UploadHandle | null>(null);

  function openPicker() {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.addEventListener("change", () => {
      const file = input.files?.[0];
      if (file) startUpload(file);
    });
    input.click();
  }

  function startUpload(file: File) {
    uploadRef.current?.abort();

    const url = URL.createObjectURL(file);
    setBlobUrl(url);
    setUploading(true);
    setProgress(0);

    const handle = uploadMedia(file, ({fraction}) => {
      setProgress(Math.round(fraction * 100));
    }, uploadPurpose);
    uploadRef.current = handle;

    handle.promise
    .then((media) => {
      URL.revokeObjectURL(url);
      setBlobUrl(null);
      setUploading(false);
      setAlt(media.alt ?? "");
      onChange(media);
    })
    .catch((err) => {
      URL.revokeObjectURL(url);
      setBlobUrl(null);
      setUploading(false);
      notify.error(err.message || "Ошибка загрузки");
    });
  }

  function remove() {
    uploadRef.current?.abort();
    if (blobUrl) URL.revokeObjectURL(blobUrl);
    setBlobUrl(null);
    setUploading(false);
    setAlt("");
    onChange(null);
  }

  function updateAlt(newAlt: string) {
    setAlt(newAlt);
    if (value) {
      onChange({...value, alt: newAlt});
    }
  }

  const previewSrc = blobUrl ?? value?.url;
  const hasImage = !!previewSrc;

  // Find smallest thumb for thumbnail
  const thumbSrc = value?.thumbs?.length
    ? [...value.thumbs]
  .filter(t => t.mime === "image/webp" || t.mime === "image/jpeg")
  .sort((a, b) => a.width - b.width)[0]?.url ?? value.url
    : value?.url;

  return (
    <Stack gap={4}>
      {label && (
        <Text size="sm" fw={500}>
          {label}
          {withAsterisk && <span className={classes.asterisk}> *</span>}
        </Text>
      )}
      {description && <Text size="xs" c="dimmed">{description}</Text>}

      <div className={clsx(classes.container, hasImage && classes.hasImage)}>
        {hasImage ? (
          <>
            {/* Image preview with overlay */}
            <div className={classes.preview}>
              <img
                src={previewSrc}
                alt={alt}
                className={uploading ? classes.imgUploading : classes.img}
              />

              {/* Upload progress */}
              {uploading && (
                <div className={classes.progressBar}>
                  <div className={classes.progressFill} style={{width: `${progress}%`}}/>
                </div>
              )}

              {/* Hover overlay — click to replace */}
              {!uploading && (
                <button
                  type="button"
                  className={classes.overlay}
                  onClick={openPicker}
                >
                  <span
                    className={classes.overlayIcon}
                    dangerouslySetInnerHTML={{__html: iconUpload}}
                  />
                  <span className={classes.overlayText}>Заменить</span>
                </button>
              )}

              {/* Action buttons (top-right) */}
              {!uploading && (
                <div className={classes.topActions}>
                  <a
                    href={value?.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={classes.topActionBtn}
                    title="Открыть оригинал"
                    dangerouslySetInnerHTML={{__html: iconExternal}}
                  />
                  <button
                    type="button"
                    className={`${classes.topActionBtn} ${classes.topActionBtnDanger}`}
                    onClick={remove}
                    title="Удалить"
                    dangerouslySetInnerHTML={{__html: iconBin}}
                  />
                </div>
              )}
            </div>

            {/* Alt text input */}
            {showAlt && !uploading && (
              <input
                type="text"
                className={classes.altInput}
                placeholder="Alt текст"
                value={alt}
                onChange={(e) => updateAlt(e.target.value)}
              />
            )}

            {/* Thumbnail */}
            {thumbnailWidth && value && !uploading && (
              <div className={classes.thumbnail} style={{width: thumbnailWidth, height: thumbnailHeight}}>
                <img src={thumbSrc} alt={alt} className={classes.img}/>
              </div>
            )}
          </>
        ) : (
          /* Empty state — placeholder button */
          <button type="button" className={classes.placeholder} onClick={openPicker}>
            <span
              className={classes.placeholderIcon}
              dangerouslySetInnerHTML={{__html: iconUpload}}
            />
            <span className={classes.placeholderText}>Нажмите, чтобы выбрать изображение</span>
          </button>
        )}
      </div>

      {error && <Text size="xs" c="red">{error}</Text>}
    </Stack>
  );
}
