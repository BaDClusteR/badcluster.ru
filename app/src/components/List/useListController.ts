import {ReactNode, useEffect, useState} from "react";
import {useQuery} from "@tanstack/react-query";
import {useDebouncedCallback} from "use-debounce";
import {useDisclosure} from "@mantine/hooks";
import {useNavigate} from "react-router";
import type {
  EntityRow,
  ListDataProvider,
  ListDataResponse,
  ListPermissions,
  ListState,
  Nullable,
  PartialListState,
  TableState,
} from "@admin/types";
import {useUrlListState} from "./useUrlListState";
import apiCall from "@/utils/apiCall";
import {buildAdminUrl} from "@/utils/buildAdminUrl";
import {notify} from "@/lib/notify";
import getDefaultDataProvider from "./defaultDataProvider";

export interface ListControllerLabels<T extends EntityRow> {
  title: ReactNode;
  searchPlaceholder?: string;
  deleteConfirmation?: {
    multiple: string;
    single: (row: T) => ReactNode;
  };
  add?: string;
}

export interface ListControllerOptions<T extends EntityRow> {
  name: string;
  permissions?: ListPermissions;
  labels: ListControllerLabels<T>;
  webPath?: string;
  dataProvider?: ListDataProvider<T>;
  apiEndpoint?: string;
}

export function useListController<T extends EntityRow>(options: ListControllerOptions<T>) {
  const {name, labels, webPath, apiEndpoint} = options;

  const permissions: ListPermissions = options.permissions ?? {
    add: true,
    edit: true,
    delete: true,
    select: true,
    filter: true,
  };

  let dataProvider = options.dataProvider;
  if (!dataProvider && apiEndpoint) {
    dataProvider = getDefaultDataProvider(apiEndpoint);
  }

  if (!dataProvider) {
    notify.error("Ошибка", "Не задан ни dataProvider, ни apiEndpoint.");
  }

  const listState = useUrlListState();
  const {state} = listState;
  const navigate = useNavigate();

  const [filterText, setFilterText] = useState(state.filter);
  const [tableData, setTableData] = useState<ListDataResponse<T>>({items: [], total: 0});
  const [hasLoaded, setHasLoaded] = useState(false);
  const [prevState, setPrevState] = useState<Nullable<ListState>>(null);
  const [selectedRows, setSelectedRows] = useState<boolean[]>([]);

  const [isConfirmDeletion, {close: closeDeletionConfirmation, open: openDeletionConfirmation}] = useDisclosure();
  const [isDeleting, setIsDeleting] = useState(false);
  const [deleteConfirmationText, setDeleteConfirmationText] = useState<ReactNode>("Действительно удалить?");
  const [rowsToDelete, setRowsToDelete] = useState<T[]>([]);

  const {data, error, isFetching, refetch} = useQuery({
    queryKey: [name, state],
    retry: false,
    queryFn: ({signal}) => dataProvider!.getData(state, {signal}),
    enabled: !!dataProvider,
  });

  useEffect(() => {
    if (!isFetching && data) {
      setTableData(data as ListDataResponse<T>);
      setHasLoaded(true);
    }
  }, [isFetching, data]);

  useEffect(() => {
    setFilterText(state.filter);
  }, [state.filter]);

  const handleSetState = (newState: PartialListState) => {
    setPrevState(state);
    setSelectedRows([]);
    listState.setState(newState);
  };

  const setFilterState = useDebouncedCallback(
    (filter: string) => handleSetState({filter}),
    300,
  );

  const rollbackState = () => {
    if (prevState) {
      listState.setState(prevState);
      setPrevState(null);
    }
  };

  const confirmDeletion = (rows: T | T[]) => {
    const rowsArray = Array.isArray(rows) ? rows : [rows];

    if (rowsArray.length > 1) {
      setDeleteConfirmationText(
        labels.deleteConfirmation?.multiple
          ? labels.deleteConfirmation.multiple.replace("{{count}}", rowsArray.length.toString())
          : "Действительно удалить?",
      );
    } else {
      setDeleteConfirmationText(
        labels.deleteConfirmation?.single
          ? labels.deleteConfirmation.single(rowsArray[0])
          : "Действительно удалить?",
      );
    }

    setRowsToDelete(rowsArray);
    openDeletionConfirmation();
  };

  const executeDeletion = async () => {
    if (!apiEndpoint) return;
    setIsDeleting(true);
    try {
      await apiCall("DELETE", apiEndpoint, {
        rows: rowsToDelete.map((r) => r.id),
      });
      setIsDeleting(false);
      closeDeletionConfirmation();
      setSelectedRows([]);
      await refetch();
    } catch {
      setIsDeleting(false);
    }
  };

  const confirmBulkDeletion = () => {
    const rows: T[] = [];
    selectedRows.forEach((selected, i) => {
      if (selected && tableData.items[i]) {
        rows.push(tableData.items[i]);
      }
    });
    if (rows.length > 0) confirmDeletion(rows);
  };

  const navigateToEdit = (row: EntityRow) => {
    if (webPath) navigate(buildAdminUrl(`${webPath}/${row.id}`));
  };

  const navigateToAdd = () => {
    if (webPath) navigate(buildAdminUrl(`${webPath}/new`));
  };

  const handleTableStateChange = (tableState: TableState) => {
    handleSetState({table: tableState});
  };

  const handleSortChange = (sortBy: string, sortDir: "asc" | "desc") => {
    handleSetState({
      table: {sortBy, sortDir, page: 1},
    });
  };

  const handleFilterChange = (value: string) => {
    setFilterText(value);
    setFilterState(value);
  };

  return {
    // State
    state,
    permissions,
    items: tableData.items,
    total: tableData.total,
    loading: isFetching || !hasLoaded,
    error: !!error,
    filterText,
    selectedRows,
    hasLoaded,
    prevState,

    // Deletion modal state
    isConfirmDeletion,
    isDeleting,
    deleteConfirmationText,
    closeDeletionConfirmation,

    // Actions
    handleSetState,
    handleTableStateChange,
    handleSortChange,
    handleFilterChange,
    setSelectedRows,
    confirmDeletion,
    confirmBulkDeletion,
    executeDeletion,
    rollbackState,
    refetch,
    navigateToEdit,
    navigateToAdd,

    // Labels
    labels,
    webPath,
  };
}