import type {FieldDef} from "@admin/types";
import {Book, BookContext} from "../types";
import BookFormats from "./BookFormats";

const FIELDS: FieldDef<Book, BookContext>[] = [
  {
    name: "title",
    label: "Название",
    type: "text",
    role: "primary",
    span: "full",
    required: true
  },
  {
    name: "cover",
    label: "Обложка",
    hint: "Только JPG, шириной желательно не меньше 1000 пикселей",
    type: "image",
    thumbnailWidth: 130,
    span: "full",
    role: "primary",
    uploadPurpose: "book_cover"
  },
  {
    name: "type",
    label: "Тип",
    type: "select",
    role: "primary",
    span: "full",
    required: true,
    options: [
      {value: "A", label: "Авторское"},
      {value: "T", label: "Перевод"}
    ]
  },
  {
    name: "author",
    label: "Автор(ы)",
    type: "text",
    hint: "Если авторов больше одного, перечисляются через запятую.",
    visible: values => values.type === "T",
    span: "full",
    role: "primary"
  },
  {
    name: "annotation",
    label: "Аннотация",
    type: "textarea",
    span: "full",
    role: "primary",
    required: true
  },
  {
    type: "spacer",
    role: "primary",
    span: "full"
  },
  {
    name: "slug",
    label: "Слаг",
    type: "slug",
    required: true,
    role: "primary",
    url: () => ""
  },
  {
    name: "lastUpdateDate",
    type: "datetime",
    label: "Последнее обновление",
    required: true,
    role: "primary"
  },
  {
    name: "shortAnnotation",
    type: "textarea",
    label: "Краткая аннотация",
    hint: "Текст для страницы со списком произведений",
    required: true,
    span: "full",
    role: "primary"
  },
  {
    name: "coverBg",
    type: "image",
    label: "Картинка на заднем фоне",
    hint: "Придает цвет заблюренному фону книги, если у нее нет обложки. 40 пикселей в ширину.",
    span: "full",
    role: "primary",
    uploadPurpose: "book_cover_bg"
  },
  {
    name: "group",
    type: "select",
    label: "Группа",
    span: "full",
    role: "primary",
    options: [
      {value: "", label: "[Без группы]"},
      {value: "Серия «Doom» про Флая Таггарта", label: "Серия «Doom» про Флая Таггарта"},
      {value: "Дилогия «Doom» Мэтью Костелло", label: "Дилогия «Doom» Мэтью Костелло"},
      {value: "Крипипаста", label: "Крипипаста"}
    ]
  },
  {
    name: "position",
    type: "number",
    label: "Позиция",
    hint: "Произведения сортируются по этому полю внутри группы, на сортировку самих групп не влияет.",
    span: "full",
    role: "primary"
  },
  {
    name: "fb2Genre",
    type: "text",
    span: "full",
    role: "primary",
    label: "Жанр (для FB2)",
    hint: <a
      href="http://www.fictionbook.org/index.php/Eng:FictionBook_2.1_genres"
      target="_blank"
    >
      Список всех жанров на fictionbook.org
    </a>
  },
  {
    type: "group",
    span: "full",
    render: (form, options, values) =>
      <BookFormats formats={options.context?.formats ?? []} generatedFormats={values?.formats} onChange={
        (format: string, allowed: boolean, filename: string, postfix: string) => {
          const current = form.values.formats ?? {};
          form.setFieldValue(
            "formats",
            {
              ...current,
              [format]: {
                allowed,
                filename,
                postfix
              }
            } as never
          );
        }
      }/>
  }
];

export default FIELDS;
