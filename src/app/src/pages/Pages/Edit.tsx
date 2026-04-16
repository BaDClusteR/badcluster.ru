import { useMemo } from 'react';
import { useNavigate, useParams } from 'react-router';
import { Anchor, Breadcrumbs, Title } from '@mantine/core';
import { EntityForm, type FieldDef } from '@/components/EntityForm';
import { notify } from '@/lib/notify';
import { MOCK_PAGES } from '@/pages/Pages/mockData.ts';

const FIELDS: FieldDef[] = [
  {
    name: 'title',
    label: 'Title',
    type: 'text',
    required: true,
    placeholder: 'Page title',
    span: 'half',
  },
  {
    name: 'slug',
    label: 'Slug',
    type: 'text',
    required: true,
    placeholder: 'url-friendly-name',
    span: 'half',
  },
  {
    name: 'status',
    label: 'Status',
    type: 'select',
    options: [
      { value: 'draft', label: 'Draft' },
      { value: 'published', label: 'Published' },
    ],
    span: 'half',
  },
  {
    name: 'featured',
    label: 'Featured',
    type: 'switch',
    description: 'Show on the home page',
    span: 'half',
  },
  {
    name: 'content',
    label: 'Content',
    type: 'blocks',
    description: 'Block-based content, rendered to HTML/Markdown/etc by backend renderers',
  },
];

export function PageEdit() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const page = useMemo(
    () => MOCK_PAGES.find((p) => String(p.id) === id),
    [id],
  );

  if (!page) {
    return <Title order={3}>Page not found</Title>;
  }

  const initialValues = {
    title: page.title,
    slug: page.slug,
    status: page.status,
    featured: false,
    content: {
      blocks: [
        { type: 'header', data: { text: page.title, level: 2 } },
        { type: 'paragraph', data: { text: 'Start editing this page...' } },
      ],
    },
  };

  return (
    <>
      <Breadcrumbs mb="sm">
        <Anchor onClick={() => navigate('/admin/pages')} style={{ cursor: 'pointer' }}>
          Pages
        </Anchor>
        <span>{page.title}</span>
      </Breadcrumbs>

      <Title order={2} mb="lg">
        Edit page
      </Title>

      <EntityForm
        fields={FIELDS}
        initialValues={initialValues}
        onSubmit={async (values) => {
          // eslint-disable-next-line no-console
          console.log('Submitting', values);
          await new Promise((r) => setTimeout(r, 500));
          notify.success('Saved', `"${values.title as string}" updated`);
        }}
        onCancel={() => navigate('/admin/pages')}
      />
    </>
  );
}
