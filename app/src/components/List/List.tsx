import {ReactNode} from "react";
import {ActionIcon, Group, TextInput, Title} from "@mantine/core";
import {IconPencil, IconTrash, IconPlus, IconSearch} from "@tabler/icons-react";
import {DataTable, TableState} from "@/components/DataTable";
import type {ColumnDef, EntityRow, ListProps} from "@admin/types";
import classes from "./List.module.css";
import Button from "@/components/primitives/Button";
import clsx from "clsx";
import Modal from "@/components/primitives/Modal";
import {IconError} from "@/components/List/components/Icons.tsx";
import {useListController} from "./useListController";

export function List<T extends EntityRow>(
  {
    name,
    permissions,
    dataProvider,
    columns,
    labels,
    webPath,
    apiEndpoint,
  }: ListProps<T>
) {
  const ctrl = useListController<T>({
    name,
    permissions,
    labels,
    webPath,
    dataProvider,
    apiEndpoint,
  });

  const renderActions = (row: T): ReactNode => {
    const actions = [];

    if (ctrl.permissions.edit) {
      actions.push(
        <ActionIcon
          component="a"
          onClick={(e: React.MouseEvent) => {
            e.preventDefault();
            ctrl.navigateToEdit(row);
          }}
          key={`row-${row.id}-edit`}
          variant="subtle"
          aria-label="Редактировать"
          className={clsx(classes.action, classes.actionEdit)}
        >
          <IconPencil size={16}/>
        </ActionIcon>,
      );
    }

    if (ctrl.permissions.delete) {
      actions.push(
        <ActionIcon
          key={`row-${row.id}-delete`}
          variant="subtle"
          color="red"
          aria-label="Удалить"
          className={clsx(classes.action, classes.actionDelete)}
          onClick={(e: React.MouseEvent) => {
            e.preventDefault();
            ctrl.confirmDeletion([row]);
          }}
        >
          <IconTrash size={16}/>
        </ActionIcon>,
      );
    }

    return actions.length ? <>{actions}</> : null;
  };

  const renderBulkActions = (): ReactNode => {
    return ctrl.permissions.delete
      ? <Button variant="default" color="red" onClick={(e: React.MouseEvent) => {
        e.preventDefault();
        ctrl.confirmBulkDeletion();
      }}>
        Удалить
      </Button>
      : null;
  };

  const errorContent = ctrl.error
    ? <span className={classes.error}>
        <IconError/>
        <span><strong>Упс!</strong> Что-то пошло не так.</span>
        <Group gap="sm" className={classes.errorButtons}>
          {ctrl.prevState && (
            <Button onClick={ctrl.rollbackState} variant="default">
              На шаг назад
            </Button>
          )}
          <Button onClick={() => ctrl.refetch()}>
            Попытаться еще раз
          </Button>
        </Group>
      </span>
    : null;

  return (
    <>
      <Modal
        opened={ctrl.isConfirmDeletion}
        onClose={ctrl.isDeleting ? () => {} : ctrl.closeDeletionConfirmation}
        withCloseButton={!ctrl.isDeleting}
        title="Действительно удалить?"
      >
        <p>{ctrl.deleteConfirmationText}</p>
        <Group justify="flex-end" className={classes.modalButtonsGroup}>
          <Button disabled={ctrl.isDeleting} onClick={ctrl.closeDeletionConfirmation} variant="default">
            Отмена
          </Button>
          <Button loading={ctrl.isDeleting} onClick={ctrl.executeDeletion}>
            Удалить
          </Button>
        </Group>
      </Modal>

      <Title className={classes.title} order={2}>{ctrl.labels.title}</Title>
      <Group justify="space-between" mb="lg">
        {ctrl.permissions.filter && (
          <TextInput
            placeholder={ctrl.labels.searchPlaceholder ?? "Поиск..."}
            value={ctrl.filterText}
            onChange={(e) => ctrl.handleFilterChange(e.target.value)}
            leftSection={<IconSearch size={16}/>}
            style={{maxWidth: 320}}
          />
        )}
        {ctrl.permissions.add && (
          <Button leftSection={<IconPlus size={16}/>} onClick={ctrl.navigateToAdd}>
            {ctrl.labels.add ?? "Добавить"}
          </Button>
        )}
      </Group>

      <DataTable<T>
        columns={columns}
        rows={ctrl.items}
        loading={ctrl.loading}
        total={ctrl.total}
        state={ctrl.state.table}
        error={ctrl.error}
        errorContent={errorContent}
        actions={(row) => renderActions(row)}
        selectable={ctrl.permissions.select}
        selectedRows={ctrl.selectedRows}
        onStateChange={(state: TableState) => ctrl.handleTableStateChange(state)}
        onSelectionChange={ctrl.setSelectedRows}
        bulkActions={renderBulkActions()}
        webPath={webPath}
      />
    </>
  );
}