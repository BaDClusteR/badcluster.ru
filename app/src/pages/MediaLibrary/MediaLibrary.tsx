import {MediaGrid, type SortOption} from "@/components/MediaGrid/MediaGrid";
import getDefaultDataProvider from "@/components/List/defaultDataProvider.ts";
import {MediaItem} from "./types";

const dataProvider = getDefaultDataProvider<MediaItem>("media");

// --- Sort options ---
const SORT_OPTIONS: SortOption[] = [
  {value: "id:desc", label: "Сначала новые", dir: "desc"},
  {value: "id:asc", label: "Сначала старые", dir: "asc"}
];

// --- Page ---
export default function MediaLibrary() {
  return (
    <MediaGrid<MediaItem>
      name="media"
      dataProvider={dataProvider}
      apiEndpoint="media"
      webPath="media"
      sortOptions={SORT_OPTIONS}
      defaultSort="id:desc"
      cols={{base: 2, sm: 3, md: 4, lg: 5}}
      // Загрузка файлов в медиатеку идет из редакторов контента,
      // поэтому кнопки "Добавить" здесь нет
      permissions={{add: false, edit: true, delete: true, select: true, filter: true}}
      labels={{
        title: "Медиатека",
        searchPlaceholder: "Поиск по имени файла, описанию или типу...",
        deleteConfirmation: {
          multiple: "Удалить {{count}} файлов?",
          single: (row) => `Удалить файл #${row.id}?`
        }
      }}
    />
  );
}