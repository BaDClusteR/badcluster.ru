import classes from "./FileBlock.module.css";
import {File, Optional} from "@admin/types";
import {ReactNode} from "react";
import formatDate from "../utils";

export default function FileBlock({file, dateAdded}: { file: Optional<File>, dateAdded: Optional<string> }): ReactNode {
  const date = formatDate(dateAdded);

  return <section className={classes.file}>
    <div className={classes.fileInfo}>
      <span className={classes.fileLabel}>Файл:</span>
      <span className={classes.fileName}>
        {file?.filename ?? "---"}
      </span>
      {
        file &&
        <div className={classes.fileMeta}>
          <span>{file.sizeHumanReadable}</span>
          {
            date &&
            <>
              <span className={classes.fileMetaDot}>•</span>
              <span>Добавлен {formatDate(dateAdded)}</span>
            </>
          }
        </div>
      }
    </div>
    {
      file &&
      <div className={classes.fileActions}>
        <a href={file.url} className={classes.fileAction}>
          <svg viewBox="3 2 20 20" width="18" height="18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path
              fill="currentColor"
              d="M11 14.59V3a1 1 0 0 1 2 0v11.59l3.3-3.3a1 1 0 0 1 1.4 1.42l-5 5a1 1 0 0 1-1.4 0l-5-5a1 1 0 0 1 1.4-1.42l3.3 3.3zM3 17a1 1 0 0 1 2 0v3h14v-3a1 1 0 0 1 2 0v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3z"
            />
          </svg>
          Скачать
        </a>
      </div>
    }
  </section>;
}
