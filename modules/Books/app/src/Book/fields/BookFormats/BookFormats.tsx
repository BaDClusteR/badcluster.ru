import {type BookFormat} from "../../types";
import {Button, Skeleton, Switch, Textarea, TextInput} from "@mantine/core";
import {useEffect, useState} from "react";
import {Optional} from "@admin/types";
import classes from "./BookFormats.module.css";

export type BookFormatChangeCallback = (format: string, allowed: boolean, filename: string, postfix: string) => void;

function BookFormat(props: { format: Optional<BookFormat>, type?: string, onChange: BookFormatChangeCallback }) {
  const {format, type} = props;

  const [isAllowed, setAllowed] = useState<boolean>(format?.allowed ?? false);

  // Sync when data arrives from backend (format prop updates after fetch)
  useEffect(() => {
    setAllowed(format?.allowed ?? false);
  }, [format?.allowed]);

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
      isAllowed && (format?.size ?? 0) > 0 && (
        <table>
          <tbody>
          <tr>
            <th>Закэшированный дамп:</th>
            <td>{format?.sizeHumanReadable}</td>
          </tr>
          <tr>
            <th>Дата генерации:</th>
            <td>{format?.dateGenerated}</td>
          </tr>
          <tr>
            <th>&nbsp;</th>
            <td>
              <Button>Очистить кэш</Button>
              <Button>Перегенерировать</Button>
            </td>
          </tr>
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
    onChange: BookFormatChangeCallback
  }
) {
  const {formats, generatedFormats, onChange} = props;

  return <Skeleton visible={formats.length === 0}>
    <h2 className={classes.header}>Форматы</h2>
    <div className={classes.list}>
      {
        formats.map(
          format => <BookFormat
            format={generatedFormats?.[format]}
            type={format}
            onChange={onChange}
          />
        )
      }
    </div>
  </Skeleton>;
}
