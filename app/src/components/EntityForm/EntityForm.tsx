import {lazy, type ReactNode, useEffect, useRef, useState} from "react";
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
  Fieldset
} from "@mantine/core";
import {DateTimePicker, DateTimeStringValue} from "@mantine/dates";
import "@mantine/dates/styles.layer.css";
import {useForm} from "@mantine/form";
import {useQuery} from "@tanstack/react-query";
import {HttpError} from "@/utils/errors";
import {notify} from "@/lib/notify";
import {IconError, IconEmpty} from "@/components/List/components/Icons";
import type {EntityCreatedResponse, EntityFormProps, FieldDef, FieldDefNamed} from "@admin/types";
import classes from "./EntityForm.module.css";
import Button from "@/components/primitives/Button.tsx";
import Slug from "@/components/primitives/Slug.tsx";
import {ImageField} from "./fields/ImageField";
import {FileField} from "./fields/FileField";
import FieldGroup from "./FieldGroup";
import type {EntityFormComponents} from "@admin/types";
import dtClasses from "./fields/DateTimePicker.module.css";
import clsx from "clsx";
import {useNavigate} from "react-router";
import {buildAdminUrl} from "@/utils/buildAdminUrl.ts";
import apiCall from "@/utils/apiCall";

const BlocksField = lazy(() =>
  import("./fields/BlocksField").then((m) => ({default: m.BlocksField}))
);

/** Tracks character count via DOM input events (works with uncontrolled forms). */
function CharCounter({maxLength, initial = ""}: { maxLength: number; initial?: string }) {
  const [len, setLen] = useState(String(initial ?? "").length);
  const ref = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement | HTMLTextAreaElement | null>(null);

  // Attach input listener on mount
  useEffect(() => {
    const wrapper = ref.current?.parentElement;
    if (!wrapper) return;
    const input = wrapper.querySelector("input, textarea") as HTMLInputElement | HTMLTextAreaElement | null;
    if (!input) return;
    inputRef.current = input;

    setLen(input.value.length);
    const handler = () => setLen(input.value.length);
    input.addEventListener("input", handler);
    return () => input.removeEventListener("input", handler);
  }, []);

  // Sync when form data arrives (initial prop changes from form.values)
  useEffect(() => {
    // Re-read DOM value — form.initialize() updates it without firing 'input' event
    requestAnimationFrame(() => {
      if (inputRef.current) {
        setLen(inputRef.current.value.length);
      }
    });
  }, [initial]);

  const over = len > maxLength;
  return (
    <div ref={ref}>
      <Text size="xs" ta="right" mt={4} c={over ? "yellow" : "dimmed"}>
        {len} / {maxLength}
      </Text>
    </div>
  );
}

/** Components bag passed to group render functions so modules don't need direct imports. */
const formComponents: EntityFormComponents = {
  BlocksField: BlocksField as unknown as EntityFormComponents["BlocksField"],
  FieldGroup
};

type FieldChunk<T, C> =
  | { type: "single"; field: FieldDef<T, C> }
  | { type: "fieldset"; legend: string; fields: FieldDef<T, C>[] };

/** Group consecutive fields with the same `fieldset` value. */
function chunkFields<T, C>(fields: FieldDef<T, C>[]): FieldChunk<T, C>[] {
  const chunks: FieldChunk<T, C>[] = [];

  for (const field of fields) {
    if (field.fieldset) {
      const prev = chunks[chunks.length - 1];
      if (prev && prev.type === "fieldset" && prev.legend === field.fieldset) {
        prev.fields.push(field);
      } else {
        chunks.push({type: "fieldset", legend: field.fieldset, fields: [field]});
      }
    } else {
      chunks.push({type: "single", field});
    }
  }

  return chunks;
}

export function EntityForm<T extends Record<string, any>, C = unknown>(
  {
    fields,
    dataProvider,
    initialValues,
    context,
    title,
    labels,
    webPath,
    apiEndpoint
  }: EntityFormProps<T, C>
) {
  const isCreateMode = !dataProvider;
  const navigate = useNavigate();

  const {data, error, isLoading, refetch} = useQuery<T>({
    queryKey: dataProvider?.queryKey ?? ["__entity-form-disabled__"],
    queryFn: ({signal}) => dataProvider!.getData(signal),
    enabled: !!dataProvider,
    retry: false
  });

  const form = useForm<T>({
    mode: "uncontrolled",
    initialValues: (initialValues ?? {}) as T,
    validate: (values: T) => {
      const errors: Record<string, string> = {};
      for (const f of fields) {
        if (!("name" in f) || (!f.required && !f.validate)) continue;
        // Skip validation for hidden fields
        if (f.visible && !f.visible(values)) continue;

        const name = (f as FieldDefNamed<T>).name as string;
        const value = values[name];

        if (f.required) {
          if (value == null) { errors[name] = "Обязательное поле"; continue; }
          if (typeof value === "string" && value.trim() === "") { errors[name] = "Обязательное поле"; continue; }
        }
        if (f.validate) {
          const err = f.validate(value);
          if (err) errors[name] = err;
        }
      }
      return errors;
    }
  });

  useEffect(() => {
    if (data && !form.isDirty()) {
      form.initialize(data);
    }
  }, [data]);

  const formRef = useRef<HTMLFormElement>(null);

  // Warn before leaving with unsaved changes
  useEffect(() => {
    const handler = (e: BeforeUnloadEvent) => {
      if (form.isDirty()) {
        e.preventDefault();
      }
    };
    window.addEventListener("beforeunload", handler);
    return () => window.removeEventListener("beforeunload", handler);
  }, []);

  // Ctrl+S / Cmd+S → submit
  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key === "s") {
        e.preventDefault();
        formRef.current?.requestSubmit();
      }
    };
    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  }, []);

  // --- State branches ---

  const is404 = error instanceof HttpError && error.status === 404;

  if (is404) {
    return (
      <Stack align="center" gap="md" py="xl">
        <IconEmpty/>
        <Text c="dimmed" size="lg">{labels.notFound.text ?? "Не найдено"}</Text>
        <Button
          variant="subtle"
          onClick={() => {
            navigate(buildAdminUrl(webPath));
          }}
        >
          {labels.notFound.btnCaption ?? "Назад"}
        </Button>
      </Stack>
    );
  }

  if (error) {
    return (
      <Stack align="center" gap="md" py="xl">
        <IconError/>
        <Text c="dimmed" size="lg">Что-то пошло не так</Text>
        <Button onClick={() => refetch()}>Попробовать ещё раз</Button>
      </Stack>
    );
  }

  const primaryFields = fields.filter((f) => f.role === "primary");
  const secondaryFields = fields.filter((f) => f.role !== "primary");

  if (isLoading) {
    return (
      <div className={`${classes.skeletonContainer} skeletonContainer`}>
        {renderSection(primaryFields, renderSkeleton, false)}
        {secondaryFields.length > 0 && renderSection(secondaryFields, renderSkeleton, true)}
      </div>
    );
  }

  // --- Normal form ---

  function renderField(field: FieldDef<T, C>) {
    if (field.visible && !field.visible(form.values)) {
      return null;
    }

    const common = {
      label: "label" in field ?
        field.label
        : undefined,
      description: field.hint,
      placeholder: ("placeholder" in field) ?
        field.placeholder
        : undefined,
      withAsterisk: field.required
    };

    function withCounter(input: ReactNode, fieldDef: typeof field) {
      if (!("softMaxLength" in fieldDef) || !fieldDef.softMaxLength) return input;
      return (
        <div>
          {input}
          <CharCounter
            maxLength={fieldDef.softMaxLength}
            initial={String(form.values[fieldDef.name] ?? "")}
          />
        </div>
      );
    }

    switch (field.type) {
      case "text":
        return withCounter(
          <TextInput
            {...common}
            value={form.values[field.name] as string ?? ""}
            onChange={(e) => form.setFieldValue(field.name as string, e.currentTarget.value as never)}
            error={form.errors[field.name as string]}
          />,
          field
        );
      case "slug":
        return <Slug
          url={(slug: string) => field.url(slug, form.values, context)}
          {...common}
          {...form.getInputProps(field.name as string)}
        />;

      case "textarea":
        return withCounter(
          <Textarea
            {...common}
            autosize
            minRows={3}
            value={form.values[field.name] as string ?? ""}
            onChange={(e) => form.setFieldValue(field.name as string, e.currentTarget.value as never)}
            error={form.errors[field.name as string]}
          />,
          field
        );
      case "number":
        return <NumberInput
          {...common}
          {...form.getInputProps(field.name as string)}
        />;
      case "select":
        return (
          <Select
            {...common}
            data={field.options ?? []}
            value={form.values[field.name] as string ?? null}
            onChange={(val) => form.setFieldValue(field.name as string, val as never)}
            error={form.errors[field.name as string]}
          />
        );
      case "switch":
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
            checked={!!form.values[field.name]}
            onChange={(e) => form.setFieldValue(field.name as string, e.currentTarget.checked as never)}
          />
        );
      case "blocks":
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
              presetButton: dtClasses.presetButton
            }}
            label={field.label}
            value={form.values[field.name] as DateTimeStringValue}
            onChange={
              (value) => form.setFieldValue(field.name as string, value as never)
            }
            error={form.errors[field.name as string]}
            clearable={field.clearable}
            valueFormat={field.valueFormat}
            presets={[
              {value: new Date().toISOString(), label: "Сейчас"}
            ]}
          />
        );
      case "image":
        return (
          <ImageField
            label={field.label}
            description={field.hint}
            withAsterisk={field.required}
            error={form.errors[field.name as string]}
            value={form.values[field.name] as any}
            onChange={(media) => form.setFieldValue(field.name as string, media as never)}
            previewWidth={field.previewWidth}
            thumbnailWidth={field.thumbnailWidth}
            thumbnailHeight={field.thumbnailHeight}
            uploadPurpose={field.uploadPurpose}
            showAlt={field.showAlt}
          />
        );
      case "file":
        return (
          <FileField
            label={field.label}
            description={field.hint}
            withAsterisk={field.required}
            error={form.errors[field.name as string]}
            value={form.values[field.name] as any}
            onChange={(file) => form.setFieldValue(field.name as string, file as never)}
            uploadEndpoint={field.uploadEndpoint}
            uploadFields={field.uploadFields}
            accept={field.accept}
          />
        );
      case "heading":
        return (
          <Title order={4} className={classes.sectionHeading}>
            {field.label}
          </Title>
        );
      case "group":
        return field.render
          ? field.render(form, {
            loading: false,
            submitting: form.submitting,
            context,
            components: formComponents
          }, form.values)
          : null;
    }
  }

  function renderSection(sectionFields: FieldDef<T, C>[], renderFn: (field: FieldDef<T, C>) => ReactNode, asCard: boolean) {
    if (sectionFields.length === 0) return null;

    const content = (
      <Grid className={classes.formFieldsGrid}>
        {renderChunks(chunkFields(sectionFields), renderFn)}
      </Grid>
    );

    if (!asCard) return content;

    return (
      <Paper className={clsx(classes.card, isLoading && classes.cardLoading)}>
        {content}
      </Paper>
    );
  }

  function renderChunks(chunks: FieldChunk<T, C>[], renderFn: (field: FieldDef<T, C>) => ReactNode) {
    return chunks.map((chunk, ci) => {
      if (chunk.type === "single") {
        const key = "name" in chunk.field
          ? (chunk.field.name as string)
          : (
            ("label" in chunk.field)
              ? chunk.field.label
              : `chunk-${ci}`
          );
        return (
          <Grid.Col
            key={key}
            span={{base: 12, md: chunk.field.span === "full" ? 12 : 6}}
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
                    ("name" in field)
                      ? (field.name as string)
                      : `${chunk.legend}-${fi}`
                  }
                  span={{base: 12, md: field.span === "full" ? 12 : 6}}
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

  function renderSkeleton(field: FieldDef<T, C>) {
    if (field.type === "group") {
      return field.render ? field.render(form, {
        loading: true,
        submitting: false,
        context,
        components: formComponents
      }, form.values) : <Skeleton height={100}/>;
    }

    if (field.type === "heading") {
      return <Skeleton height={24} width={160}/>;
    }

    const heights: Record<string, number> = {
      text: 36,
      number: 36,
      select: 36,
      textarea: 80,
      switch: 24,
      blocks: 200,
      datetime: 36,
      image: 120,
      file: 48
    };

    return (
      <Stack gap={4}>
        {field.label && <Skeleton height={16} width={120}/>}
        <Skeleton height={heights[field.type] ?? 36}/>
      </Stack>
    );
  }

  const titleNode = title && form.values
    ? <h2 className={classes.formTitle}>{
      typeof title === "string"
        ? title
        : title(form.values, context)
    }</h2>
    : null;

  return (
    <>
      {titleNode}
      <form ref={formRef} className="entity-form" onSubmit={form.onSubmit(async (values) => {
        try {
          if (isCreateMode) {
            const result = await apiCall("POST", `${apiEndpoint}`, values) as EntityCreatedResponse;
            form.resetDirty(values);
            navigate(
              buildAdminUrl(`${webPath}/${result.id}`),
              {replace: true}
            );
            if (labels.messages.onCreate) {
              notify.success("Создано", labels.messages.onCreate);
            }
          } else {
            await apiCall("PUT", `${apiEndpoint}/${dataProvider?.entityId}`, values);
            form.resetDirty(values);
            if (labels.messages.onUpdate) {
              notify.success("Сохранено", labels.messages.onUpdate);
            }
          }
        } catch (err) {
          if (err instanceof HttpError && err.status === 422 && err.payload?.errors) {
            form.setErrors(err.payload.errors as Record<string, string>);
            if (err.payload.message) {
              notify.error(String(err.payload.message));
            }
          } else {
            throw err;
          }
        }
      })}>
        <Stack gap="lg">
          {renderSection(primaryFields, renderField, false)}
          {secondaryFields.length > 0 && renderSection(secondaryFields, renderField, true)}

          <Group justify="flex-end">
            <Button type="submit" loading={form.submitting}>
              {
                isCreateMode
                  ? labels.submit.create
                  : labels.submit.update
              }
            </Button>
          </Group>
        </Stack>
      </form>
    </>
  );
}
