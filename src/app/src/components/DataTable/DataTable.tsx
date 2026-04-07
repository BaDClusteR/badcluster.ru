import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import {
  Group,
  Loader,
  Pagination,
  Select,
  TextInput,
} from '@mantine/core';
import { IconChevronUp, IconChevronDown, IconSelector, IconSearch } from '@tabler/icons-react';
import type { ColumnDef, DataTableProps } from './types';
import classes from './DataTable.module.css';

const DEFAULT_PER_PAGE_OPTIONS = [10, 25, 50, 100];

export function DataTable<T>({
  columns,
  rows,
  total,
  state: manager,
  rowKey,
  actions,
  loading = false,
  filterable = false,
  perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
  emptyMessage = 'No data',
}: DataTableProps<T>) {
  const { state, setState } = manager;

  // Local filter input, debounced into the state manager.
  const [filterInput, setFilterInput] = useState(state.filter);

  useEffect(() => {
    setFilterInput(state.filter);
  }, [state.filter]);

  useEffect(() => {
    if (filterInput === state.filter) return;
    const t = setTimeout(() => setState({ filter: filterInput }), 300);
    return () => clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filterInput]);

  const totalPages = Math.max(1, Math.ceil(total / state.perPage));
  const from = total === 0 ? 0 : (state.page - 1) * state.perPage + 1;
  const to = Math.min(total, state.page * state.perPage);

  function handleSort(col: ColumnDef<T>) {
    if (!col.sortable) return;
    if (state.sortBy !== col.key) {
      setState({ sortBy: col.key, sortDir: 'asc' });
    } else if (state.sortDir === 'asc') {
      setState({ sortDir: 'desc' });
    } else {
      setState({ sortBy: null, sortDir: 'asc' });
    }
  }

  function renderCell(col: ColumnDef<T>, row: T) {
    const content = col.render
      ? col.render(row)
      : col.accessor
      ? col.accessor(row)
      : (row as Record<string, unknown>)[col.key] as React.ReactNode;

    if (col.link) {
      return (
        <Link to={col.link(row)} className={classes.cellLink}>
          {content}
        </Link>
      );
    }
    return content;
  }

  return (
    <div className={classes.wrapper}>
      {filterable && (
        <TextInput
          placeholder="Search..."
          value={filterInput}
          onChange={(e) => setFilterInput(e.currentTarget.value)}
          leftSection={<IconSearch size={16} />}
          style={{ maxWidth: 320 }}
        />
      )}

      <div className={classes.tableCard}>
        {loading && (
          <div className={classes.loadingOverlay}>
            <Loader />
          </div>
        )}

        <table className={classes.table}>
          <thead>
            <tr>
              {columns.map((col) => {
                const isSorted = state.sortBy === col.key;
                return (
                  <th
                    key={col.key}
                    style={{
                      width: col.width,
                      textAlign: col.align ?? 'left',
                    }}
                    className={col.sortable ? classes.sortable : undefined}
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
              {actions && <th className={classes.actionsCell}>Actions</th>}
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && !loading && (
              <tr>
                <td
                  colSpan={columns.length + (actions ? 1 : 0)}
                  className={classes.empty}
                >
                  {emptyMessage}
                </td>
              </tr>
            )}
            {rows.map((row) => (
              <tr key={rowKey(row)}>
                {columns.map((col) => (
                  <td key={col.key} style={{ textAlign: col.align ?? 'left' }}>
                    {renderCell(col, row)}
                  </td>
                ))}
                {actions && (
                  <td className={classes.actionsCell}>
                    <Group gap="xs" justify="flex-end" wrap="nowrap">
                      {actions(row)}
                    </Group>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className={classes.footer}>
        <Group gap="sm">
          <span className={classes.footerInfo}>
            {from}–{to} of {total}
          </span>
          <Select
            size="xs"
            value={String(state.perPage)}
            onChange={(v) => v && setState({ perPage: Number(v), page: 1 })}
            data={perPageOptions.map((n) => ({ value: String(n), label: `${n} / page` }))}
            w={110}
            allowDeselect={false}
          />
        </Group>

        <Pagination
          value={state.page}
          onChange={(page) => setState({ page })}
          total={totalPages}
          siblings={1}
          size="sm"
        />
      </div>
    </div>
  );
}