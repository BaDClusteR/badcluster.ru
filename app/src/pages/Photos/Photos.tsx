import {Text} from "@mantine/core";
import {MediaGrid, type SortOption, type MediaGridController} from "@/components/MediaGrid/MediaGrid";
import {MediaCard} from "@/components/MediaGrid/MediaCard";
import getDefaultDataProvider from "@/components/List/defaultDataProvider.ts";
import {PhotoRow} from "./types";

const dataProvider = getDefaultDataProvider<PhotoRow>("photos");

// --- Sort options ---
const SORT_OPTIONS: SortOption[] = [
  {value: "uploadedAt:desc", label: "Сначала новые", dir: "desc"},
  {value: "uploadedAt:asc", label: "Сначала старые", dir: "asc"},
  {value: "position:desc", label: "По порядку", dir: "desc"},
  {value: "position:asc", label: "Обратный порядок", dir: "asc"}
];

// --- Card renderer ---
function PhotoCard({item, ctrl}: { item: PhotoRow; ctrl: MediaGridController<PhotoRow> }) {
  return (
    <MediaCard
      item={item}
      ctrl={ctrl}
      info={
        <>
          <Text size="sm" fw={500}>{item.filename}</Text>
          <Text size="xs" c="dimmed">{item.width}×{item.height} · {item.uploadedAt}</Text>
          {item.alt && <Text size="xs" lineClamp={1}>{item.alt}</Text>}
        </>
      }
    />
  );
}

// --- Page ---
export default function Photos() {
  return (
    <MediaGrid<PhotoRow>
      name="photos"
      dataProvider={dataProvider}
      apiEndpoint="photos"
      webPath="photos"
      sortOptions={SORT_OPTIONS}
      defaultSort="uploadedAt:desc"
      cols={{base: 2, sm: 3, md: 4, lg: 5}}
      labels={{
        title: "Фотки",
        searchPlaceholder: "Поиск по имени файла или описанию...",
        add: "Загрузить",
        deleteConfirmation: {
          multiple: "Удалить {{count}} файлов?",
          single: (row) => `Удалить фотку #${row.id}?`
        }
      }}
      card={PhotoCard}
    />
  );
}
