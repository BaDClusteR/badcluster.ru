import {type BookFormat} from "../../types";
import {Skeleton, Switch, Textarea, TextInput} from "@mantine/core";
import {useEffect, useRef, useState} from "react";
import {Optional} from "@admin/types";
import classes from "./BookFormats.module.css";
import {useAdminCore} from "../../../admin/useAdminCore";
import {BookFormatGenerateResponse} from "./types";
import {IconClear, IconDownload, IconUpdate} from "./icons";

export type BookFormatChangeCallback = (format: string, allowed: boolean, filename: string, postfix: string) => void;

function BookFormat(props: {
  format: Optional<BookFormat>,
  type?: string,
  onChange: BookFormatChangeCallback,
  submitting?: boolean
}) {
  const {format, type} = props;
  const {Button, apiCall, notify} = useAdminCore();

  const [isAllowed, setAllowed] = useState<boolean>(format?.allowed ?? false);
  const [generating, setGenerating] = useState<boolean>(false);
  const [clearing, setClearing] = useState<boolean>(false);
  const [sizeHumanReadable, setSizeHumanReadable] = useState<string>("0 байт");
  const [size, setSize] = useState<number>(format?.size ?? 0);
  const [date, setDate] = useState<string>(format?.dateGenerated ?? "");
  const [synced, setSynced] = useState<boolean>(false);
  const [filename, setFilename] = useState<string>("");
  const [downloadable, setDownloadable] = useState<boolean>(false);

  useEffect(
    () => {
      if (format && !synced) {
        setSizeHumanReadable(format.sizeHumanReadable);
        setSize(format.size);
        setDate(format.dateGenerated);
        setFilename(format.filename);
        setSynced(true);
        setDownloadable(format.allowed);
      }
    },
    [format]
  );

  // Sync when data arrives from backend (format prop updates after fetch)
  useEffect(() => {
    setAllowed(format?.allowed ?? false);
  }, [format?.allowed]);

  // After successful save — re-sync from current format prop
  const wasSubmitting = useRef(false);
  useEffect(() => {
    if (wasSubmitting.current && !props.submitting && format) {
      setFilename(format.filename);
      setDownloadable(format.allowed);
    }
    wasSubmitting.current = props.submitting ?? false;
  }, [props.submitting]);

  return <>
    <Switch
      classNames={{
        label: classes.formatLabel,
        track: classes.formatSwitchTrack,
        input: classes.formatInput,
        labelWrapper: classes.formatLabelWrapper
      }}
      label={String(format?.type ?? type).toUpperCase()}
      checked={isAllowed}
      onChange={(e) => {
        setAllowed(e.target.checked);
        props.onChange(
          String(format?.type ?? type),
          e.target.checked,
          format?.filename || "",
          String(format?.postfix ?? "")
        );
      }}
    />
    {
      isAllowed && <>
        <TextInput
          label="Имя файла:"
          required
          withAsterisk
          value={format?.filename ?? ""}
          onChange={(e) => props.onChange(
            String(format?.type ?? type),
            true,
            e.target.value,
            String(format?.postfix ?? "")
          )}
        />
        <Textarea
          label="Текст после глав:"
          description="Если оставить пустым, будет стандартный текст после глав. Плейсхолдеры: {{start_year}}, {{end_year}}."
          value={format?.postfix ?? ""}
          onChange={(e) => props.onChange(
            String(format?.type ?? type),
            true,
            String(format?.filename ?? ""),
            e.target.value
          )}
        />
      </>
    }
    {
      isAllowed && (
        <table>
          <tbody>
          <tr>
            <th>Закэшированный дамп:</th>
            <td>{sizeHumanReadable}</td>
          </tr>
          {
            size > 0 &&
            <tr>
              <th>Дата генерации:</th>
              <td>{date}</td>
            </tr>
          }
          {
            format?.id &&
            <tr>
              <th>&nbsp;</th>
              <td>
                <span className={classes.actions}>
                  {
                    size > 0 &&
                    <Button
                      disabled={generating || clearing || size === 0}
                      loading={clearing}
                      className={classes.actionBtn}
                      classNames={{loader: classes.actionLoader}}
                      onClick={
                        async () => {
                          setClearing(true);
                          await apiCall("GET", "book_format_clear", {formatId: format.id});
                          notify.success("Кэш очищен");
                          setSize(0);
                          setSizeHumanReadable("0 байт");
                          setClearing(false);
                        }
                      }
                    >
                      <span className="visually-hidden">Очистить кэш</span>
                      <IconClear/>
                    </Button>
                  }
                  <Button
                    disabled={generating || clearing}
                    loading={generating}
                    className={classes.actionBtn}
                    classNames={{loader: classes.actionLoader}}
                    onClick={
                      async () => {
                        setGenerating(true);
                        const response = await apiCall(
                          "GET",
                          "book_format_generate",
                          {formatId: format.id}
                        ) as BookFormatGenerateResponse;

                        notify.success("Кэш обновлен");
                        setSize(response.size);
                        setSizeHumanReadable(response.sizeHumanReadable);
                        setDate(response.date);
                        setGenerating(false);
                      }
                    }
                  >
                    <span className="visually-hidden">Перегенерировать кэш</span>
                    <IconUpdate/>
                  </Button>
                  {
                    downloadable &&
                    <a href={`/books/${filename}`} className={classes.formatDownload}>
                      <IconDownload/>
                      <span className="visually-hidden">Скачать</span>
                    </a>
                  }
                </span>
              </td>
            </tr>
          }
          </tbody>
        </table>
      )
    }
  </>;
}

export default function BookFormats(
  props: {
    formats: string[],
    generatedFormats?: { [key: string]: BookFormat },
    onChange: BookFormatChangeCallback,
    submitting?: boolean
  }
) {
  const {formats, generatedFormats, onChange} = props;

  return <Skeleton visible={formats.length === 0}>
    <h2 className={classes.header}>Форматы</h2>
    <div className={classes.list}>
      {
        formats.map(
          format => <BookFormat
            key={`format-${format}`}
            format={generatedFormats?.[format]}
            type={format}
            onChange={onChange}
            submitting={props.submitting}
          />
        )
      }
    </div>
  </Skeleton>;
}
