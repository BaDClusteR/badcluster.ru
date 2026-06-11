import {Skeleton, Textarea} from "@mantine/core";
import classes from "./ChapterEditor.module.css";
import {FormErrors, UseFormReturnType} from "@mantine/form";
import {EntityFormRenderOptions} from "@admin/types";
import React from "react";
import {Chapter} from "../../types";

export default function ChapterEditor(
  {
    form,
    options
  }: {
    form: UseFormReturnType<Chapter, Chapter, (values: Chapter) => FormErrors>,
    options: EntityFormRenderOptions
  }
): React.JSX.Element {
  const {BlocksField, FieldGroup} = options!.components;
  const titleProps = form.getInputProps("title");
  return <>
    <FieldGroup isSubmitting={form.submitting}>
      <Skeleton visible={options?.loading}>
        <Textarea
          autosize
          placeholder="Заголовок"
          {...titleProps}
          classNames={{input: `page-title ${classes.chapterTitle}`}}
          onKeyDown={
            (e) => {
              if (e.key === "Enter") {
                e.preventDefault();
              }
            }
          }
          onChange={
            (e) => {
              e.target.value = e.target.value.replace(/[\r\n]+/gm, " ");
              titleProps.onChange(e);
            }
          }
        />
        <BlocksField
          showSettings={false}
          uploadPurpose="chapter"
          className={classes.contentInline}
          value={form.values.content as never}
          onChange={(data) => {
            form.setFieldValue("content", data as never);
          }}
        />
      </Skeleton>
    </FieldGroup>
  </>;
}
