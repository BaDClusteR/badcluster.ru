import {Card, Image, Text, Group, ActionIcon} from "@mantine/core";
import {IconTrash, IconExternalLink} from "@tabler/icons-react";
import {MediaGrid, type SortOption, type MediaGridController} from "@/components/MediaGrid/MediaGrid";
import type {EntityRow, ListDataProvider} from "@admin/types";

interface MediaItem extends EntityRow {
  url: string;
  width: number;
  height: number;
  mime: string;
  uploadedAt: string;
}

// --- Mock data provider ---
const MOCK_ITEMS: MediaItem[] = Array.from({length: 23}, (_, i) => ({
  id: i + 1,
  url: `https://picsum.photos/seed/${i + 1}/400/300`,
  width: 1920,
  height: 1080,
  mime: "image/jpeg",
  uploadedAt: new Date(2026, 4, 20 - i).toISOString().slice(0, 10),
}));

const mockDataProvider: ListDataProvider<MediaItem> = {
  getData: async (state) => {
    // Simulate network delay
    await new Promise((r) => setTimeout(r, 400));

    let items = [...MOCK_ITEMS];

    // Filter
    if (state.filter) {
      const q = state.filter.toLowerCase();
      items = items.filter((m) => m.mime.includes(q) || String(m.id).includes(q));
    }

    // Sort
    if (state.table.sortBy) {
      const key = state.table.sortBy as keyof MediaItem;
      const dir = state.table.sortDir === "asc" ? 1 : -1;
      items.sort((a, b) => {
        if (a[key]! < b[key]!) return -dir;
        if (a[key]! > b[key]!) return dir;
        return 0;
      });
    }

    // Paginate
    const start = (state.table.page - 1) * state.table.perPage;
    const paged = items.slice(start, start + state.table.perPage);

    return {items: paged, total: items.length};
  },
};

// --- Sort options ---
const SORT_OPTIONS: SortOption[] = [
  {value: "uploadedAt:desc", label: "Сначала новые", dir: "desc"},
  {value: "uploadedAt:asc", label: "Сначала старые", dir: "asc"},
  {value: "id:desc", label: "ID ↓", dir: "desc"},
  {value: "id:asc", label: "ID ↑", dir: "asc"},
];

// --- Card renderer ---
function MediaCard({item, ctrl}: { item: MediaItem; ctrl: MediaGridController<MediaItem> }) {
  return (
    <Card shadow="xs" padding="xs" radius="md" withBorder>
      <Card.Section
        style={{cursor: "pointer"}}
        onClick={() => ctrl.navigateToEdit(item)}
      >
        <Image src={item.url} height={140} alt={`#${item.id}`}/>
      </Card.Section>

      <Group justify="space-between" mt="xs">
        <div>
          <Text size="sm" fw={500}>#{item.id}</Text>
          <Text size="xs" c="dimmed">{item.width}×{item.height} · {item.uploadedAt}</Text>
        </div>
        <Group gap={4}>
          <ActionIcon
            variant="subtle"
            size="sm"
            component="a"
            href={item.url}
            target="_blank"
          >
            <IconExternalLink size={14}/>
          </ActionIcon>
          {ctrl.permissions.delete && (
            <ActionIcon
              variant="subtle"
              color="red"
              size="sm"
              onClick={() => ctrl.confirmDeletion([item])}
            >
              <IconTrash size={14}/>
            </ActionIcon>
          )}
        </Group>
      </Group>
    </Card>
  );
}

// --- Page ---
export default function MediaLibrary() {
  return (
    <MediaGrid<MediaItem>
      name="media"
      dataProvider={mockDataProvider}
      webPath="media"
      sortOptions={SORT_OPTIONS}
      defaultSort="uploadedAt:desc"
      cols={{base: 2, sm: 3, md: 4, lg: 5}}
      labels={{
        title: "Медиатека",
        searchPlaceholder: "Поиск по ID или типу...",
        add: "Загрузить",
        deleteConfirmation: {
          multiple: "Удалить {{count}} файлов?",
          single: (row) => `Удалить файл #${row.id}?`,
        },
      }}
      renderCard={(item, ctrl) => <MediaCard key={item.id} item={item} ctrl={ctrl}/>}
    />
  );
}