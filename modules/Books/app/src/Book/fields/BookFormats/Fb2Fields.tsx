import {SimpleGrid, Textarea, TextInput} from "@mantine/core";
import type {FieldDefGroup} from "@admin/types";
import type {Book, BookContext} from "../../types";

type BookForm = Parameters<NonNullable<FieldDefGroup<Book, BookContext>["render"]>>[0];

/**
 * Метаданные книги для FB2. Живут внутри блока формата, а не в FIELDS,
 * поэтому валидация версии — только на бэкенде (ошибка вернется в form.errors).
 */
export default function Fb2Fields({form}: { form: BookForm }) {
  return <>
    <SimpleGrid cols={{base: 1, md: 2}}>
      <TextInput
        label="Жанр:"
        description={
          <a
            href="http://www.fictionbook.org/index.php/Eng:FictionBook_2.1_genres"
            target="_blank"
          >
            Список всех жанров на fictionbook.org
          </a>
        }
        value={form.values.fb2Genre ?? ""}
        onChange={(e) => form.setFieldValue("fb2Genre", e.currentTarget.value as never)}
        error={form.errors.fb2Genre}
      />
      <TextInput
        label="Версия:"
        description="Например 1.0"
        withAsterisk
        value={form.values.fb2Version ?? ""}
        onChange={(e) => form.setFieldValue("fb2Version", e.currentTarget.value as never)}
        error={form.errors.fb2Version}
      />
    </SimpleGrid>
    <Textarea
      label="История изменений:"
      description={
        "Каждая строка — отдельная запись (абзац в <history>). В FB2-файлах принято писать "
        + "по порядку версий: сверху первая, ниже — более поздние."
      }
      placeholder={"1.0 — создание файла\n1.1 — исправлены опечатки"}
      autosize
      minRows={3}
      maxRows={12}
      value={form.values.fb2History ?? ""}
      onChange={(e) => form.setFieldValue("fb2History", e.currentTarget.value as never)}
      error={form.errors.fb2History}
    />
    <TextInput
      label="ID:"
      description="Генерируется автоматически и больше не меняется, чтобы читалки узнавали книгу при обновлении"
      readOnly
      value={form.values.fb2Id ?? ""}
    />
  </>;
}
