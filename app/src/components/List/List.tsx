import {ReactNode, useEffect, useState} from "react";
import {ActionIcon, Group, TextInput, Title} from "@mantine/core";
import {IconPencil, IconTrash, IconPlus, IconSearch} from "@tabler/icons-react";
import {
  DataTable,
  TableState
} from "@/components/DataTable";
import {EntityRow, ListDataResponse, ListProps, ListState, PartialListState} from "@/components/List/types";
import {useUrlListState} from "./useUrlListState";
import {Nullable} from "@/types.ts";
import classes from "./List.module.css";
import {useQuery} from "@tanstack/react-query";
import {useDebouncedCallback} from "use-debounce";
import Button from "@/components/primitives/Button";
import clsx from "clsx";
import {useDisclosure} from "@mantine/hooks";
import Modal from "@/components/primitives/Modal";
import {IconError} from "@/components/List/components/Icons.tsx";
import {useNavigate} from "react-router";

export function List<T extends EntityRow>(
  {
    name,
    permissions,
    defaults,
    dataProvider,
    columns,
    title,
    searchPlaceHolder,
    getEditLink,
    onAdd,
    onDelete,
    addButtonTitle,
    getDeleteConfirmationTitle,
    getDeleteConfirmationText
  }: ListProps<T>
) {
  const listState = useUrlListState({defaults});
  const {state} = listState;
  const {getData} = dataProvider;
  const navigate = useNavigate();

  const [filterText, setFilterText] = useState(state.filter);
  const [tableData, setTableData] = useState({rows: [], total: 0} as ListDataResponse<any>);
  const [prevState, setPrevState] = useState<Nullable<ListState>>(null);
  const [selectedRows, setSelectedRows] = useState<boolean[]>([]);

  const [isConfirmDeletion, { close: closeDeletionConfirmation, open: openDeletionConfirmation }] = useDisclosure();
  const [isDeleting, setIsDeleting] = useState(false);

  const [deleteConfirmationTitle, setDeleteConfirmationTitle] = useState<ReactNode>("Подтверждение");
  const [deleteConfirmationText, setDeleteConfirmationText] = useState<ReactNode>("Действительно удалить?");
  const [rowsToDelete, setRowsToDelete] = useState<EntityRow[]>([]);

  const handleSetState = (newState: PartialListState) => {
    setPrevState(state);
    setSelectedRows([]);
    listState.setState(newState);
  }

  const {data, error: err, isFetching, refetch} = useQuery({
    queryKey: [name, state],
    retry: false,
    queryFn: ({ signal }) => getData(state, {signal})
  });

  const error: any = err;

  useEffect(() => {
    if (!isFetching && data) {
      setTableData(data);
    }
  }, [isFetching, data]);

  useEffect(() => {
    setFilterText(state.filter);
  }, [state.filter]);

  const rollbackState = () => {
    if (prevState) {
      listState.setState(prevState);
      setPrevState(null);
    }
  }

  const renderBulkActions = (): ReactNode => {
    return permissions.delete
      ? <Button variant="default" color="red" onClick={
        (e) => {
          e.preventDefault();
          const rowsToDelete: T|T[] = [];
          selectedRows?.forEach((selected, rowIndex) => {
            if (selected && data?.rows[rowIndex]) {
              rowsToDelete.push(data?.rows[rowIndex]);
            }
          });

          confirmDeletion(rowsToDelete);
        }
      }>
        Удалить
      </Button>
      : null;
  }

  const confirmDeletion = (rows: T|T[]) => {
    setDeleteConfirmationTitle(
      getDeleteConfirmationTitle?.(rows as any[]) ?? "Действительно удалить?"
    );
    setDeleteConfirmationText(
      getDeleteConfirmationText?.(rows as any[]) ?? "Подтверждение"
    );
    setRowsToDelete(rows as EntityRow[]);
    openDeletionConfirmation();
  }

  const errorContent = error
    ? <span className={classes.error}>
        <IconError />
        <span><strong>Упс!</strong> Что-то пошло не так.</span>
        <Group gap="sm" className={classes.errorButtons}>
          {
            prevState
            && <Button onClick={rollbackState} variant="default">
              На шаг назад
            </Button>
          }
          <Button onClick={() => refetch()}>
            Попытаться еще раз
          </Button>
        </Group>
      </span>
    : null;

    const setFilterState = useDebouncedCallback(
      (filter: string) => {
        handleSetState({filter});
      },
      300
    );

  const renderActions = (row: EntityRow) => {
    const actions = [];
    if (permissions.edit) {
      const url = String(getEditLink?.(row as any) || '');

      actions.push(
        <ActionIcon
          component="a"
          onClick={(e) => {
            e.preventDefault();
            navigate(url);
          }}
          key={`row-${row.id}-edit`}
          href={url}
          variant="subtle"
          aria-label="Редактировать"
          className={clsx(classes.action, classes.actionEdit)}
        >
          <IconPencil size={16} />
        </ActionIcon>
      )
    }

    if (permissions.delete) {
      actions.push(
        <ActionIcon
          key={`row-${row.id}-delete`}
          variant="subtle"
          color="red"
          aria-label="Удалить"
          className={clsx(classes.action, classes.actionDelete)}
          onClick={(e) => {
            e.preventDefault();
            confirmDeletion([row as any]);
          }}
        >
          <IconTrash size={16} />
        </ActionIcon>
      )
    }

    return actions.length
      ? <>{actions}</>
      : null;
  }

  const renderDeleteConfirmationModal = () => {
    return <Modal
      opened={isConfirmDeletion}
      onClose={isDeleting ? () => {} : closeDeletionConfirmation}
      withCloseButton={!isDeleting}
      title={deleteConfirmationTitle}
    >
      <p>{deleteConfirmationText}</p>
      <Group justify="flex-end" className={classes.modalButtonsGroup}>
        <Button disabled={isDeleting} onClick={closeDeletionConfirmation} variant="default">
          Отмена
        </Button>
        <Button loading={isDeleting} onClick={async () => {
          if (onDelete) {
            setIsDeleting(true);

            try {
              await onDelete(rowsToDelete as any[]);
              setIsDeleting(false);
              closeDeletionConfirmation();
              setSelectedRows([]);
              await refetch();
            } catch (error) {
              setIsDeleting(false);
            }
          }
        }}>
          Удалить
        </Button>
      </Group>
    </Modal>;
  }

  return (
    <>
      {renderDeleteConfirmationModal()}
      <Title className={classes.title} order={2}>{title}</Title>
      <Group justify="space-between" mb="lg">
        {permissions.filter && (
          <TextInput
            placeholder={searchPlaceHolder ?? "Поиск..."}
            value={filterText}
            onChange={(e) => {
              const value = String(e?.target?.value || '');
              setFilterText(value);
              setFilterState(value);
            }}
            leftSection={<IconSearch size={16} />}
            style={{ maxWidth: 320 }}
          />
        )}
        {
          permissions.add && <Button
            leftSection={<IconPlus size={16} />}
            onClick={() => onAdd?.()}
          >
            {addButtonTitle ?? "Добавить"}
          </Button>
        }
      </Group>

      <DataTable<T>
        columns={columns}
        rows={tableData.rows}
        loading={isFetching}
        total={tableData.total}
        state={state.table}
        error={!!err}
        errorContent={errorContent}
        actions={(row) => renderActions(row)}
        selectable={permissions.select}
        selectedRows={selectedRows}
        onStateChange={(state: TableState) => {
          handleSetState({
            table: state
          });
        }}
        onSelectionChange={(rows) => {
          setSelectedRows(rows);
        }}
        bulkActions={renderBulkActions()}
      />
    </>
  );
}
