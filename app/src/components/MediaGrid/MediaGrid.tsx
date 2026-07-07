import {Group, Pagination, Select, SimpleGrid, Skeleton, TextInput, Title} from "@mantine/core";
import {IconSearch, IconPlus, IconTrash} from "@tabler/icons-react";
import clsx from "clsx";
import buttonClasses from "@/components/primitives/Button.module.css";
import type {EntityRow, ListPermissions, SortDirection} from "@admin/types";
import {useListController, type ListControllerLabels} from "@/components/List/useListController";
import type {ListDataProvider} from "@admin/types";
import Button from "@/components/primitives/Button";
import Modal from "@/components/primitives/Modal";
import classes from "./MediaGrid.module.css";
import {type ComponentType} from "react";
import {MediaCard, type MediaItem} from "./MediaCard";

const PER_PAGE_OPTIONS = [10, 25, 50, 100];

export interface SortOption {
  value: string;
  label: string;
  dir: SortDirection;
}

export interface MediaGridProps<T extends MediaItem> {
  name: string;
  labels: ListControllerLabels<T>;
  permissions?: ListPermissions;
  webPath?: string;
  dataProvider?: ListDataProvider<T>;
  apiEndpoint?: string;
  /** Available sort options for the dropdown. */
  sortOptions?: SortOption[];
  /** Default sort option value. */
  defaultSort?: string;
  /** Number of columns at different breakpoints. */
  cols?: { base?: number; sm?: number; md?: number; lg?: number };
  /** Card component for a single grid item. Defaults to MediaCard. */
  card?: ComponentType<{ item: T; ctrl: MediaGridController<T> }>;
}

export interface MediaGridController<T extends EntityRow> {
  navigateToEdit: (row: EntityRow) => void;
  confirmDeletion: (rows: T | T[]) => void;
  permissions: ListPermissions;
}

export function MediaGrid<T extends MediaItem>(
  {
    name,
    labels,
    permissions,
    webPath,
    dataProvider,
    apiEndpoint,
    sortOptions,
    defaultSort,
    cols = {base: 2, sm: 3, md: 4, lg: 5},
    card: CardComponent = MediaCard,
  }: MediaGridProps<T>
) {
  const ctrl = useListController<T>({
    name,
    permissions,
    labels,
    webPath,
    dataProvider,
    apiEndpoint,
  });

  const currentSort = sortOptions?.find(
    (o) => o.value === `${ctrl.state.table.sortBy}:${ctrl.state.table.sortDir}`
  )?.value ?? defaultSort ?? null;

  const handleSortChange = (value: string | null) => {
    if (!value) return;
    const opt = sortOptions?.find((o) => o.value === value);
    if (opt) {
      const [sortBy] = value.split(":");
      ctrl.handleSortChange(sortBy, opt.dir);
    }
  };

  const gridCtrl: MediaGridController<T> = {
    navigateToEdit: ctrl.navigateToEdit,
    confirmDeletion: ctrl.confirmDeletion,
    permissions: ctrl.permissions,
  };

  const {page, perPage} = ctrl.state.table;
  const totalPages = Math.max(1, Math.ceil(ctrl.total / perPage));
  const from = ctrl.total === 0 ? 0 : (page - 1) * perPage + 1;
  const to = Math.min(ctrl.total, page * perPage);

  const renderSkeletons = () => {
    return Array.from({length: 10}).map((_, i) => (
      <div key={i} className={classes.card}>
        <Skeleton height={140} radius="md"/>
        <Skeleton height={14} mt={8} width="70%"/>
        <Skeleton height={12} mt={4} width="40%"/>
      </div>
    ));
  };

  return (
    <>
      <Modal
        opened={ctrl.isConfirmDeletion}
        onClose={ctrl.isDeleting ? () => {} : ctrl.closeDeletionConfirmation}
        withCloseButton={!ctrl.isDeleting}
        title="Действительно удалить?"
      >
        <p>{ctrl.deleteConfirmationText}</p>
        <Group justify="flex-end" mt="md">
          <Button disabled={ctrl.isDeleting} onClick={ctrl.closeDeletionConfirmation} variant="default">
            Отмена
          </Button>
          <Button loading={ctrl.isDeleting} onClick={ctrl.executeDeletion}>
            Удалить
          </Button>
        </Group>
      </Modal>

      <Title order={2} mb="lg">{ctrl.labels.title}</Title>

      <Group justify="space-between" mb="lg">
        <Group>
          {ctrl.permissions.filter && (
            <TextInput
              placeholder={ctrl.labels.searchPlaceholder ?? "Поиск..."}
              value={ctrl.filterText}
              onChange={(e) => ctrl.handleFilterChange(e.target.value)}
              leftSection={<IconSearch size={16}/>}
              style={{maxWidth: 280}}
            />
          )}
          {sortOptions && sortOptions.length > 0 && (
            <Select
              data={sortOptions.map((o) => ({value: o.value, label: o.label}))}
              value={currentSort}
              onChange={handleSortChange}
              placeholder="Сортировка"
              style={{maxWidth: 200}}
              size="sm"
            />
          )}
        </Group>

        <Group>
          {ctrl.selectedRows.some(Boolean) && ctrl.permissions.delete && (
            <Button
              variant="default"
              color="red"
              leftSection={<IconTrash size={16}/>}
              onClick={ctrl.confirmBulkDeletion}
            >
              Удалить
            </Button>
          )}
          {ctrl.permissions.add && (
            <Button leftSection={<IconPlus size={16}/>} onClick={ctrl.navigateToAdd}>
              {ctrl.labels.add ?? "Добавить"}
            </Button>
          )}
        </Group>
      </Group>

      <SimpleGrid cols={cols} spacing="md">
        {ctrl.loading
          ? renderSkeletons()
          : ctrl.items.map((item) => <CardComponent key={item.id} item={item} ctrl={gridCtrl}/>)
        }
      </SimpleGrid>

      {!ctrl.loading && ctrl.items.length === 0 && (
        <div className={classes.empty}>Ничего не найдено</div>
      )}

      <div className={classes.footer}>
        <Group gap="sm">
          <span className={classes.footerInfo}>
            <Skeleton visible={ctrl.loading}>
              {from}–{to} of {ctrl.total}
            </Skeleton>
          </span>
          <Select
            size="xs"
            value={String(perPage)}
            onChange={(v) => v && ctrl.handleTableStateChange({...ctrl.state.table, perPage: Number(v), page: 1})}
            data={PER_PAGE_OPTIONS.map((n) => ({value: String(n), label: `${n} / page`}))}
            w={110}
            allowDeselect={false}
            disabled={ctrl.loading || ctrl.error}
          />
        </Group>
        {!ctrl.error && (
          <Skeleton visible={ctrl.loading} className={classes.skeletonPagination}>
            <Pagination.Root
              classNames={{
                control: clsx(buttonClasses.button, classes.paginationButton)
              }}
              value={page}
              onChange={(newPage) => ctrl.handleTableStateChange({...ctrl.state.table, page: newPage})}
              total={totalPages}
              siblings={1}
              size="sm"
            >
              <Group gap={5} justify="center">
                <Pagination.First className={classes.paginationButton}/>
                <Pagination.Previous className={classes.paginationButton}/>
                <Pagination.Items/>
                <Pagination.Next className={classes.paginationButton}/>
                <Pagination.Last className={classes.paginationButton}/>
              </Group>
            </Pagination.Root>
          </Skeleton>
        )}
      </div>
    </>
  );
}