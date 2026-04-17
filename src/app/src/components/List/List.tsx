import {useEffect, useState} from "react";
import {ActionIcon, Group, TextInput, Title} from "@mantine/core";
import {IconPencil, IconTrash, IconPlus, IconSearch} from "@tabler/icons-react";
import {
  DataTable,
  TableState
} from "@/components/DataTable";
import { notify } from '@/lib/notify';
import {EntityRow, ListDataResponse, ListProps, ListState, PartialListState} from "@/components/List/types";
import {useUrlListState} from "./useUrlListState";
import {Nullable} from "@/types.ts";
import classes from "./List.module.css";
import {useQuery} from "@tanstack/react-query";
import {useDebouncedCallback} from "use-debounce";
import showApiError from "@/utils/showApiError";
import Button from "@/components/Button/Button.tsx";
import clsx from "clsx";

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
    getDeleteLink
  }: ListProps<T>
) {
  const listState = useUrlListState({defaults});
  const {state} = listState;
  const {getData} = dataProvider;

  const [filterText, setFilterText] = useState(state.filter);
  const [tableData, setTableData] = useState({rows: [], total: 0} as ListDataResponse<any>);
  const [prevState, setPrevState] = useState<Nullable<ListState>>(null);

  const handleSetState = (newState: PartialListState) => {
    setPrevState(state);
    listState.setState(newState);
  }

  const {data, error: err, isFetching, refetch} = useQuery({
    queryKey: [name, state],
    retry: false,
    queryFn: ({ signal }) => getData(state, {signal})
  });

  const error: any = err;

  useEffect(() => {
    if (error && !error.isHandled) {
      showApiError(error?.payload);
    }
  }, [error]);

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

  const errorContent = error
    ? <span>
        Ошибка!
        <button onClick={() => refetch()}>Попытаться еще раз</button>
        {prevState && <button onClick={() => rollbackState()}>На шаг назад</button> }
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
      actions.push(
        <ActionIcon
          component="a"
          href={getEditLink?.(row as any)}
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
          component="a"
          href={getDeleteLink?.(row as any)}
          variant="subtle"
          color="red"
          aria-label="Удалить"
          className={clsx(classes.action, classes.actionDelete)}
        >
          <IconTrash size={16} />
        </ActionIcon>
      )
    }

    return actions.length
      ? <>{actions}</>
      : null;
  }



  return (
    <>
      <Title className={classes.title} order={2}>{title}</Title>
      <Group justify="space-between" mb="lg">
        {permissions.filter && (
          <TextInput
            placeholder={searchPlaceHolder ?? "Search..."}
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
            onClick={() => notify.info('Not implemented', 'Create page coming soon')}
          >
            New page
          </Button>
        }
      </Group>

      <DataTable<T>
        columns={columns}
        rows={tableData.rows}
        loading={isFetching}
        total={tableData.total}
        state={state.table}
        rowKey={(row) => row.id}
        error={!!err}
        errorContent={errorContent}
        actions={(row) => renderActions(row)}
        onStateChange={(state: TableState) => {
          handleSetState({
            table: state
          });
        }}
      />
    </>
  );
}
