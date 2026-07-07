import {useRef, useState} from "react";
import {ActionIcon, Group, NumberInput} from "@mantine/core";
import {IconLock, IconLockOpen} from "@tabler/icons-react";
import {FormErrors, UseFormReturnType} from "@mantine/form";
import {MediaFile} from "../types";

/**
 * Ширина и высота картинки с "замком" для сохранения пропорций.
 * Пропорция запоминается по первым загруженным значениям.
 */
export default function MediaDimensions(
  {form}: { form: UseFormReturnType<MediaFile, MediaFile, (values: MediaFile) => FormErrors> }
) {
  const [locked, setLocked] = useState(true);
  const ratio = useRef<number | null>(null);

  const width = Number(form.values.width) || 0;
  const height = Number(form.values.height) || 0;
  if (ratio.current === null && width > 0 && height > 0) {
    ratio.current = width / height;
  }

  const handleWidthChange = (value: number | string) => {
    form.setFieldValue("width", value as never);
    const w = Number(value) || 0;
    if (locked && ratio.current && w > 0) {
      form.setFieldValue("height", Math.round(w / ratio.current) as never);
    }
  };

  const handleHeightChange = (value: number | string) => {
    form.setFieldValue("height", value as never);
    const h = Number(value) || 0;
    if (locked && ratio.current && h > 0) {
      form.setFieldValue("width", Math.round(h * ratio.current) as never);
    }
  };

  return (
    <Group gap="xs" align="flex-end" wrap="nowrap">
      <NumberInput
        label="Ширина"
        min={0}
        value={form.values.width}
        onChange={handleWidthChange}
        error={form.errors.width}
        style={{flex: 1}}
      />
      <ActionIcon
        variant={locked ? "light" : "subtle"}
        color={locked ? "blue" : "gray"}
        size="lg"
        mb={2}
        title={locked ? "Пропорции сохраняются" : "Пропорции не сохраняются"}
        onClick={() => setLocked((v) => !v)}
      >
        {locked ? <IconLock size={18}/> : <IconLockOpen size={18}/>}
      </ActionIcon>
      <NumberInput
        label="Высота"
        min={0}
        value={form.values.height}
        onChange={handleHeightChange}
        error={form.errors.height}
        style={{flex: 1}}
      />
    </Group>
  );
}