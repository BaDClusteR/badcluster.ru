import {lazy, type ReactNode, useEffect} from "react";
import {
  Grid,
  Group,
  NumberInput,
  Paper,
  Select,
  Skeleton,
  Stack,
  Switch,
  Text,
  TextInput,
  Textarea,
  Title,
  Fieldset,
} from "@mantine/core";
import {DateTimePicker, DateTimeStringValue} from "@mantine/dates";
import "@mantine/dates/styles.layer.css";
import { useForm } from '@mantine/form';
import { useQuery } from '@tanstack/react-query';
import { HttpError } from '@/utils/errors';
import { IconError, IconEmpty } from '@/components/List/components/Icons';
import type {EntityFormProps, FieldDef, FieldDefNamed} from "./types";
import classes from "./EntityForm.module.css";
import Button from "@/components/primitives/Button.tsx";
import Slug from "@/components/primitives/Slug.tsx";
import dtClasses from "./fields/DateTimePicker.module.css";

const BlocksField = lazy(() =>
  import('./fields/BlocksField').then((m) => ({ default: m.BlocksField })),
);

type FieldChunk<T> =
  | { type: 'single'; field: FieldDef<T> }
  | { type: 'fieldset'; legend: string; fields: FieldDef<T>[] };

/** Group consecutive fields with the same `fieldset` value. */
function chunkFields<T>(fields: FieldDef<T>[]): FieldChunk<T>[] {
  const chunks: FieldChunk<T>[] = [];

  for (const field of fields) {
    if (field.fieldset) {
      const prev = chunks[chunks.length - 1];
      if (prev && prev.type === 'fieldset' && prev.legend === field.fieldset) {
        prev.fields.push(field);
      } else {
        chunks.push({ type: 'fieldset', legend: field.fieldset, fields: [field] });
      }
    } else {
      chunks.push({ type: 'single', field });
    }
  }

  return chunks;
}

export function EntityForm<T extends Record<string, unknown>, C = unknown>({
  fields,
  dataProvider,
  context,
  onSubmit,
  submitLabel = 'Save',
  onCancel,
  notFoundText,
  notFoundBtnCaption
}: EntityFormProps<T, C>) {
  const { data, error, isLoading, refetch } = useQuery<T>({
    queryKey: dataProvider?.queryKey ?? ['__entity-form-disabled__'],
    queryFn: ({ signal }) => dataProvider!.getData(signal),
    enabled: !!dataProvider,
    retry: false
  });

  const form = useForm<T>({
    mode: 'uncontrolled',
    initialValues: {} as T,
    validate: Object.fromEntries(
      fields
        .filter((f) => f.required && ('name' in f))
        .map((f) => [
          (f as FieldDefNamed<T>).name,
          (value: unknown) => {
            if (value == null) return `${(f as FieldDefNamed<T>).label} is required`;
            if (typeof value === 'string' && value.trim() === '') {
              return `${(f as FieldDefNamed<T>).label} is required`;
            }
            return null;
          },
        ]),
    ) as never,
  });

  useEffect(() => {
    if (data && !form.isDirty()) {
      form.initialize(data);
    }
  }, [data]);

  // --- State branches ---

  const is404 = error instanceof HttpError && error.status === 404;

  if (is404) {
    return (
      <Stack align="center" gap="md" py="xl">
        <IconEmpty />
        <Text c="dimmed" size="lg">{notFoundText ?? "Не найдено"}</Text>
        {
          onCancel && (
            <Button variant="subtle" onClick={onCancel}>{notFoundBtnCaption ?? "Назад"}</Button>
          )
        }
      </Stack>
    );
  }

  if (error) {
    return (
      <Stack align="center" gap="md" py="xl">
        <IconError />
        <Text c="dimmed" size="lg">Что-то пошло не так</Text>
        <Button onClick={() => refetch()}>Попробовать ещё раз</Button>
      </Stack>
    );
  }

  const primaryFields = fields.filter((f) => f.role === 'primary');
  const secondaryFields = fields.filter((f) => f.role !== 'primary');

  if (isLoading) {
    return (
      <>
        {renderSection(primaryFields, renderSkeleton, false)}
        {secondaryFields.length > 0 && renderSection(secondaryFields, renderSkeleton, true)}
      </>
    );
  }

  // --- Normal form ---

  function renderField(field: FieldDef<T>) {
    const common = {
      label: 'label' in field ?
        field.label
        : undefined,
      description: field.hint,
      placeholder: ('placeholder' in field) ?
        field.placeholder
        : undefined,
      required: field.required,
    };

    switch (field.type) {
      case 'text':
        return <TextInput
          {...common}
          {...form.getInputProps(field.name as string)}
        />;
      case 'slug':
        return <Slug
          url={field.url}
          {...common}
          {...form.getInputProps(field.name as string)}
        />

      case 'textarea':
        return (
          <Textarea
            {...common}
            autosize
            minRows={3}
            {...form.getInputProps(field.name as string)}
          />
        );
      case 'number':
        return <NumberInput
          {...common}
          {...form.getInputProps(field.name as string)}
        />;
      case 'select':
        return (
          <Select
            {...common}
            data={field.options ?? []}
            {...form.getInputProps(field.name as string)}
          />
        );
      case 'switch':
        return (
          <Switch
            classNames={{
              body: classes.switchBody,
              labelWrapper: classes.switchLabelWrapper,
              label: classes.switchLabel,
              track: classes.switchTrack
            }}
            label={field.label}
            description={field.hint}
            {...form.getInputProps(field.name as string, { type: 'checkbox' })}
          />
        );
      case 'blocks':
        return (
          <BlocksField
            {...common}
            description={field.hint}
            value={form.values[field.name] as never}
            onChange={(data) => form.setFieldValue(field.name as string, data as never)}
          />
        );
      case "datetime":
        return (
          <DateTimePicker
            withAsterisk={field.required}
            classNames={{
              day: dtClasses.day,
              // @ts-expect-error presetButton does exist in classNames, but TS for some reason doesn't see it
              presetButton: dtClasses.presetButton,
            }}
            label={field.label}
            value={form.values[field.name] as DateTimeStringValue}
            onChange={
              (value) => form.setFieldValue(field.name as string, value as never)
            }
            clearable={field.clearable}
            valueFormat={field.valueFormat}
            presets={[
              {value: new Date().toISOString(), label: 'Сейчас'}
            ]}
          />
        );
      case 'heading':
        return (
          <Title order={4} className={classes.sectionHeading}>
            {field.label}
          </Title>
        );
      case 'group':
        return field.render
          ? field.render(form, { loading: false, context })
          : null;
    }
  }

  function renderSection(sectionFields: FieldDef<T>[], renderFn: (field: FieldDef<T>) => ReactNode, asCard: boolean) {
    if (sectionFields.length === 0) return null;

    const content = (
      <Grid>
        {renderChunks(chunkFields(sectionFields), renderFn)}
      </Grid>
    );

    if (!asCard) return content;

    return (
      <Paper className={classes.card}>
        {content}
      </Paper>
    );
  }

  function renderChunks(chunks: FieldChunk<T>[], renderFn: (field: FieldDef<T>) => ReactNode) {
    return chunks.map((chunk, ci) => {
      if (chunk.type === 'single') {
        const key = 'name' in chunk.field
          ? (chunk.field.name as string)
          : (
            ('label' in chunk.field)
              ? chunk.field.label
              : `chunk-${ci}`
          );
        return (
          <Grid.Col
            key={key}
            span={{ base: 12, md: chunk.field.span === 'half' ? 6 : 12 }}
          >
            {renderFn(chunk.field)}
          </Grid.Col>
        );
      }

      return (
        <Grid.Col key={`fieldset-${chunk.legend}`} span={12}>
          <Fieldset className={classes.fieldset} legend={chunk.legend}>
            <Grid>
              {chunk.fields.map((field, fi) => (
                <Grid.Col
                  key={
                    ('name' in field)
                      ? (field.name as string)
                      : `${chunk.legend}-${fi}`
                  }
                  span={{ base: 12, md: field.span === 'half' ? 6 : 12 }}
                >
                  {renderFn(field)}
                </Grid.Col>
              ))}
            </Grid>
          </Fieldset>
        </Grid.Col>
      );
    });
  }

  function renderSkeleton(field: FieldDef<T>) {
    if (field.type === 'group') {
      return field.render ? field.render(form, { loading: true, context }) : <Skeleton height={100} />;
    }

    if (field.type === 'heading') {
      return <Skeleton height={24} width={160} />;
    }

    const heights: Record<string, number> = {
      text: 36,
      number: 36,
      select: 36,
      textarea: 80,
      switch: 24,
      blocks: 200,
      datetime: 36,
    };

    return (
      <Stack gap={4}>
        {field.label && <Skeleton height={16} width={120} />}
        <Skeleton height={heights[field.type] ?? 36} />
      </Stack>
    );
  }

  return (
    <form className="entity-form" onSubmit={form.onSubmit(async (values) => { await onSubmit(values); })}>
      <Stack gap="lg">
        {renderSection(primaryFields, renderField, false)}
        {secondaryFields.length > 0 && renderSection(secondaryFields, renderField, true)}

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
