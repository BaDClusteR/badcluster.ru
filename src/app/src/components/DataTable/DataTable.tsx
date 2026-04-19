import React from 'react';
import { Link } from 'react-router';
import {
  Group,
  Pagination,
  Select,
  Skeleton
} from "@mantine/core";
import Checkbox from "@/components/primitives/Checkbox";
import { IconChevronUp, IconChevronDown, IconSelector} from '@tabler/icons-react';
import type {ColumnDef, DataTableProps, TableSort, TableState} from "./types";
import classes from './DataTable.module.css';
import deepMerge from "@/utils/deepMerge";
import clsx from "clsx";
import {EntityRow} from "@/components/List/types.ts";
import buttonClasses from "../primitives/Button.module.css";

const DEFAULT_PER_PAGE_OPTIONS = [10, 25, 50, 100];

function TableSkeleton() {
  return <Skeleton height={10} mt={6} mb={6} radius="xl" width="70%" />;
}

export function DataTable<T extends EntityRow>({
  columns,
  rows,
  total,
  state,
  actions,
  loading = false,
  perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
  emptyMessage = 'No data',
  onStateChange,
  error,
  errorContent,
  selectable,
  selectedRows,
  onSelectionChange,
  bulkActions
}: DataTableProps<T>
) {
  const totalPages = Math.max(1, Math.ceil(total / state.perPage));
  const from = total === 0 ? 0 : (state.page - 1) * state.perPage + 1;
  const to = Math.min(total, state.page * state.perPage);
  let isSelectedAll = rows.length > 0;
  rows.forEach((_row, i) => {
    if (isSelectedAll && !selectedRows?.[i]) {
      isSelectedAll = false;
    }
  });

  if (!rows.length && loading) {
    // @ts-expect-error
    rows = [[], [], [], [], []];
  }

  function handleStateChange(newState: Partial<TableState>) {
    if (!onStateChange) {
      return;
    }

    onStateChange(
      deepMerge(
        state,
        newState
      ) as TableState
    );
  }

  function handleSort(col: ColumnDef<T>) {
    if (!col.sortable) {
      return;
    }

    const newSort: TableSort = {
      sortBy: null,
      sortDir: 'asc'
    };

    if (state.sortBy !== col.key) {
      newSort.sortBy = col.key;
    } else if (state.sortDir === 'asc') {
      newSort.sortBy = state.sortBy;
      newSort.sortDir = 'desc';
    }

    handleStateChange({
      sortBy: newSort.sortBy,
      sortDir: newSort.sortDir,
    })
  }

  function renderCell(col: ColumnDef<T>, row: T) {
    if (loading) {
      return <TableSkeleton />;
    }

    let content = col.render
      ? col.render(row)
      : col.accessor
      ? col.accessor(row)
      : (row as Record<string, unknown>)[col.key] as React.ReactNode;

    let subContent = col.subRender
      ? col.subRender(row)
      : col.subKey
      ? (row as Record<string, unknown>)[col.subKey] as React.ReactNode
      : null;

    if (subContent) {
      subContent = <span className={classes.subContent}>
        {subContent}
      </span>
    }

    if (col.link) {
      content = (
        <Link to={col.link(row)} className={classes.cellLink}>
          {content}
        </Link>
      );
    }

    return <>
      {content}
      {subContent}
    </>;
  }

  const getFullWidthColSpan = () => {
    let result = columns.length;
    if (actions) {
      result++;
    }
    if (selectable) {
      result++;
    }

    return result;
  }

  return (
    <>
      <div className={clsx(
        classes.wrapper,
        loading && classes.loading,
        error && classes.error
      )}>
        <div className={classes.tableCard}>
          <table className={classes.table}>
            <thead>
            <tr>
              {
                selectable &&
                <th key="select-th">
                  <Checkbox
                    checked={isSelectedAll}
                    onChange={
                      (value) => {
                        if (onSelectionChange) {
                          const newSelectedRows: boolean[] = [];
                          rows.forEach((_row, i) => {
                            newSelectedRows[i] = value;
                          });

                          onSelectionChange(newSelectedRows);
                        }
                      }
                    }
                  />
                </th>
              }
              {columns.map((col) => {
                const isSorted = state.sortBy === col.key;
                return (
                  <th
                    key={col.key}
                    style={{
                      width: col.width,
                      textAlign: col.align ?? 'left',
                    }}
                    className={
                      clsx(
                        col.sortable && classes.sortable,
                        isSorted && classes.sortableActive
                      )
                    }
                    onClick={() => handleSort(col)}
                  >
                    {col.header}
                    {col.sortable && (
                      <span
                        className={`${classes.sortIcon} ${isSorted ? classes.sortActive : ''}`}
                      >
                        {!isSorted && <IconSelector size={14} />}
                        {isSorted && state.sortDir === 'asc' && <IconChevronUp size={14} />}
                        {isSorted && state.sortDir === 'desc' && <IconChevronDown size={14} />}
                      </span>
                    )}
                  </th>
                );
              })}
              {actions && <th className={classes.actionsCell}> </th>}
            </tr>
            </thead>
            <tbody>
            {
              error &&
              <tr>
                <td
                  colSpan={getFullWidthColSpan()}
                  className={classes.errorContainer}
                >
                  {errorContent}
                </td>
              </tr>
            }
            {!error && rows.length === 0 && !loading && (
              <tr>
                <td
                  colSpan={getFullWidthColSpan()}
                  className={classes.empty}
                >
                  {emptyMessage}
                </td>
              </tr>
            )}
            {
              !error &&
              rows.map(
                (row, rowIndex) => (
                  <tr
                    key={`${row.id}-${rowIndex}-${!!selectedRows?.[rowIndex]}`}
                    className={clsx(!!selectedRows?.[rowIndex] && classes.selectedRow)}
                  >
                    {
                      selectable &&
                      <td key={`${rowIndex}-select`}>
                        <Checkbox
                          checked={!!selectedRows?.[rowIndex]}
                          onChange={
                            (value) => {
                              if (onSelectionChange) {
                                let newSelectedRows = Array.from(selectedRows ?? []);
                                newSelectedRows[rowIndex] = value;

                                onSelectionChange(newSelectedRows);
                              }
                            }
                          }
                        />
                      </td>
                    }
                    {columns.map((col, colIndex) => (
                      <td key={col.key ?? `${rowIndex}-${colIndex}`} style={{ textAlign: col.align ?? 'left' }}>
                        {renderCell(col, row)}
                      </td>
                    ))}
                    {actions && (
                      <td className={classes.actionsCell}>
                        <Group gap="xs" justify="flex-end" wrap="nowrap">
                          {loading ? <TableSkeleton /> : actions(row)}
                        </Group>
                      </td>
                    )}
                  </tr>
                )
              )
            }
            </tbody>
          </table>
        </div>

        <div className={classes.footer}>
          <Group gap="sm">
            <span className={classes.footerInfo}>
              <Skeleton visible={loading}>
                {from}–{to} of {total}
              </Skeleton>
            </span>
            <Select
              size="xs"
              value={String(state.perPage)}
              onChange={(v) => v && handleStateChange({ perPage: Number(v), page: 1 })}
              data={perPageOptions.map((n) => ({ value: String(n), label: `${n} / page` }))}
              w={110}
              allowDeselect={false}
              disabled={loading || error}
            />
          </Group>
          {
            selectable
            && ((selectedRows ?? []).filter(Boolean).length > 0)
            && bulkActions
            && <Group key={`panel-${selectable}-${selectedRows?.length}`} className={classes.actionPanel}>
              {bulkActions}
            </Group>
          }
          {
            !error && <Skeleton visible={loading} className={classes.skeletonPagination}>
              <Pagination.Root
                classNames={{
                  control: clsx(buttonClasses.button, classes.paginationButton)
                }}
                value={state.page}
                onChange={(page) => handleStateChange({ page })}
                total={totalPages}
                siblings={1}
                size="sm"
              >
                <Group gap={5} justify="center">
                  <Pagination.First className={classes.paginationButton} />
                  <Pagination.Previous className={classes.paginationButton} />
                  <Pagination.Items />
                  <Pagination.Next className={classes.paginationButton} />
                  <Pagination.Last className={classes.paginationButton} />
                </Group>
              </Pagination.Root>
            </Skeleton>
          }
        </div>
      </div>
    </>
  );
}
