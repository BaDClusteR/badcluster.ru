import { lazy, Suspense } from 'react';
import {
  Button,
  Grid,
  Group,
  Loader,
  NumberInput,
  Select,
  Stack,
  Switch,
  TextInput,
  Textarea,
} from '@mantine/core';
import { useForm } from '@mantine/form';
import type { EntityFormProps, FieldDef } from './types';

// Editor.js and all its plugins are heavy (~400 KB). Load them only when a
// form actually renders a `blocks` field — the import is resolved lazily and
// Rolldown splits it into a separate chunk.
const BlocksField = lazy(() =>
  import('./fields/BlocksField').then((m) => ({ default: m.BlocksField })),
);

/**
 * Schema-driven form. Pass a list of FieldDefs and initial values;
 * the form renders the right Mantine input for each field type.
 *
 * Supported types: text, textarea, number, select, switch, blocks (Editor.js).
 * Extend by adding cases to `renderField` and optionally a new component.
 */
export function EntityForm<T extends Record<string, unknown>>({
  fields,
  initialValues,
  onSubmit,
  submitLabel = 'Save',
  onCancel,
}: EntityFormProps<T>) {
  const form = useForm<T>({
    initialValues,
    validate: Object.fromEntries(
      fields
        .filter((f) => f.required)
        .map((f) => [
          f.name,
          (value: unknown) => {
            if (value == null) return `${f.label} is required`;
            if (typeof value === 'string' && value.trim() === '') {
              return `${f.label} is required`;
            }
            return null;
          },
        ]),
    ) as never,
  });

  function renderField(field: FieldDef) {
    const common = {
      label: field.label,
      description: field.description,
      placeholder: field.placeholder,
      required: field.required,
    };

    switch (field.type) {
      case 'text':
        return <TextInput {...common} {...form.getInputProps(field.name)} />;
      case 'textarea':
        return (
          <Textarea
            {...common}
            autosize
            minRows={3}
            {...form.getInputProps(field.name)}
          />
        );
      case 'number':
        return <NumberInput {...common} {...form.getInputProps(field.name)} />;
      case 'select':
        return (
          <Select
            {...common}
            data={field.options ?? []}
            {...form.getInputProps(field.name)}
          />
        );
      case 'switch':
        return (
          <Switch
            label={field.label}
            description={field.description}
            {...form.getInputProps(field.name, { type: 'checkbox' })}
          />
        );
      case 'blocks':
        return (
          <Suspense
            fallback={
              <Group justify="center" py="xl">
                <Loader size="sm" />
              </Group>
            }
          >
            <BlocksField
              label={field.label}
              description={field.description}
              value={form.values[field.name] as never}
              onChange={(data) => form.setFieldValue(field.name, data as never)}
            />
          </Suspense>
        );
    }
  }

  return (
    <form onSubmit={form.onSubmit(async (values) => { await onSubmit(values); })}>
      <Stack gap="lg">
        <Grid>
          {fields.map((field) => (
            <Grid.Col
              key={field.name}
              span={{ base: 12, md: field.span === 'half' ? 6 : 12 }}
            >
              {renderField(field)}
            </Grid.Col>
          ))}
        </Grid>

        <Group justify="flex-end">
          {onCancel && (
            <Button variant="subtle" onClick={onCancel} type="button">
              Cancel
            </Button>
          )}
          <Button type="submit" loading={form.submitting}>
            {submitLabel}
          </Button>
        </Group>
      </Stack>
    </form>
  );
}
