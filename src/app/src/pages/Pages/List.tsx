import { useMemo } from 'react';
import { ActionIcon, Badge, Button, Group, Title } from '@mantine/core';
import { IconPencil, IconTrash, IconPlus } from '@tabler/icons-react';
import {
  DataTable,
  useUrlTableState,
  type ColumnDef,
} from '@/components/DataTable';
import { notify } from '@/lib/notify';
import { MOCK_PAGES, type PageRow } from './mockData';

export function PagesList() {
  const tableState = useUrlTableState({ defaults: { perPage: 10 } });
  const { state } = tableState;

  // Simulate server-side filtering/sorting/pagination using mock data.
  // In a real module this would be an API call using `state` as query params.
  const { rows, total } = useMemo(() => {
    let filtered = MOCK_PAGES;

    if (state.filter) {
      const q = state.filter.toLowerCase();
      filtered = filtered.filter(
        (p) =>
          p.title.toLowerCase().includes(q) ||
          p.slug.toLowerCase().includes(q) ||
          p.author.toLowerCase().includes(q),
      );
    }

    if (state.sortBy) {
      const key = state.sortBy as keyof PageRow;
      filtered = [...filtered].sort((a, b) => {
        const av = a[key];
        const bv = b[key];
        if (av < bv) return state.sortDir === 'asc' ? -1 : 1;
        if (av > bv) return state.sortDir === 'asc' ? 1 : -1;
        return 0;
      });
    }

    const total = filtered.length;
    const start = (state.page - 1) * state.perPage;
    return { rows: filtered.slice(start, start + state.perPage), total };
  }, [state]);

  const columns: ColumnDef<PageRow>[] = [
    {
      key: 'title',
      header: 'Title',
      sortable: true,
      link: (row) => `/admin/pages/${row.id}`,
    },
    {
      key: 'slug',
      header: 'Slug',
      sortable: true,
      render: (row) => <code>/{row.slug}</code>,
    },
    {
      key: 'status',
      header: 'Status',
      sortable: true,
      render: (row) => (
        <Badge
          color={row.status === 'published' ? 'teal' : 'gray'}
          variant="light"
        >
          {row.status}
        </Badge>
      ),
    },
    { key: 'author', header: 'Author', sortable: true },
    { key: 'updatedAt', header: 'Updated', sortable: true, width: 120 },
  ];

  return (
    <>
      <Group justify="space-between" mb="lg">
        <Title order={2}>Pages</Title>
        <Button
          leftSection={<IconPlus size={16} />}
          onClick={() => notify.info('Not implemented', 'Create page coming soon')}
        >
          New page
        </Button>
      </Group>

      <DataTable
        columns={columns}
        rows={rows}
        total={total}
        state={tableState}
        rowKey={(row) => row.id}
        filterable
        actions={(row) => (
          <>
            <ActionIcon
              component="a"
              href={`/admin/pages/${row.id}`}
              variant="subtle"
              aria-label="Edit"
            >
              <IconPencil size={16} />
            </ActionIcon>
            <ActionIcon
              variant="subtle"
              color="red"
              onClick={() => notify.warning('Delete', `Would delete "${row.title}"`)}
              aria-label="Delete"
            >
              <IconTrash size={16} />
            </ActionIcon>
          </>
        )}
      />
    </>
  );
}
