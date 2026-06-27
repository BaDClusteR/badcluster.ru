import {useState, useRef} from "react";
import {Anchor, Text, Stack} from "@mantine/core";
import {uploadFile, type FileUploadHandle} from "./uploadFile";
import {notify} from "@/lib/notify";
import classes from "./FileField.module.css";

export interface FileData {
  id: number;
  filename: string;
  size: number;
  sizeHumanReadable: string;
  mime: string;
  url: string;
}

interface FileFieldProps {
  label?: string;
  description?: React.ReactNode;
  withAsterisk?: boolean;
  error?: React.ReactNode;
  value?: FileData | null;
  onChange: (file: FileData | null) => void;
  /** Upload endpoint. Defaults to /admin/api/upload. */
  uploadEndpoint?: string;
  /** Extra form fields to send with the upload. */
  uploadFields?: Record<string, string>;
  /** Accepted file types (e.g. ".zip,.pdf"). Defaults to all. */
  accept?: string;
  /** Custom function to render subtitle (shown after size). By default shows mime type. */
  subtitle?: (file: FileData) => string;
}

export function FileField({
  label,
  description,
  withAsterisk,
  error,
  value,
  onChange,
  uploadEndpoint = "/admin/api/upload",
  uploadFields,
  accept,
  subtitle,
}: FileFieldProps) {
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [fileName, setFileName] = useState<string | null>(null);
  const uploadRef = useRef<FileUploadHandle | null>(null);

  function openPicker() {
    if (uploading) return;
    const input = document.createElement("input");
    input.type = "file";
    if (accept) input.accept = accept;
    input.addEventListener("change", () => {
      const file = input.files?.[0];
      if (file) startUpload(file);
    });
    input.click();
  }

  function startUpload(file: File) {
    uploadRef.current?.abort();
    setUploading(true);
    setProgress(0);
    setFileName(file.name);

    const handle = uploadFile<FileData>(file, uploadEndpoint, ({fraction}) => {
      setProgress(Math.round(fraction * 100));
    }, uploadFields);
    uploadRef.current = handle;

    handle.promise
      .then((data) => {
        setUploading(false);
        setFileName(null);
        onChange(data);
      })
      .catch((err) => {
        setUploading(false);
        setFileName(null);
        notify.error(err.message || "Ошибка загрузки");
      });
  }

  function remove() {
    uploadRef.current?.abort();
    setUploading(false);
    setFileName(null);
    onChange(null);
  }

  return (
    <Stack gap={4}>
      {label && (
        <Text size="sm" fw={500}>
          {label}
          {withAsterisk && <span className={classes.asterisk}> *</span>}
        </Text>
      )}
      {description && <Text size="xs" c="dimmed">{description}</Text>}

      {uploading ? (
        /* Uploading state */
        <div className={classes.uploadingBox}>
          <div className={classes.uploadingInfo}>
            <Text size="sm" truncate>{fileName}</Text>
            <Text size="xs" c="dimmed">{progress}%</Text>
          </div>
          <div className={classes.progressBar}>
            <div className={classes.progressFill} style={{width: `${progress}%`}}/>
          </div>
        </div>
      ) : value ? (
        /* File loaded */
        <div className={classes.fileBox}>
          <div className={classes.fileInfo}>
            <Anchor href={value.url} target="_blank" size="sm" fw={500} truncate="end">
              {value.filename}
            </Anchor>
            <Text size="xs" c="dimmed">
              {value.sizeHumanReadable} · {subtitle ? subtitle(value) : value.mime}
            </Text>
          </div>
          <div className={classes.fileActions}>
            <button type="button" className={classes.actionBtn} onClick={openPicker}>
              Заменить
            </button>
            <button type="button" className={classes.actionBtnDanger} onClick={remove}>
              Удалить
            </button>
          </div>
        </div>
      ) : (
        /* Empty state */
        <button type="button" className={classes.placeholder} onClick={openPicker}>
          <span className={classes.placeholderText}>Нажмите, чтобы загрузить файл</span>
        </button>
      )}

      {error && <Text size="xs" c="red">{error}</Text>}
    </Stack>
  );
}